import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class MyEventsPage extends ConsumerStatefulWidget {
  const MyEventsPage({super.key});

  @override
  ConsumerState<MyEventsPage> createState() => _MyEventsPageState();
}

class _MyEventsPageState extends ConsumerState<MyEventsPage> {
  List<EventItem> _items = [];
  String? _error;
  bool _loading = true;
  int _page = 1;
  bool _hasMore = false;
  bool _loadingMore = false;

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
    final result =
        await ref.read(eventsRepositoryProvider).mine(page: refresh ? 1 : _page);
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

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
              child: Text(
                l10n.navMyEvents,
                style: const TextStyle(
                  fontSize: 25,
                  fontWeight: FontWeight.w800,
                  letterSpacing: -0.4,
                ),
              ),
            ),
            Expanded(
              child: AsyncBody(
                isLoading: _loading && _items.isEmpty,
                error: _error,
                onRetry: () => _load(refresh: true),
                isEmpty: _items.isEmpty,
                empty: Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Text(
                      l10n.emptyMyEvents,
                      textAlign: TextAlign.center,
                      style: const TextStyle(color: AppColors.muted),
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
                      return MyEventCardWrapper(
                        event: event,
                        onView: () => context.push('/events/${event.id}'),
                      );
                    },
                  ),
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class MyEventCardWrapper extends StatelessWidget {
  const MyEventCardWrapper({
    super.key,
    required this.event,
    required this.onView,
  });

  final EventItem event;
  final VoidCallback onView;

  @override
  Widget build(BuildContext context) {
    return Stack(
      clipBehavior: Clip.none,
      children: [
        EventCard(event: event, onView: onView),
        if (event.status != null)
          PositionedDirectional(
            top: 12,
            start: 12,
            child: EventStatusChip(status: event.status!),
          ),
      ],
    );
  }
}

class EventStatusChip extends StatelessWidget {
  const EventStatusChip({super.key, required this.status});

  final String status;

  String _label(AppLocalizations l10n) {
    return switch (status) {
      'draft' => l10n.statusDraft,
      'published' => l10n.statusPublished,
      'cancelled' => l10n.statusCancelled,
      'completed' => l10n.statusCompleted,
      _ => status,
    };
  }

  Color _color() {
    return switch (status) {
      'draft' => AppColors.muted,
      'published' => AppColors.primary,
      'cancelled' => AppColors.danger,
      'completed' => AppColors.success,
      _ => AppColors.muted,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: _color().withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: _color().withValues(alpha: 0.4)),
      ),
      child: Text(
        _label(l10n),
        style: TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: _color(),
        ),
      ),
    );
  }
}
