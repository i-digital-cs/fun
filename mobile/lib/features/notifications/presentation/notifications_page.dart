import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/notifications/domain/app_notification.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

/// Amber unread dot for shell topbar / sidebar.
final notificationUnreadTotalProvider = StateProvider<int>((ref) => 0);

final notificationsListProvider =
    FutureProvider.autoDispose<
      ({List<AppNotification> data, int unreadCount})
    >((ref) async {
      final result = await ref
          .watch(notificationsRepositoryProvider)
          .list(perPage: 40);
      if (result.failure != null) {
        throw result.failure!;
      }
      ref.read(notificationUnreadTotalProvider.notifier).state =
          result.unreadCount;
      return (data: result.data, unreadCount: result.unreadCount);
    });

class NotificationsPage extends ConsumerWidget {
  const NotificationsPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final async = ref.watch(notificationsListProvider);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.notificationsTitle),
        actions: [
          async.maybeWhen(
            data: (payload) => payload.unreadCount > 0
                ? TextButton(
                    onPressed: () async {
                      await ref
                          .read(notificationsRepositoryProvider)
                          .markAllRead();
                      ref.invalidate(notificationsListProvider);
                      ref.read(notificationUnreadTotalProvider.notifier).state =
                          0;
                    },
                    child: Text(l10n.notificationsMarkAllRead),
                  )
                : const SizedBox.shrink(),
            orElse: () => const SizedBox.shrink(),
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
                      l10n.notificationsEmptyTitle,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      l10n.notificationsEmptyBody,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: AppColors.muted),
                    ),
                  ],
                ),
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(notificationsListProvider),
            child: ListView.separated(
              padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
              itemCount: payload.data.length,
              separatorBuilder: (_, _) => const SizedBox(height: 8),
              itemBuilder: (context, index) {
                final item = payload.data[index];
                return _NotificationTile(item: item);
              },
            ),
          );
        },
      ),
    );
  }
}

class _NotificationTile extends ConsumerWidget {
  const _NotificationTile({required this.item});

  final AppNotification item;

  Future<void> _open(BuildContext context, WidgetRef ref) async {
    if (item.isUnread) {
      await ref.read(notificationsRepositoryProvider).markRead(item.id);
      final current = ref.read(notificationUnreadTotalProvider);
      if (current > 0) {
        ref.read(notificationUnreadTotalProvider.notifier).state = current - 1;
      }
      ref.invalidate(notificationsListProvider);
    }

    if (!context.mounted) return;

    if (item.type == 'join_request' && item.eventId != null) {
      context.push('/events/${item.eventId}/requests');
      return;
    }
    if (item.eventId != null) {
      context.push('/events/${item.eventId}');
      return;
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final locale = Localizations.localeOf(context).toString();
    final time = item.createdAt != null
        ? DateFormat.MMMd(locale).add_jm().format(item.createdAt!.toLocal())
        : '';
    final unread = item.isUnread;

    return Material(
      color: unread ? const Color(0xFFEEF2FF) : Colors.white,
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        borderRadius: BorderRadius.circular(18),
        onTap: () => _open(context, ref),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 14, 14, 14),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: unread ? AppColors.primary : const Color(0xFFF1F5F9),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: Icon(
                  item.type == 'join_accepted'
                      ? Icons.check_circle_outline
                      : Icons.notifications_outlined,
                  size: 22,
                  color: unread ? Colors.white : const Color(0xFF64748B),
                ),
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
                            item.title,
                            style: TextStyle(
                              fontWeight: unread
                                  ? FontWeight.w800
                                  : FontWeight.w700,
                              fontSize: 14,
                              color: AppColors.text,
                            ),
                          ),
                        ),
                        if (time.isNotEmpty)
                          Text(
                            time,
                            style: const TextStyle(
                              fontSize: 11,
                              color: AppColors.muted,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      item.body,
                      style: TextStyle(
                        fontSize: 13,
                        height: 1.35,
                        fontWeight: unread
                            ? FontWeight.w600
                            : FontWeight.w400,
                        color: unread
                            ? const Color(0xFF334155)
                            : AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              if (unread) ...[
                const SizedBox(width: 8),
                Container(
                  width: 8,
                  height: 8,
                  margin: const EdgeInsets.only(top: 6),
                  decoration: const BoxDecoration(
                    color: Color(0xFFF97316),
                    shape: BoxShape.circle,
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

/// Polls unread notification count while the shell is open.
class NotificationUnreadListener extends ConsumerStatefulWidget {
  const NotificationUnreadListener({super.key, required this.child});

  final Widget child;

  @override
  ConsumerState<NotificationUnreadListener> createState() =>
      _NotificationUnreadListenerState();
}

class _NotificationUnreadListenerState
    extends ConsumerState<NotificationUnreadListener> {
  Timer? _timer;
  bool _inFlight = false;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 30), (_) => _poll());
    WidgetsBinding.instance.addPostFrameCallback((_) => _poll());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _poll() async {
    if (_inFlight) return;
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      ref.read(notificationUnreadTotalProvider.notifier).state = 0;
      return;
    }

    _inFlight = true;
    try {
      final result = await ref
          .read(notificationsRepositoryProvider)
          .unreadCount();
      if (!mounted) return;
      if (result.failure == null) {
        ref.read(notificationUnreadTotalProvider.notifier).state =
            result.unreadCount;
      }
    } finally {
      _inFlight = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(authControllerProvider, (prev, next) {
      if (next is Authenticated) {
        _poll();
      } else {
        ref.read(notificationUnreadTotalProvider.notifier).state = 0;
      }
    });
    return widget.child;
  }
}
