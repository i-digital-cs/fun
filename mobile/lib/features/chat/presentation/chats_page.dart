import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/data/chats_remote_source.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

final chatsRemoteSourceProvider = Provider<ChatsRemoteSource>((ref) {
  return ChatsRemoteSource(ref.watch(apiClientProvider));
});

final chatsListProvider =
    FutureProvider.autoDispose<({List<ChatThread> data, int unreadTotal})>((ref) async {
  final result = await ref.watch(chatsRemoteSourceProvider).list();
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
              separatorBuilder: (_, __) => const SizedBox(height: 8),
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

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 15), (_) => _poll());
    WidgetsBinding.instance.addPostFrameCallback((_) => _poll());
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  Future<void> _poll() async {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      _primed = false;
      _lastSeenMessageIds.clear();
      return;
    }

    final result = await ref.read(chatsRemoteSourceProvider).list();
    if (!mounted || result.failure != null) {
      return;
    }

    final location = GoRouterState.of(context).uri.path;
    final l10n = AppLocalizations.of(context);

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

      final name = chat.lastMessage?.userName ?? '';
      final body = chat.lastMessage?.body ?? '';
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('$name: $body'),
          action: SnackBarAction(
            label: l10n.openChat,
            onPressed: () => context.push('/events/${chat.eventId}/chat'),
          ),
          duration: const Duration(seconds: 5),
        ),
      );
    }

    _primed = true;
  }

  @override
  Widget build(BuildContext context) => widget.child;
}
