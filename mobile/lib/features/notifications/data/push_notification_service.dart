import 'dart:async';
import 'dart:io';

import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/features/notifications/presentation/notifications_page.dart';

/// Background isolate entry (required by firebase_messaging).
@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  try {
    await Firebase.initializeApp();
  } catch (_) {}
  // Notification-payload messages are shown by the OS when backgrounded.
}

/// Registers FCM tokens with the API and keeps them in sync with auth.
///
/// When the app is **open (foreground)**: no system tray banner — sweet
/// in-app toast (same as web) + badge. When backgrounded/killed: OS / FCM
/// shows the tray notification (with `ic_stat_funity` branding on Android).
class PushNotificationService {
  PushNotificationService(this._ref);

  final Ref _ref;
  bool _ready = false;
  String? _token;
  StreamSubscription<String>? _tokenRefreshSub;
  StreamSubscription<RemoteMessage>? _foregroundSub;
  final _toastBus = StreamController<InAppToastPayload>.broadcast();

  bool get isReady => _ready;

  /// Foreground FCM → in-app toast stream (deduped by [ChatToastListener]).
  Stream<InAppToastPayload> get foregroundToasts => _toastBus.stream;

  Future<void> initialize() async {
    if (kIsWeb) return;

    try {
      await Firebase.initializeApp();
      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);
      _ready = true;
    } catch (e, st) {
      _ready = false;
      if (kDebugMode) {
        debugPrint(
          'PushNotificationService: Firebase not configured ($e). '
          'Add android/app/google-services.json (see mobile/docs/FIREBASE_PUSH.md).\n$st',
        );
      }
      return;
    }

    // App open → suppress OS banners; in-app toast + badge instead.
    if (Platform.isIOS) {
      await FirebaseMessaging.instance
          .setForegroundNotificationPresentationOptions(
            alert: false,
            badge: true,
            sound: false,
          );
    }

    _foregroundSub = FirebaseMessaging.onMessage.listen(_onForegroundMessage);
  }

  void _onForegroundMessage(RemoteMessage message) {
    // Do NOT show a system notification while the app is open.
    final data = Map<String, dynamic>.from(message.data);
    final notification = message.notification;
    if (notification != null) {
      data.putIfAbsent('title', () => notification.title ?? '');
      data.putIfAbsent('body', () => notification.body ?? '');
    }

    final toast = InAppToastPayload.tryFromFcmData(data);
    if (toast != null && !_toastBus.isClosed) {
      // Sweet in-app toast + badge via ChatToastListener (deduped vs Pusher).
      _toastBus.add(toast);
      return;
    }

    // Unknown payload — still bump the notify badge.
    _ref.read(notificationUnreadTotalProvider.notifier).update((n) => n + 1);
  }

  Future<void> syncForSession() async {
    if (!_ready) return;

    final auth = _ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      await clear();
      return;
    }

    final messaging = FirebaseMessaging.instance;
    await messaging.requestPermission(alert: true, badge: true, sound: true);

    final token = await messaging.getToken();
    if (token == null || token.isEmpty) return;

    await _register(token);

    await _tokenRefreshSub?.cancel();
    _tokenRefreshSub = messaging.onTokenRefresh.listen(_register);
  }

  Future<void> _register(String token) async {
    _token = token;
    final platform = Platform.isIOS ? 'ios' : 'android';
    await _ref
        .read(notificationsRepositoryProvider)
        .registerDeviceToken(token: token, platform: platform);
    if (kDebugMode) {
      debugPrint('PushNotificationService: registered FCM token ($platform)');
    }
  }

  Future<void> clear() async {
    final token = _token;
    _token = null;
    await _tokenRefreshSub?.cancel();
    _tokenRefreshSub = null;

    if (token == null || token.isEmpty) return;
    try {
      await _ref.read(notificationsRepositoryProvider).forgetDeviceToken(token);
    } catch (_) {}
  }

  Future<void> dispose() async {
    await _foregroundSub?.cancel();
    await _tokenRefreshSub?.cancel();
    await _toastBus.close();
  }
}

final pushNotificationServiceProvider = Provider<PushNotificationService>((
  ref,
) {
  final service = PushNotificationService(ref);
  ref.onDispose(() {
    unawaited(service.dispose());
  });
  return service;
});

/// Keeps FCM registration aligned with [authControllerProvider].
class PushNotificationBinder extends ConsumerStatefulWidget {
  const PushNotificationBinder({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<PushNotificationBinder> createState() =>
      _PushNotificationBinderState();
}

class _PushNotificationBinderState
    extends ConsumerState<PushNotificationBinder> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      await ref.read(pushNotificationServiceProvider).initialize();
      await ref.read(pushNotificationServiceProvider).syncForSession();
    });
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<AuthState>(authControllerProvider, (prev, next) {
      unawaited(ref.read(pushNotificationServiceProvider).syncForSession());
    });
    return widget.child;
  }
}
