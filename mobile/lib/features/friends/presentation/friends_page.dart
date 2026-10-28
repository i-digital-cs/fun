import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

final friendsListProvider = FutureProvider.autoDispose<List<FriendUser>>((
  ref,
) async {
  final result = await ref.watch(friendsRepositoryProvider).listFriends();
  if (result.failure != null) throw result.failure!;
  return result.data;
});

final friendRequestsProvider =
    FutureProvider.autoDispose<FriendRequestsBundle>((ref) async {
      final result = await ref.watch(friendsRepositoryProvider).listRequests();
      if (result.failure != null) throw result.failure!;
      return result.data ?? const FriendRequestsBundle();
    });

class FriendsPage extends ConsumerStatefulWidget {
  const FriendsPage({
    super.key,
    this.focusSearch = false,
    this.openRequestsTab = false,
  });

  final bool focusSearch;

  /// When true (e.g. `/friends?tab=requests`), open the Requests tab (NAV-F6).
  final bool openRequestsTab;

  @override
  ConsumerState<FriendsPage> createState() => _FriendsPageState();
}

class _FriendsPageState extends ConsumerState<FriendsPage>
    with SingleTickerProviderStateMixin {
  late final TabController _tabs;
  final _search = TextEditingController();
  List<FriendUser>? _searchResults;
  String? _searchError;
  bool _searching = false;

  @override
  void initState() {
    super.initState();
    final initialIndex = widget.openRequestsTab && !widget.focusSearch ? 1 : 0;
    _tabs = TabController(length: 2, vsync: this, initialIndex: initialIndex);
    if (widget.focusSearch) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        _tabs.animateTo(0);
      });
    } else if (widget.openRequestsTab) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (_tabs.index != 1) _tabs.animateTo(1);
      });
    }
  }

  @override
  void dispose() {
    _tabs.dispose();
    _search.dispose();
    super.dispose();
  }

  Future<void> _runSearch() async {
    final q = _search.text.trim();
    if (q.length < 3) {
      setState(() {
        _searchResults = null;
        _searchError = AppLocalizations.of(context).friendsSearchMinLength;
      });
      return;
    }
    setState(() {
      _searching = true;
      _searchError = null;
    });
    final result = await ref.read(friendsRepositoryProvider).search(q);
    if (!mounted) return;
    setState(() {
      _searching = false;
      if (result.failure != null) {
        _searchError = failureUserMessage(result.failure!);
        _searchResults = null;
      } else {
        _searchResults = result.data;
      }
    });
  }

  Future<void> _refreshAll() async {
    ref.invalidate(friendsListProvider);
    ref.invalidate(friendRequestsProvider);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.friendsTitle),
        bottom: TabBar(
          controller: _tabs,
          tabs: [
            Tab(text: l10n.friendsTabFriends),
            Tab(text: l10n.friendsTabRequests),
          ],
        ),
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _search,
                    textInputAction: TextInputAction.search,
                    onSubmitted: (_) => _runSearch(),
                    decoration: InputDecoration(
                      hintText: l10n.friendsSearchHint,
                      prefixIcon: const Icon(Icons.search_rounded),
                      filled: true,
                      fillColor: AppColors.surface,
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(
                          color: AppColors.borderSoft,
                        ),
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                IconButton.filled(
                  tooltip: l10n.search,
                  onPressed: _searching ? null : _runSearch,
                  icon: _searching
                      ? const SizedBox(
                          width: 18,
                          height: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.arrow_forward_rounded),
                ),
              ],
            ),
          ),
          if (_searchError != null)
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
              child: Text(
                _searchError!,
                style: const TextStyle(color: AppColors.danger, fontSize: 13),
              ),
            ),
          if (_searchResults != null) ...[
            const SizedBox(height: 8),
            Expanded(child: _SearchResultsList(results: _searchResults!)),
          ] else
            Expanded(
              child: TabBarView(
                controller: _tabs,
                children: [
                  _FriendsList(onChanged: _refreshAll),
                  _RequestsPanel(onChanged: _refreshAll),
                ],
              ),
            ),
        ],
      ),
    );
  }
}

class _FriendsList extends ConsumerWidget {
  const _FriendsList({required this.onChanged});

  final Future<void> Function() onChanged;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final async = ref.watch(friendsListProvider);

    return async.when(
      loading: () => const Center(child: FunLoader()),
      error: (e, _) => Center(
        child: Text(
          e is Failure ? failureUserMessage(e) : l10n.failedToLoadEvents,
        ),
      ),
      data: (friends) {
        if (friends.isEmpty) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Text(
                l10n.friendsEmpty,
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.muted),
              ),
            ),
          );
        }
        return RefreshIndicator(
          onRefresh: onChanged,
          child: ListView.separated(
            padding: const EdgeInsets.all(12),
            itemCount: friends.length,
            separatorBuilder: (_, _) => const SizedBox(height: 6),
            itemBuilder: (context, index) {
              final friend = friends[index];
              return _FriendRow(
                user: friend,
                onOpenProfile: () => context.push('/u/${friend.publicId}'),
                onMessage: () => _openDm(context, ref, friend.id),
                onUnfriend: () => _unfriend(context, ref, friend, onChanged),
              );
            },
          ),
        );
      },
    );
  }

  Future<void> _openDm(BuildContext context, WidgetRef ref, int userId) async {
    final l10n = AppLocalizations.of(context);
    final result = await ref.read(dmsRepositoryProvider).openWith(userId);
    if (!context.mounted) return;
    if (result.failure != null || result.data == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result.failure?.message ?? l10n.failedToLoadEvents,
          ),
        ),
      );
      return;
    }
    context.push('/dms/${result.data!.conversationId}');
  }

  Future<void> _unfriend(
    BuildContext context,
    WidgetRef ref,
    FriendUser friend,
    Future<void> Function() onChanged,
  ) async {
    final result = await ref.read(friendsRepositoryProvider).unfriend(friend.id);
    if (!context.mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await onChanged();
  }
}

class _RequestsPanel extends ConsumerWidget {
  const _RequestsPanel({required this.onChanged});

  final Future<void> Function() onChanged;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final async = ref.watch(friendRequestsProvider);

    return async.when(
      loading: () => const Center(child: FunLoader()),
      error: (e, _) => Center(
        child: Text(
          e is Failure ? failureUserMessage(e) : l10n.failedToLoadEvents,
        ),
      ),
      data: (bundle) {
        if (bundle.incoming.isEmpty && bundle.outgoing.isEmpty) {
          return Center(
            child: Text(
              l10n.friendsNoRequests,
              style: const TextStyle(color: AppColors.muted),
            ),
          );
        }
        return RefreshIndicator(
          onRefresh: onChanged,
          child: ListView(
            padding: const EdgeInsets.all(12),
            children: [
              if (bundle.incoming.isNotEmpty) ...[
                Text(
                  l10n.friendsIncoming,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 8),
                ...bundle.incoming.map(
                  (row) => _RequestRow(
                    row: row,
                    incoming: true,
                    onChanged: onChanged,
                  ),
                ),
                const SizedBox(height: 16),
              ],
              if (bundle.outgoing.isNotEmpty) ...[
                Text(
                  l10n.friendsOutgoing,
                  style: const TextStyle(fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 8),
                ...bundle.outgoing.map(
                  (row) => _RequestRow(
                    row: row,
                    incoming: false,
                    onChanged: onChanged,
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }
}

class _RequestRow extends ConsumerWidget {
  const _RequestRow({
    required this.row,
    required this.incoming,
    required this.onChanged,
  });

  final FriendRequestRow row;
  final bool incoming;
  final Future<void> Function() onChanged;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final user = row.user;

    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: _Avatar(user: user),
        title: Text(user.name),
        subtitle: Text(user.publicId),
        onTap: () => context.push('/u/${user.publicId}'),
        trailing: incoming
            ? Wrap(
                spacing: 6,
                children: [
                  TextButton(
                    onPressed: () async {
                      final r = await ref
                          .read(friendsRepositoryProvider)
                          .decline(user.id);
                      if (context.mounted && r.failure == null) {
                        await onChanged();
                      }
                    },
                    child: Text(l10n.friendsDecline),
                  ),
                  FilledButton(
                    onPressed: () async {
                      final r = await ref
                          .read(friendsRepositoryProvider)
                          .accept(user.id);
                      if (context.mounted && r.failure == null) {
                        await onChanged();
                      }
                    },
                    child: Text(l10n.friendsAccept),
                  ),
                ],
              )
            : TextButton(
                onPressed: () async {
                  final r = await ref
                      .read(friendsRepositoryProvider)
                      .cancel(user.id);
                  if (context.mounted && r.failure == null) {
                    await onChanged();
                  }
                },
                child: Text(l10n.friendsCancelRequest),
              ),
      ),
    );
  }
}

class _SearchResultsList extends ConsumerWidget {
  const _SearchResultsList({required this.results});

  final List<FriendUser> results;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (results.isEmpty) {
      return Center(child: Text(l10n.friendsSearchEmpty));
    }
    return ListView.separated(
      padding: const EdgeInsets.all(12),
      itemCount: results.length,
      separatorBuilder: (_, _) => const SizedBox(height: 6),
      itemBuilder: (context, index) {
        final user = results[index];
        return _FriendSearchRow(user: user);
      },
    );
  }
}

class _FriendSearchRow extends ConsumerWidget {
  const _FriendSearchRow({required this.user});

  final FriendUser user;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final fs = user.friendship;

    return Card(
      child: ListTile(
        leading: _Avatar(user: user),
        title: Text(user.name),
        subtitle: Text(user.publicId),
        onTap: () => context.push('/u/${user.publicId}'),
        trailing: _FriendshipActionChip(user: user, friendship: fs, l10n: l10n),
      ),
    );
  }
}

class _FriendRow extends StatelessWidget {
  const _FriendRow({
    required this.user,
    required this.onOpenProfile,
    required this.onMessage,
    required this.onUnfriend,
  });

  final FriendUser user;
  final VoidCallback onOpenProfile;
  final VoidCallback onMessage;
  final VoidCallback onUnfriend;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Card(
      child: ListTile(
        leading: _Avatar(user: user),
        title: Text(user.name),
        subtitle: Text(user.publicId),
        onTap: onOpenProfile,
        trailing: Wrap(
          spacing: 4,
          children: [
            IconButton(
              tooltip: l10n.friendsMessage,
              onPressed: onMessage,
              icon: const Icon(Icons.chat_bubble_outline),
            ),
            IconButton(
              tooltip: l10n.friendsUnfriend,
              onPressed: onUnfriend,
              icon: const Icon(Icons.person_remove_outlined),
            ),
          ],
        ),
      ),
    );
  }
}

class _FriendshipActionChip extends ConsumerWidget {
  const _FriendshipActionChip({
    required this.user,
    required this.friendship,
    required this.l10n,
  });

  final FriendUser user;
  final FriendshipState friendship;
  final AppLocalizations l10n;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final status = friendship.status;
    if (status == 'accepted' && friendship.canMessage) {
      return IconButton(
        tooltip: l10n.friendsMessage,
        icon: const Icon(Icons.chat_bubble_outline),
        onPressed: () async {
          final result = await ref.read(dmsRepositoryProvider).openWith(user.id);
          if (!context.mounted || result.data == null) return;
          context.push('/dms/${result.data!.conversationId}');
        },
      );
    }
    if (status == 'pending' && friendship.direction == 'outgoing') {
      return TextButton(
        onPressed: () async {
          await ref.read(friendsRepositoryProvider).cancel(user.id);
        },
        child: Text(l10n.friendsCancelRequest),
      );
    }
    if (friendship.canRequest) {
      return FilledButton(
        onPressed: () async {
          final r = await ref
              .read(friendsRepositoryProvider)
              .requestFriend(user.publicId);
          if (context.mounted && r.failure == null) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(content: Text(l10n.friendsRequestSent)),
            );
          }
        },
        child: Text(l10n.friendsAdd),
      );
    }
    return const SizedBox.shrink();
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.user});

  final FriendUser user;

  @override
  Widget build(BuildContext context) {
    final photo = user.profileImagePath;
    if (photo != null && photo.isNotEmpty) {
      return CircleAvatar(
        child: ClipOval(
          child: AppNetworkImage(
            url: photo,
            logicalWidth: 40,
            logicalHeight: 40,
            fit: BoxFit.cover,
          ),
        ),
      );
    }
    return CircleAvatar(child: Text(user.initials));
  }
}
