import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chat_realtime_client.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/features/chat/presentation/widgets/chat_toast_banner.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Ambient unread badge for shell topbar / sidebar (updated by [ChatToastListener]).
final chatUnreadTotalProvider = StateProvider<int>((ref) => 0);

final chatsListProvider =
    FutureProvider.autoDispose<({List<ChatThread> data, int unreadTotal})>((ref) async {
  final result = await ref.watch(chatsRepositoryProvider).list();
  if (result.failure != null) {
    throw result.failure!;
  }
  return (data: result.data, unreadTotal: result.unreadTotal);
});

class ChatsPage extends ConsumerWidget {
  const ChatsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final async = ref.watch(chatsListProvider);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.chatsTitle)),
      body: async.when(
        loading: () => const Center(child: FunLoader()),
        error: (error, _) => Center(child: Text(error.toString())),
        data: (payload) {
          if (payload.data.isEmpty) {
            return Center(
              child: Padding(
                padding: const EdgeInsets.all(24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      l10n.chatsEmptyTitle,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      l10n.chatsEmptyBody,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: AppColors.muted),
                    ),
                  ],
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(chatsListProvider),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: payload.data.length,
              separatorBuilder: (_, _) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final chat = payload.data[index];
                final last = chat.lastMessage;
                final preview = last == null
                    ? l10n.chatsNoMessages
                    : '${last.isMine ? l10n.chatsYou : (last.userName ?? '')}: ${last.body}';
                final unread = chat.unreadCount > 0;

                return Material(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(18),
                  child: InkWell(
                    borderRadius: BorderRadius.circular(18),
                    onTap: () => context.push('/events/${chat.eventId}/chat'),
                    child: Padding(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        children: [
                          CircleAvatar(
                            backgroundColor: unread
                                ? AppColors.primary
                                : AppColors.primary.withValues(alpha: 0.12),
                            foregroundColor:
                                unread ? Colors.white : AppColors.primary,
                            child: Text(
                              chat.title.isNotEmpty
                                  ? chat.title.characters.first.toUpperCase()
                                  : '?',
                              style: const TextStyle(fontWeight: FontWeight.w800),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  chat.title,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontWeight: unread
                                        ? FontWeight.w800
                                        : FontWeight.w700,
                                    fontSize: 15,
                                  ),
                                ),
                                if (chat.city != null) ...[
                                  const SizedBox(height: 2),
                                  Text(
                                    chat.city!,
                                    style: const TextStyle(
                                      color: AppColors.muted,
                                      fontSize: 11,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 4),
                                Text(
                                  preview,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    color: unread
                                        ? AppColors.text
                                        : AppColors.muted,
                                    fontWeight: unread
                                        ? FontWeight.w600
                                        : FontWeight.w400,
                                    fontSize: 13,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          if (unread)
                            Container(
                              margin: const EdgeInsetsDirectional.only(start: 8),
                              padding: const EdgeInsets.symmetric(
                                horizontal: 7,
                                vertical: 3,
                              ),
                              decoration: BoxDecoration(
                                color: AppColors.primary,
                                borderRadius: BorderRadius.circular(999),
                              ),
                              child: Text(
                                chat.unreadCount > 99
                                    ? '99+'
                                    : '${chat.unreadCount}',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                ),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}

/// Polls chat inbox while browsing and shows a SnackBar for new messages.
/// Shell wrapper: Reverb live toasts + inbox poll fallback / unread sync.
class ChatToastListener extends ConsumerStatefulWidget {
  const ChatToastListener({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<ChatToastListener> createState() => _ChatToastListenerState();
}

class _ChatToastListenerState extends ConsumerState<ChatToastListener> {
  Timer? _timer;
  final Map<int, int> _lastSeenMessageIds = {};
  bool _primed = false;
  bool _inFlight = false;
  bool _usePollToasts = true;

  ChatRealtimeClient? _realtime;
  StreamSubscription<ChatToastPayload>? _realtimeSub;
  final List<ChatToastPayload> _toasts = [];
  final Set<int> _shownMessageIds = {};

  @override
  void initState() {
    super.initState();
    _schedulePoll(const Duration(seconds: 15));
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _poll();
      _syncRealtime();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    unawaited(_teardownRealtime());
    super.dispose();
  }

  void _schedulePoll(Duration interval) {
    _timer?.cancel();
    _timer = Timer.periodic(interval, (_) => _poll());
  }

  Future<void> _teardownRealtime() async {
    await _realtimeSub?.cancel();
    _realtimeSub = null;
    await _realtime?.dispose();
    _realtime = null;
    _usePollToasts = true;
  }

  Future<void> _syncRealtime() async {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      await _teardownRealtime();
      _schedulePoll(const Duration(seconds: 15));
      return;
    }

    final branding = await ref.read(siteBrandingProvider.future);
    final config = branding.broadcast;
    if (!config.enabled) {
      await _teardownRealtime();
      _schedulePoll(const Duration(seconds: 15));
      return;
    }

    if (_realtime?.userId == auth.user.id && _realtime?.isConnected == true) {
      return;
    }

    await _teardownRealtime();
    final client = ChatRealtimeClient(
      tokenStorage: ref.read(tokenStorageProvider),
    );
    _realtime = client;
    _realtimeSub = client.messages.listen(_onRealtimeMessage);

    final started = await client.connect(userId: auth.user.id, config: config);
    if (!mounted) return;

    if (!started) {
      _usePollToasts = true;
      _schedulePoll(const Duration(seconds: 15));
      return;
    }

    // Keep poll toasts until the socket is live, then slow down.
    _usePollToasts = true;
    _schedulePoll(const Duration(seconds: 15));
    Future<void>.delayed(const Duration(seconds: 8), () {
      if (!mounted) return;
      if (_realtime?.isConnected == true) {
        _usePollToasts = false;
        _schedulePoll(const Duration(seconds: 60));
      }
    });
  }

  void _onRealtimeMessage(
    ChatToastPayload payload, {
    bool bumpUnread = true,
  }) {
    if (!mounted) return;

    if (payload.messageId > 0) {
      if (_shownMessageIds.contains(payload.messageId)) return;
      _shownMessageIds.add(payload.messageId);
      _lastSeenMessageIds[payload.eventId] = payload.messageId;
    }

    final location = GoRouterState.of(context).uri.path;
    if (location == '/events/${payload.eventId}/chat') {
      return;
    }

    if (bumpUnread) {
      ref.read(chatUnreadTotalProvider.notifier).update((n) => n + 1);
    }

    setState(() {
      _toasts.insert(0, payload);
      if (_toasts.length > 3) {
        _toasts.removeRange(3, _toasts.length);
      }
    });

    Future<void>.delayed(const Duration(seconds: 7), () {
      if (!mounted) return;
      _dismissToast(payload);
    });
  }

  void _dismissToast(ChatToastPayload payload) {
    setState(() => _toasts.removeWhere((t) => identical(t, payload) ||
        (t.messageId != 0 && t.messageId == payload.messageId)));
  }

  Future<void> _poll() async {
    if (_inFlight) return;
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      _primed = false;
      _lastSeenMessageIds.clear();
      _shownMessageIds.clear();
      ref.read(chatUnreadTotalProvider.notifier).state = 0;
      return;
    }

    _inFlight = true;
    try {
      final result = await ref.read(chatsRepositoryProvider).list();
      if (!mounted || result.failure != null) {
        return;
      }

      ref.read(chatUnreadTotalProvider.notifier).state = result.unreadTotal;

      if (!_usePollToasts) {
        for (final chat in result.data) {
          final lastId = chat.lastMessage?.id ?? 0;
          if (lastId > 0) {
            _lastSeenMessageIds[chat.eventId] = lastId;
          }
        }
        _primed = true;
        return;
      }

      final location = GoRouterState.of(context).uri.path;

      for (final chat in result.data) {
        final lastId = chat.lastMessage?.id ?? 0;
        final previous = _lastSeenMessageIds[chat.eventId] ?? 0;
        _lastSeenMessageIds[chat.eventId] = lastId;

        if (!_primed || lastId <= previous || lastId == 0) {
          continue;
        }
        if (chat.lastMessage?.isMine == true) {
          continue;
        }
        if (location == '/events/${chat.eventId}/chat') {
          continue;
        }

        final last = chat.lastMessage;
        _onRealtimeMessage(
          ChatToastPayload(
            messageId: lastId,
            eventId: chat.eventId,
            eventTitle: chat.title,
            senderName: last?.userName ?? '',
            senderInitials: (last?.userName?.trim().isNotEmpty == true)
                ? last!.userName!.trim()[0].toUpperCase()
                : '?',
          ),
          bumpUnread: false,
        );
      }

      _primed = true;
    } finally {
      _inFlight = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen<AuthState>(authControllerProvider, (prev, next) {
      unawaited(_syncRealtime());
    });

    final bottomInset = MediaQuery.paddingOf(context).bottom;
    final wide = MediaQuery.sizeOf(context).width >= 1024;

    return Stack(
      children: [
        widget.child,
        if (_toasts.isNotEmpty)
          Positioned(
            right: 16,
            left: 16,
            bottom: wide ? 24 + bottomInset : 88 + bottomInset,
            child: Align(
              alignment: AlignmentDirectional.bottomEnd,
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 320),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    for (final toast in _toasts) ...[
                      ChatToastBanner(
                        payload: toast,
                        onDismiss: () => _dismissToast(toast),
                        onOpen: () {
                          _dismissToast(toast);
                          context.push('/events/${toast.eventId}/chat');
                        },
                      ),
                      const SizedBox(height: 12),
                    ],
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }
}
