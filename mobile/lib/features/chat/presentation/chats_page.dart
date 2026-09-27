import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chat_realtime_client.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/features/chat/domain/chat_toast_payload.dart';
import 'package:fun_mobile/features/chat/presentation/widgets/chat_toast_banner.dart';
import 'package:fun_mobile/features/notifications/data/push_notification_service.dart';
import 'package:fun_mobile/features/notifications/presentation/notifications_page.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Ambient unread badge for shell topbar / sidebar (updated by [ChatToastListener]).
final chatUnreadTotalProvider = StateProvider<int>((ref) => 0);

final chatsListProvider =
    FutureProvider.autoDispose<({List<ChatThread> data, int unreadTotal})>((
      ref,
    ) async {
      final result = await ref.watch(chatsRepositoryProvider).list();
      if (result.failure != null) {
        throw result.failure!;
      }
      return (data: result.data, unreadTotal: result.unreadTotal);
    });

enum _ChatFilter { recent, unread }

class ChatsPage extends ConsumerStatefulWidget {
  const ChatsPage({super.key});

  @override
  ConsumerState<ChatsPage> createState() => _ChatsPageState();
}

class _ChatsPageState extends ConsumerState<ChatsPage> {
  final _search = TextEditingController();
  _ChatFilter _filter = _ChatFilter.recent;
  bool _showSearch = false;

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  List<ChatThread> _filtered(List<ChatThread> all) {
    var list = all;
    if (_filter == _ChatFilter.unread) {
      list = list.where((c) => c.unreadCount > 0).toList();
    }
    final q = _search.text.trim().toLowerCase();
    if (q.isEmpty) return list;
    return list
        .where((c) {
          final hay = [
            c.title,
            c.city ?? '',
            c.lastMessage?.body ?? '',
            c.lastMessage?.userName ?? '',
          ].join(' ').toLowerCase();
          return hay.contains(q);
        })
        .toList();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final async = ref.watch(chatsListProvider);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        centerTitle: false,
        title: Text(l10n.chatsTitle),
        actions: [
          IconButton(
            tooltip: l10n.search,
            icon: Icon(_showSearch ? Icons.close : Icons.search),
            onPressed: () {
              setState(() {
                _showSearch = !_showSearch;
                if (!_showSearch) _search.clear();
              });
            },
          ),
        ],
      ),
      body: async.when(
        loading: () => const Center(child: FunLoader()),
        error: (error, _) => Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              error is Failure
                  ? failureUserMessage(error)
                  : l10n.failedToLoadEvents,
              textAlign: TextAlign.center,
            ),
          ),
        ),
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

          final rows = _filtered(payload.data);
          final unreadTotal = payload.data
              .where((c) => c.unreadCount > 0)
              .fold<int>(0, (sum, c) => sum + c.unreadCount);

          return Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
                child: Column(
                  children: [
                    _ChatSegmentBar(
                      recentLabel: l10n.chatsFilterRecent,
                      unreadLabel: l10n.chatsFilterUnread,
                      unreadCount: unreadTotal,
                      selected: _filter,
                      onChanged: (next) => setState(() => _filter = next),
                    ),
                    if (_showSearch) ...[
                      const SizedBox(height: 12),
                      TextField(
                        controller: _search,
                        onChanged: (_) => setState(() {}),
                        autofocus: true,
                        textInputAction: TextInputAction.search,
                        decoration: InputDecoration(
                          hintText: l10n.searchChatsHint,
                          prefixIcon: const Icon(Icons.search_rounded),
                          suffixIcon: _search.text.isEmpty
                              ? null
                              : IconButton(
                                  tooltip: l10n.search,
                                  onPressed: () {
                                    _search.clear();
                                    setState(() {});
                                  },
                                  icon: const Icon(Icons.close_rounded),
                                ),
                          filled: true,
                          fillColor: AppColors.surface,
                          contentPadding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 12,
                          ),
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(
                              color: AppColors.borderSoft,
                            ),
                          ),
                          enabledBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(
                              color: AppColors.borderSoft,
                            ),
                          ),
                          focusedBorder: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(14),
                            borderSide: const BorderSide(
                              color: AppColors.primary,
                              width: 1.5,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 8),
              Expanded(
                child: rows.isEmpty
                    ? Center(
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Text(
                            _filter == _ChatFilter.unread &&
                                    _search.text.trim().isEmpty
                                ? l10n.chatsNoUnread
                                : l10n.chatsNoResults,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppColors.muted),
                          ),
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () async =>
                            ref.invalidate(chatsListProvider),
                        child: ListView.separated(
                          padding: const EdgeInsets.fromLTRB(12, 4, 12, 24),
                          itemCount: rows.length,
                          separatorBuilder: (_, _) => const SizedBox(height: 6),
                          itemBuilder: (context, index) {
                            final chat = rows[index];
                            return _ChatCard(
                              chat: chat,
                              onTap: () => context.push(
                                '/events/${chat.eventId}/chat',
                              ),
                            );
                          },
                        ),
                      ),
              ),
            ],
          );
        },
      ),
    );
  }
}

class _ChatSegmentBar extends StatelessWidget {
  const _ChatSegmentBar({
    required this.recentLabel,
    required this.unreadLabel,
    required this.unreadCount,
    required this.selected,
    required this.onChanged,
  });

  final String recentLabel;
  final String unreadLabel;
  final int unreadCount;
  final _ChatFilter selected;
  final ValueChanged<_ChatFilter> onChanged;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(4),
      decoration: BoxDecoration(
        color: AppColors.primarySoft.withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.borderSoft),
      ),
      child: Row(
        children: [
          Expanded(
            child: _SegmentTab(
              label: recentLabel,
              selected: selected == _ChatFilter.recent,
              onTap: () => onChanged(_ChatFilter.recent),
            ),
          ),
          Expanded(
            child: _SegmentTab(
              label: unreadLabel,
              selected: selected == _ChatFilter.unread,
              badge: unreadCount > 0 ? unreadCount : null,
              onTap: () => onChanged(_ChatFilter.unread),
            ),
          ),
        ],
      ),
    );
  }
}

class _SegmentTab extends StatelessWidget {
  const _SegmentTab({
    required this.label,
    required this.selected,
    required this.onTap,
    this.badge,
  });

  final String label;
  final bool selected;
  final VoidCallback onTap;
  final int? badge;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: selected ? AppColors.surface : Colors.transparent,
      elevation: selected ? 1 : 0,
      shadowColor: AppColors.primary.withValues(alpha: 0.18),
      borderRadius: BorderRadius.circular(11),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(11),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 11),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                  color: selected ? AppColors.primaryInk : AppColors.muted,
                ),
              ),
              if (badge != null) ...[
                const SizedBox(width: 6),
                Container(
                  constraints: const BoxConstraints(minWidth: 20),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 6,
                    vertical: 2,
                  ),
                  decoration: BoxDecoration(
                    color: selected
                        ? AppColors.primary
                        : AppColors.primary.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(999),
                  ),
                  child: Text(
                    badge! > 99 ? '99+' : '$badge',
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w800,
                      color: selected ? Colors.white : AppColors.primary,
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _ChatCard extends StatelessWidget {
  const _ChatCard({required this.chat, required this.onTap});

  final ChatThread chat;
  final VoidCallback onTap;

  String _relativeTime(BuildContext context, DateTime? at) {
    if (at == null) return '';
    final local = at.toLocal();
    final now = DateTime.now();
    final diff = now.difference(local);
    if (diff.inMinutes < 1) return 'now';
    if (diff.inHours < 1) return '${diff.inMinutes}m';
    if (diff.inDays < 1) return '${diff.inHours}h';
    if (diff.inDays < 7) return '${diff.inDays}d';
    final locale = Localizations.localeOf(context).toString();
    return DateFormat.MMMd(locale).format(local);
  }

  String _eventWhen(BuildContext context) {
    final at = chat.startsAt;
    if (at == null) return '';
    final locale = Localizations.localeOf(context).toString();
    return DateFormat.MMMd(locale).add_jm().format(at.toLocal());
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final last = chat.lastMessage;
    final unread = chat.unreadCount > 0;
    final preview = last == null
        ? l10n.chatsNoMessages
        : '${last.isMine ? l10n.chatsYou : (last.userName ?? '')}: ${last.body}';
    final messageTime = _relativeTime(context, last?.createdAtDate);
    final eventWhen = _eventWhen(context);
    final hasImage = chat.imageUrl != null && chat.imageUrl!.isNotEmpty;

    return Material(
      color: unread
          ? AppColors.primarySoft.withValues(alpha: 0.35)
          : AppColors.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          child: Row(
            children: [
              _EventAvatar(
                title: chat.title,
                imageUrl: hasImage ? chat.imageUrl : null,
                unread: unread,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            chat.title,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            textDirection: bidiTextDirection(chat.title),
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight:
                                  unread ? FontWeight.w800 : FontWeight.w600,
                              color: AppColors.text,
                            ),
                          ),
                        ),
                        if (messageTime.isNotEmpty)
                          Text(
                            messageTime,
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              color: unread
                                  ? AppColors.primary
                                  : AppColors.muted,
                            ),
                          ),
                      ],
                    ),
                    if (eventWhen.isNotEmpty || chat.city != null) ...[
                      const SizedBox(height: 3),
                      Text(
                        [
                          if (eventWhen.isNotEmpty) eventWhen,
                          if (chat.city != null) chat.city!,
                        ].join(' · '),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppColors.muted,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                    const SizedBox(height: 5),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            preview,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              fontSize: 13,
                              color:
                                  unread ? AppColors.text : AppColors.muted,
                              fontWeight:
                                  unread ? FontWeight.w600 : FontWeight.w400,
                            ),
                          ),
                        ),
                        if (unread) ...[
                          const SizedBox(width: 8),
                          Container(
                            constraints: const BoxConstraints(minWidth: 22),
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
                              textAlign: TextAlign.center,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _EventAvatar extends StatelessWidget {
  const _EventAvatar({
    required this.title,
    required this.unread,
    this.imageUrl,
  });

  final String title;
  final String? imageUrl;
  final bool unread;

  @override
  Widget build(BuildContext context) {
    final initial = title.trim().isNotEmpty
        ? title.trim().characters.first.toUpperCase()
        : '?';

    return Stack(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(16),
          child: SizedBox(
            width: 52,
            height: 52,
            child: imageUrl != null
                ? AppNetworkImage(
                    url: imageUrl!,
                    logicalWidth: 52,
                    logicalHeight: 52,
                    maxCacheWidth: 160,
                    maxCacheHeight: 160,
                    placeholder: ColoredBox(
                      color: AppColors.primarySoft,
                      child: Center(
                        child: Text(
                          initial,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    ),
                    error: ColoredBox(
                      color: AppColors.primarySoft,
                      child: Center(
                        child: Text(
                          initial,
                          style: const TextStyle(
                            fontWeight: FontWeight.w800,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    ),
                  )
                : ColoredBox(
                    color: unread
                        ? AppColors.primary
                        : AppColors.primarySoft,
                    child: Center(
                      child: Text(
                        initial,
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          color: unread ? Colors.white : AppColors.primary,
                        ),
                      ),
                    ),
                  ),
          ),
        ),
        if (unread)
          Positioned(
            right: 0,
            bottom: 0,
            child: Container(
              width: 14,
              height: 14,
              decoration: BoxDecoration(
                color: AppColors.primary,
                shape: BoxShape.circle,
                border: Border.all(color: AppColors.surface, width: 2.5),
              ),
            ),
          ),
      ],
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
  StreamSubscription<InAppToastPayload>? _realtimeSub;
  StreamSubscription<InAppToastPayload>? _joinAcceptedSub;
  StreamSubscription<InAppToastPayload>? _joinRequestedSub;
  StreamSubscription<InAppToastPayload>? _fcmToastSub;
  final List<InAppToastPayload> _toasts = [];
  final Set<String> _shownToastKeys = {};

  @override
  void initState() {
    super.initState();
    _schedulePoll(const Duration(seconds: 15));
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _poll();
      _syncRealtime();
      _bindFcmToasts();
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    unawaited(_teardownRealtime());
    unawaited(_fcmToastSub?.cancel());
    super.dispose();
  }

  void _schedulePoll(Duration interval) {
    _timer?.cancel();
    _timer = Timer.periodic(interval, (_) => _poll());
  }

  void _bindFcmToasts() {
    final service = ref.read(pushNotificationServiceProvider);
    _fcmToastSub?.cancel();
    _fcmToastSub = service.foregroundToasts.listen((payload) {
      _enqueueToast(
        payload,
        bumpChat: payload.kind == InAppToastKind.chat,
        bumpNotify: payload.kind != InAppToastKind.chat,
      );
    });
  }

  Future<void> _teardownRealtime() async {
    await _realtimeSub?.cancel();
    _realtimeSub = null;
    await _joinAcceptedSub?.cancel();
    _joinAcceptedSub = null;
    await _joinRequestedSub?.cancel();
    _joinRequestedSub = null;
    await _realtime?.disconnect();
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

    final client = ref.read(chatRealtimeClientProvider);
    if (_realtime?.userId == auth.user.id && client.isConnected) {
      _realtime = client;
      return;
    }

    await _realtimeSub?.cancel();
    _realtimeSub = null;
    await _joinAcceptedSub?.cancel();
    _joinAcceptedSub = null;
    await _joinRequestedSub?.cancel();
    _joinRequestedSub = null;

    _realtime = client;
    _realtimeSub = client.messages.listen(
      (payload) => _enqueueToast(payload, bumpChat: true),
    );
    _joinAcceptedSub = client.joinAccepted.listen(
      (payload) => _enqueueToast(payload, bumpNotify: true),
    );
    _joinRequestedSub = client.joinRequested.listen(
      (payload) => _enqueueToast(payload, bumpNotify: true),
    );

    final started = await client.connect(userId: auth.user.id, config: config);
    if (!mounted) return;

    if (!started) {
      _usePollToasts = true;
      _schedulePoll(const Duration(seconds: 15));
      return;
    }

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

  void _enqueueToast(
    InAppToastPayload payload, {
    bool bumpChat = false,
    bool bumpNotify = false,
  }) {
    if (!mounted) return;
    if (payload.eventId <= 0) return;

    if (_shownToastKeys.contains(payload.dedupeKey)) return;
    _shownToastKeys.add(payload.dedupeKey);
    if (_shownToastKeys.length > 80) {
      _shownToastKeys.remove(_shownToastKeys.first);
    }

    if (payload.kind == InAppToastKind.chat) {
      final messageId = int.tryParse(
        payload.dedupeKey.startsWith('chat:')
            ? payload.dedupeKey.substring(5)
            : '',
      );
      if (messageId != null && messageId > 0) {
        _lastSeenMessageIds[payload.eventId] = messageId;
      }

      final location = GoRouterState.of(context).uri.path;
      if (location == '/events/${payload.eventId}/chat') {
        return;
      }
    }

    if (bumpChat) {
      ref.read(chatUnreadTotalProvider.notifier).update((n) => n + 1);
    }
    if (bumpNotify) {
      ref.read(notificationUnreadTotalProvider.notifier).update((n) => n + 1);
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

  void _dismissToast(InAppToastPayload payload) {
    setState(
      () => _toasts.removeWhere(
        (t) => identical(t, payload) || t.dedupeKey == payload.dedupeKey,
      ),
    );
  }

  Future<void> _poll() async {
    if (_inFlight) return;
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      _primed = false;
      _lastSeenMessageIds.clear();
      _shownToastKeys.clear();
      ref.read(chatUnreadTotalProvider.notifier).state = 0;
      ref.read(notificationUnreadTotalProvider.notifier).state = 0;
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
        final name = last?.userName ?? '';
        _enqueueToast(
          InAppToastPayload(
            kind: InAppToastKind.chat,
            dedupeKey: 'chat:$lastId',
            eventId: chat.eventId,
            eventTitle: chat.title,
            title: name,
            senderInitials: name.trim().isNotEmpty
                ? name.trim()[0].toUpperCase()
                : '?',
          ),
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
                          context.push(toast.resolvedOpenPath);
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
