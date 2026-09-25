import 'dart:async';

import 'package:dart_pusher_channels/dart_pusher_channels.dart';
import 'package:flutter/foundation.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';

/// Laravel Reverb (Pusher protocol) client for private user chat toasts.
class ChatRealtimeClient {
  ChatRealtimeClient({required TokenStorage tokenStorage})
    : _tokenStorage = tokenStorage; // ignore: prefer_initializing_formals

  final TokenStorage _tokenStorage;
  final _messages = StreamController<ChatToastPayload>.broadcast();
  final _joinAccepted = StreamController<void>.broadcast();

  PusherChannelsClient? _client;
  StreamSubscription<void>? _connectionSub;
  StreamSubscription<ChannelReadEvent>? _eventSub;
  StreamSubscription<ChannelReadEvent>? _joinSub;
  PrivateChannel? _channel;
  int? _userId;
  bool _connected = false;

  Stream<ChatToastPayload> get messages => _messages.stream;

  /// Fires when the server broadcasts `.join.accepted` to this user.
  Stream<void> get joinAccepted => _joinAccepted.stream;

  bool get isConnected => _connected;

  Future<bool> connect({
    required int userId,
    required SiteBroadcastConfig config,
  }) async {
    await disconnect();

    if (!config.enabled ||
        config.key == null ||
        config.key!.isEmpty ||
        config.wsHost == null ||
        config.wsHost!.isEmpty) {
      return false;
    }

    final token = await _tokenStorage.read();
    if (token == null || token.isEmpty) {
      return false;
    }

    _userId = userId;
    final authUri = Uri.parse('${Env.apiBaseUrl}/broadcasting/auth');

    try {
      final options = PusherChannelsOptions.fromHost(
        scheme: config.wsScheme,
        host: config.wsHost!,
        key: config.key!,
        port: config.wsPort,
        shouldSupplyMetadataQueries: true,
        metadata: PusherChannelsOptionsMetadata.byDefault(),
      );

      final client = PusherChannelsClient.websocket(
        options: options,
        connectionErrorHandler: (exception, trace, refresh) {
          _connected = false;
          if (kDebugMode) {
            debugPrint('ChatRealtime: $exception');
          }
          // Soft reconnect; poll fallback covers gaps.
          Future<void>.delayed(const Duration(seconds: 2), refresh);
        },
        minimumReconnectDelayDuration: const Duration(seconds: 2),
      );
      _client = client;

      final channel = client.privateChannel(
        'private-App.Models.User.$userId',
        authorizationDelegate:
            EndpointAuthorizableChannelTokenAuthorizationDelegate.forPrivateChannel(
              authorizationEndpoint: authUri,
              headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer $token',
              },
            ),
      );
      _channel = channel;

      _connectionSub = client.onConnectionEstablished.listen((_) {
        _connected = true;
        channel.subscribeIfNotUnsubscribed();
      });

      _eventSub = channel.bind('chat.message').listen((event) {
        final data = event.tryGetDataAsMap();
        if (data == null) return;
        final payload = ChatToastPayload.fromJson(data);
        if (payload.eventId <= 0) return;
        if (!_messages.isClosed) {
          _messages.add(payload);
        }
      });

      // Join-accepted: bump notification badge via side-channel stream.
      _joinSub = channel.bind('join.accepted').listen((event) {
        if (!_joinAccepted.isClosed) {
          _joinAccepted.add(null);
        }
      });

      unawaited(client.connect());
      return true;
    } catch (e, st) {
      if (kDebugMode) {
        debugPrint('ChatRealtime connect failed: $e\n$st');
      }
      await disconnect();
      return false;
    }
  }

  Future<void> disconnect() async {
    _connected = false;
    await _eventSub?.cancel();
    _eventSub = null;
    await _joinSub?.cancel();
    _joinSub = null;
    await _connectionSub?.cancel();
    _connectionSub = null;
    try {
      _channel?.unsubscribe();
    } catch (_) {}
    _channel = null;
    try {
      _client?.dispose();
    } catch (_) {}
    _client = null;
    _userId = null;
  }

  Future<void> dispose() async {
    await disconnect();
    await _messages.close();
    await _joinAccepted.close();
  }

  int? get userId => _userId;
}
