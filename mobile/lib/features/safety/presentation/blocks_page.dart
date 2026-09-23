import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/safety/data/safety_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

class BlocksPage extends ConsumerStatefulWidget {
  const BlocksPage({super.key});

  @override
  ConsumerState<BlocksPage> createState() => _BlocksPageState();
}

class _BlocksPageState extends ConsumerState<BlocksPage> {
  List<BlockedUser> _blocks = const [];
  String? _error;
  bool _loading = true;
  bool _loadingMore = false;
  int _page = 1;
  bool _hasMore = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load({bool append = false}) async {
    if (append) {
      if (_loadingMore || !_hasMore) return;
      setState(() => _loadingMore = true);
    } else {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
      });
    }

    final page = append ? _page + 1 : 1;
    final result =
        await ref.read(safetyRemoteSourceProvider).listBlocks(page: page);
    if (!mounted) return;

    if (result.failure != null) {
      setState(() {
        _loading = false;
        _loadingMore = false;
        _error = result.failure!.message;
      });
      return;
    }

    final pageData = result.data!;
    setState(() {
      _loading = false;
      _loadingMore = false;
      _page = pageData.currentPage;
      _hasMore = pageData.hasMore;
      _blocks = append
          ? [..._blocks, ...pageData.items]
          : pageData.items;
    });
  }

  Future<void> _unblock(BlockedUser user) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.unblock),
        content: Text(l10n.confirmUnblock(user.name)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.unblock),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    final result =
        await ref.read(safetyRemoteSourceProvider).unblock(user.userId);
    if (!mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(l10n.userUnblocked)),
    );
    await _load();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.blocks)),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        isEmpty: _blocks.isEmpty,
        empty: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              l10n.noBlocks,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.muted),
            ),
          ),
        ),
        child: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: _load,
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 20, 20, 40),
            itemCount: _blocks.length + (_hasMore ? 1 : 0),
            separatorBuilder: (_, _) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              if (index >= _blocks.length) {
                return TextButton(
                  onPressed: _loadingMore ? null : () => _load(append: true),
                  child: _loadingMore
                      ? const FunLoader(
                          size: FunLoaderSize.sm,
                          showLabel: false,
                        )
                      : Text(l10n.loadMore),
                );
              }
              final block = _blocks[index];
              return BlockedUserTile(
                user: block,
                onUnblock: () => _unblock(block),
              );
            },
          ),
        ),
      ),
    );
  }
}

class BlockedUserTile extends StatelessWidget {
  const BlockedUserTile({
    super.key,
    required this.user,
    required this.onUnblock,
  });

  final BlockedUser user;
  final VoidCallback onUnblock;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
      ),
      child: Row(
        children: [
          CircleAvatar(
            backgroundColor: AppColors.primarySoft,
            child: Text(
              user.name.isNotEmpty ? user.name[0].toUpperCase() : '?',
              style: const TextStyle(
                color: AppColors.primary,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Text(
              user.name,
              style: const TextStyle(
                fontWeight: FontWeight.w700,
                fontSize: 15,
              ),
            ),
          ),
          TextButton(
            onPressed: onUnblock,
            child: Text(l10n.unblock),
          ),
        ],
      ),
    );
  }
}
