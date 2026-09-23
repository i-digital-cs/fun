import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class FavoritesPage extends ConsumerStatefulWidget {
  const FavoritesPage({super.key});

  @override
  ConsumerState<FavoritesPage> createState() => _FavoritesPageState();
}

class _FavoritesPageState extends ConsumerState<FavoritesPage> {
  List<EventItem> _items = [];
  String? _error;
  bool _loading = true;
  int _page = 1;
  bool _hasMore = false;
  bool _loadingMore = false;
  final Set<int> _toggling = {};

  @override
  void initState() {
    super.initState();
    _load(refresh: true);
  }

  Future<void> _load({bool refresh = false}) async {
    if (refresh) {
      setState(() {
        _loading = true;
        _error = null;
        _page = 1;
      });
    }
    final result = await ref
        .read(eventsRepositoryProvider)
        .favorites(page: refresh ? 1 : _page);
    if (!mounted) return;
    setState(() {
      _loading = false;
      _loadingMore = false;
      if (result.failure != null) {
        _error = result.failure!.message;
        return;
      }
      final page = result.data!;
      if (refresh) {
        _items = page.items;
      } else {
        _items = [..._items, ...page.items];
      }
      _page = page.currentPage;
      _hasMore = page.hasMore;
    });
  }

  Future<void> _loadMore() async {
    if (_loadingMore || !_hasMore) return;
    setState(() => _loadingMore = true);
    _page += 1;
    await _load();
  }

  Future<void> _toggleFavorite(EventItem event) async {
    if (_toggling.contains(event.id)) return;
    final previous = event.isFavorited;
    setState(() {
      _toggling.add(event.id);
      if (previous) {
        _items = _items.where((e) => e.id != event.id).toList();
      } else {
        _items = _items
            .map(
              (e) => e.id == event.id ? e.copyWith(isFavorited: true) : e,
            )
            .toList();
      }
    });

    final result =
        await ref.read(eventsRepositoryProvider).toggleFavorite(event.id);
    if (!mounted) return;
    setState(() => _toggling.remove(event.id));
    if (result.failure != null) {
      setState(() {
        if (previous) {
          _items = [event, ..._items];
        } else {
          _items = _items
              .map(
                (e) =>
                    e.id == event.id ? e.copyWith(isFavorited: false) : e,
              )
              .toList();
        }
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    final favorited = result.data ?? false;
    if (!favorited && !_items.any((e) => e.id == event.id)) {
      // already removed
    } else if (!favorited) {
      setState(() {
        _items = _items.where((e) => e.id != event.id).toList();
      });
    }
  }

  Future<void> _cancelJoin(EventItem event) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmCancelJoin,
    );
    if (confirm != true || !mounted) return;
    final result =
        await ref.read(eventsRepositoryProvider).cancelJoinRequest(event.id);
    if (!mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    final next = result.data ?? 'request_to_join';
    setState(() {
      _items = [
        for (final e in _items)
          if (e.id == event.id) e.copyWith(joinAction: next) else e,
      ];
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.favorites)),
      body: AsyncBody(
        isLoading: _loading && _items.isEmpty,
        error: _error,
        onRetry: () => _load(refresh: true),
        isEmpty: _items.isEmpty,
        empty: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  l10n.emptyFavorites,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: 16),
                GradientButton(
                  label: l10n.navDiscover,
                  icon: Icons.explore_outlined,
                  onPressed: () => context.go('/discover'),
                ),
              ],
            ),
          ),
        ),
        child: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () => _load(refresh: true),
          child: ListView.separated(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
            itemCount: _items.length + (_hasMore ? 1 : 0),
            separatorBuilder: (_, _) => const SizedBox(height: 16),
            itemBuilder: (context, index) {
              if (index >= _items.length) {
                _loadMore();
                return const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(
                    child: FunLoader(
                      size: FunLoaderSize.sm,
                      showLabel: false,
                    ),
                  ),
                );
              }
              final event = _items[index];
              return EventCard(
                event: event,
                onView: () => context.push('/events/${event.id}'),
                onFavorite: () => _toggleFavorite(event),
                onCancelJoin: event.joinAction == 'request_pending'
                    ? () => _cancelJoin(event)
                    : null,
              );
            },
          ),
        ),
      ),
    );
  }
}
