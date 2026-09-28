import 'dart:async';

import 'package:dart_pusher_channels/dart_pusher_channels.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';

/// Laravel Pusher/Reverb client for private user toasts + event chat threads.
class ChatRealtimeClient {
  ChatRealtimeClient({required TokenStorage tokenStorage})
    : _tokenStorage = tokenStorage;

  final TokenStorage _tokenStorage;
  final _messages = StreamController<InAppToastPayload>.broadcast();
  final _joinAccepted = StreamController<InAppToastPayload>.broadcast();
  final _joinRequested = StreamController<InAppToastPayload>.broadcast();
  final _friendRequested = StreamController<InAppToastPayload>.broadcast();
  final _messageReacted = StreamController<InAppToastPayload>.broadcast();
  final _threadPosted = StreamController<ChatMessage>.broadcast();
  final _threadReacted = StreamController<ChatMessageReaction>.broadcast();
  final _dmMessages = StreamController<InAppToastPayload>.broadcast();
  final _dmThreadPosted = StreamController<DmMessage>.broadcast();

  PusherChannelsClient? _client;
  StreamSubscription<void>? _connectionSub;
  StreamSubscription<ChannelReadEvent>? _eventSub;
  StreamSubscription<ChannelReadEvent>? _joinSub;
  StreamSubscription<ChannelReadEvent>? _joinRequestedSub;
  StreamSubscription<ChannelReadEvent>? _friendRequestedSub;
  StreamSubscription<ChannelReadEvent>? _messageReactedSub;
  StreamSubscription<ChannelReadEvent>? _threadSub;
  StreamSubscription<ChannelReadEvent>? _threadReactSub;
  PrivateChannel? _userChannel;
  PrivateChannel? _eventChannel;
  PrivateChannel? _dmChannel;
  String? _authToken;
  int? _userId;
  int? _subscribedEventId;
  int? _subscribedDmId;
  StreamSubscription<ChannelReadEvent>? _dmToastSub;
  StreamSubscription<ChannelReadEvent>? _dmThreadSub;
  bool _connected = false;

  Stream<InAppToastPayload> get messages => _messages.stream;

  /// Fires when the server broadcasts `.join.accepted` to this user.
  Stream<InAppToastPayload> get joinAccepted => _joinAccepted.stream;

  /// Fires when the server broadcasts `.join.requested` (organizer).
  Stream<InAppToastPayload> get joinRequested => _joinRequested.stream;

  /// Fires when the server broadcasts `.friend.requested`.
  Stream<InAppToastPayload> get friendRequested => _friendRequested.stream;

  /// Fires when someone reacts to one of your messages (`.chat.reaction`).
  Stream<InAppToastPayload> get messageReacted => _messageReacted.stream;

  /// Live `.message.posted` payloads for the currently subscribed event thread.
  Stream<ChatMessage> get threadPosted => _threadPosted.stream;

  /// Live `.message.reacted` count updates for the subscribed thread.
  Stream<ChatMessageReaction> get threadReacted => _threadReacted.stream;

  /// User channel `.dm.message` toast payloads.
  Stream<InAppToastPayload> get dmMessages => _dmMessages.stream;

  /// Live `.message.posted` on `private-dms.{id}`.
  Stream<DmMessage> get dmThreadPosted => _dmThreadPosted.stream;

  bool get isConnected => _connected;

  int? get userId => _userId;

  int? get subscribedEventId => _subscribedEventId;

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
    _authToken = token;
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
      _userChannel = channel;

      _connectionSub = client.onConnectionEstablished.listen((_) {
        _connected = true;
        channel.subscribeIfNotUnsubscribed();
        final eventId = _subscribedEventId;
        if (eventId != null) {
          unawaited(_attachEventChannel(eventId));
        }
        final dmId = _subscribedDmId;
        if (dmId != null) {
          unawaited(_attachDmChannel(dmId));
        }
      });

      _eventSub = channel.bind('chat.message').listen((event) {
        final data = event.tryGetDataAsMap();
        if (data == null) return;
        final payload = InAppToastPayload.fromChatJson(data);
        if (payload.eventId <= 0) return;
        if (!_messages.isClosed) {
          _messages.add(payload);
        }
      });

      _joinSub = channel.bind('join.accepted').listen((event) {
        final data = event.tryGetDataAsMap() ?? const <String, dynamic>{};
        final payload = InAppToastPayload.fromJoinAcceptedJson(data);
        if (payload.eventId <= 0) return;
        if (!_joinAccepted.isClosed) {
          _joinAccepted.add(payload);
        }
      });

      _joinRequestedSub = channel.bind('join.requested').listen((event) {
        final data = event.tryGetDataAsMap() ?? const <String, dynamic>{};
        final payload = InAppToastPayload.fromJoinRequestedJson(data);
        if (payload.eventId <= 0) return;
        if (!_joinRequested.isClosed) {
          _joinRequested.add(payload);
        }
      });

      _friendRequestedSub = channel.bind('friend.requested').listen((event) {
        final data = event.tryGetDataAsMap() ?? const <String, dynamic>{};
        final payload = InAppToastPayload.fromFriendRequestedJson(data);
        if (!_friendRequested.isClosed) {
          _friendRequested.add(payload);
        }
      });

      _messageReactedSub = channel.bind('chat.reaction').listen((event) {
        final data = event.tryGetDataAsMap() ?? const <String, dynamic>{};
        final payload = InAppToastPayload.fromMessageReactedJson(data);
        if (payload.eventId <= 0) return;
        if (!_messageReacted.isClosed) {
          _messageReacted.add(payload);
        }
      });

      _dmToastSub = channel.bind('dm.message').listen((event) {
        final data = event.tryGetDataAsMap() ?? const <String, dynamic>{};
        final payload = InAppToastPayload.fromDmJson(data);
        if (payload.conversationId <= 0) return;
        if (!_dmMessages.isClosed) {
          _dmMessages.add(payload);
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

  /// Join `private-events.{id}.chat` for live thread appends (web Echo parity).
  Future<void> subscribeEventChat(int eventId) async {
    if (_subscribedEventId == eventId && _eventChannel != null) {
      return;
    }
    await unsubscribeEventChat();
    _subscribedEventId = eventId;
    if (_client == null || !_connected) {
      return;
    }
    await _attachEventChannel(eventId);
  }

  /// Join `private-dms.{id}` for live DM thread appends.
  Future<void> subscribeDm(int conversationId) async {
    if (_subscribedDmId == conversationId && _dmChannel != null) {
      return;
    }
    await unsubscribeDm();
    _subscribedDmId = conversationId;
    if (_client == null || !_connected) {
      return;
    }
    await _attachDmChannel(conversationId);
  }

  Future<void> unsubscribeDm() async {
    await _dmThreadSub?.cancel();
    _dmThreadSub = null;
    try {
      _dmChannel?.unsubscribe();
    } catch (_) {}
    _dmChannel = null;
    _subscribedDmId = null;
  }

  Future<void> _attachDmChannel(int conversationId) async {
    final client = _client;
    final token = _authToken;
    if (client == null || token == null || token.isEmpty) return;

    await _dmThreadSub?.cancel();
    _dmThreadSub = null;
    try {
      _dmChannel?.unsubscribe();
    } catch (_) {}
    _dmChannel = null;

    final authUri = Uri.parse('${Env.apiBaseUrl}/broadcasting/auth');
    final channel = client.privateChannel(
      'private-dms.$conversationId',
      authorizationDelegate:
          EndpointAuthorizableChannelTokenAuthorizationDelegate.forPrivateChannel(
            authorizationEndpoint: authUri,
            headers: {
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
          ),
    );
    _dmChannel = channel;
    channel.subscribeIfNotUnsubscribed();

    _dmThreadSub = channel.bind('message.posted').listen((event) {
      final data = event.tryGetDataAsMap();
      if (data == null) return;
      try {
        final message = DmMessage.fromJson(Map<String, dynamic>.from(data));
        if (message.id <= 0) return;
        if (!_dmThreadPosted.isClosed) {
          _dmThreadPosted.add(message);
        }
      } catch (e) {
        if (kDebugMode) {
          debugPrint('ChatRealtime DM parse failed: $e');
        }
      }
    });
  }

  Future<void> unsubscribeEventChat() async {
    await _threadSub?.cancel();
    _threadSub = null;
    await _threadReactSub?.cancel();
    _threadReactSub = null;
    try {
      _eventChannel?.unsubscribe();
    } catch (_) {}
    _eventChannel = null;
    _subscribedEventId = null;
  }

  Future<void> _attachEventChannel(int eventId) async {
    final client = _client;
    final token = _authToken;
    if (client == null || token == null || token.isEmpty) return;

    await _threadSub?.cancel();
    _threadSub = null;
    await _threadReactSub?.cancel();
    _threadReactSub = null;
    try {
      _eventChannel?.unsubscribe();
    } catch (_) {}
    _eventChannel = null;

    final authUri = Uri.parse('${Env.apiBaseUrl}/broadcasting/auth');
    final channel = client.privateChannel(
      'private-events.$eventId.chat',
      authorizationDelegate:
          EndpointAuthorizableChannelTokenAuthorizationDelegate.forPrivateChannel(
            authorizationEndpoint: authUri,
            headers: {
              'Accept': 'application/json',
              'Authorization': 'Bearer $token',
            },
          ),
    );
    _eventChannel = channel;
    channel.subscribeIfNotUnsubscribed();

    _threadSub = channel.bind('message.posted').listen((event) {
      final data = event.tryGetDataAsMap();
      if (data == null) return;
      try {
        final message = ChatMessage.fromJson(Map<String, dynamic>.from(data));
        if (message.id <= 0) return;
        if (!_threadPosted.isClosed) {
          _threadPosted.add(message);
        }
      } catch (e) {
        if (kDebugMode) {
          debugPrint('ChatRealtime thread parse failed: $e');
        }
      }
    });

    _threadReactSub = channel.bind('message.reacted').listen((event) {
      final data = event.tryGetDataAsMap();
      if (data == null) return;
      try {
        final reaction = ChatMessageReaction.fromJson(
          Map<String, dynamic>.from(data),
        );
        if (reaction.messageId <= 0) return;
        if (!_threadReacted.isClosed) {
          _threadReacted.add(reaction);
        }
      } catch (e) {
        if (kDebugMode) {
          debugPrint('ChatRealtime reaction parse failed: $e');
        }
      }
    });
  }

  Future<void> disconnect() async {
    _connected = false;
    await unsubscribeDm();
    await unsubscribeEventChat();
    await _eventSub?.cancel();
    _eventSub = null;
    await _joinSub?.cancel();
    _joinSub = null;
    await _joinRequestedSub?.cancel();
    _joinRequestedSub = null;
    await _friendRequestedSub?.cancel();
    _friendRequestedSub = null;
    await _messageReactedSub?.cancel();
    _messageReactedSub = null;
    await _dmToastSub?.cancel();
    _dmToastSub = null;
    await _connectionSub?.cancel();
    _connectionSub = null;
    try {
      _userChannel?.unsubscribe();
    } catch (_) {}
    _userChannel = null;
    try {
      _client?.dispose();
    } catch (_) {}
    _client = null;
    _userId = null;
    _authToken = null;
  }

  Future<void> dispose() async {
    await disconnect();
    await _messages.close();
    await _joinAccepted.close();
    await _joinRequested.close();
    await _friendRequested.close();
    await _messageReacted.close();
    await _threadPosted.close();
    await _threadReacted.close();
    await _dmMessages.close();
    await _dmThreadPosted.close();
  }
}

class ChatMessageReaction {
  const ChatMessageReaction({
    required this.messageId,
    required this.eventId,
    required this.actorId,
    required this.likesCount,
    required this.dislikesCount,
    this.myReaction,
  });

  final int messageId;
  final int eventId;
  final int actorId;
  final int likesCount;
  final int dislikesCount;
  final String? myReaction;

  factory ChatMessageReaction.fromJson(Map<String, dynamic> json) {
    return ChatMessageReaction(
      messageId: (json['id'] as num?)?.toInt() ?? 0,
      eventId: (json['event_id'] as num?)?.toInt() ?? 0,
      actorId: (json['actor_id'] as num?)?.toInt() ?? 0,
      likesCount: (json['likes_count'] as num?)?.toInt() ?? 0,
      dislikesCount: (json['dislikes_count'] as num?)?.toInt() ?? 0,
      myReaction: json['my_reaction'] as String?,
    );
  }
}

final chatRealtimeClientProvider = Provider<ChatRealtimeClient>((ref) {
  final client = ChatRealtimeClient(
    tokenStorage: ref.watch(tokenStorageProvider),
  );
  ref.onDispose(() {
    unawaited(client.dispose());
  });
  return client;
});
