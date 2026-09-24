import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

class MyEventsPage extends ConsumerStatefulWidget {
  const MyEventsPage({super.key});

  @override
  ConsumerState<MyEventsPage> createState() => _MyEventsPageState();
}

class _MyEventsPageState extends ConsumerState<MyEventsPage> {
  List<EventItem> _items = [];
  List<HomeParticipationRow> _pending = [];
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

    final mineFuture =
        ref.read(eventsRepositoryProvider).mine(page: refresh ? 1 : _page);
    final pendingFuture = refresh
        ? ref.read(homeRepositoryProvider).participations()
        : null;

    final mine = await mineFuture;
    final pending = pendingFuture == null ? null : await pendingFuture;

    if (!mounted) return;
    setState(() {
      _loading = false;
      _loadingMore = false;
      if (mine.failure != null) {
        _error = mine.failure!.message;
        return;
      }
      final page = mine.data!;
      if (refresh) {
        _items = page.items;
        if (pending?.data != null) {
          _pending = pending!.data!.pendingRequests;
        }
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

  Future<void> _cancelJoin(int eventId) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmCancelJoin,
    );
    if (confirm != true || !mounted) return;
    final result =
        await ref.read(eventsRepositoryProvider).cancelJoinRequest(eventId);
    if (!mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    setState(() {
      _pending = _pending.where((p) => p.event.id != eventId).toList();
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isEmpty = _items.isEmpty && _pending.isEmpty;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    l10n.navMyEvents,
                    style: const TextStyle(
                      fontSize: 25,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.4,
                    ),
                  ),
                ),
                TextButton.icon(
                  onPressed: () => context.go('/create'),
                  icon: const Icon(Icons.add, size: 18),
                  label: Text(l10n.createEvent),
                ),
              ],
            ),
          ),
          Expanded(
            child: AsyncBody(
              isLoading: _loading && isEmpty,
              error: _error,
              onRetry: () => _load(refresh: true),
              isEmpty: isEmpty,
              empty: Center(
                child: Padding(
                  padding: const EdgeInsets.all(24),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        l10n.emptyMyEvents,
                        textAlign: TextAlign.center,
                        style: const TextStyle(color: AppColors.muted),
                      ),
                      const SizedBox(height: 16),
                      GradientButton(
                        label: l10n.createEvent,
                        onPressed: () => context.go('/create'),
                      ),
                    ],
                  ),
                ),
              ),
              child: RefreshIndicator(
                color: AppColors.primary,
                onRefresh: () => _load(refresh: true),
                child: ListView(
                  padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
                  children: [
                    if (_pending.isNotEmpty) ...[
                      Text(
                        l10n.pendingRequestsSection,
                        style: const TextStyle(
                          fontSize: 17,
                          fontWeight: FontWeight.w800,
                          color: AppColors.text,
                        ),
                      ),
                      const SizedBox(height: 12),
                      for (final row in _pending) ...[
                        _PendingRequestCard(
                          row: row,
                          onView: () =>
                              context.push('/events/${row.event.id}'),
                          onCancel: () => _cancelJoin(row.event.id),
                        ),
                        const SizedBox(height: 12),
                      ],
                      const SizedBox(height: 12),
                    ],
                    Text(
                      l10n.joinedEventsSection,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 12),
                    if (_items.isEmpty)
                      Padding(
                        padding: const EdgeInsets.symmetric(vertical: 24),
                        child: Text(
                          l10n.emptyMyEvents,
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: AppColors.muted),
                        ),
                      )
                    else
                      for (var i = 0; i < _items.length; i++) ...[
                        if (i > 0) const SizedBox(height: 16),
                        MyEventCardWrapper(
                          event: _items[i],
                          onView: () =>
                              context.push('/events/${_items[i].id}'),
                          onViewTicket: _items[i].joinAction == 'joined'
                              ? () => context
                                  .push('/events/${_items[i].id}/ticket')
                              : null,
                        ),
                      ],
                    if (_hasMore)
                      Padding(
                        padding: const EdgeInsets.all(16),
                        child: Builder(
                          builder: (context) {
                            WidgetsBinding.instance.addPostFrameCallback((_) {
                              if (mounted) _loadMore();
                            });
                            return const Center(
                              child: FunLoader(
                                size: FunLoaderSize.sm,
                                showLabel: false,
                              ),
                            );
                          },
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _PendingRequestCard extends StatelessWidget {
  const _PendingRequestCard({
    required this.row,
    required this.onView,
    required this.onCancel,
  });

  final HomeParticipationRow row;
  final VoidCallback onView;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final event = row.event;
    final when = event.startsAt == null
        ? ''
        : DateFormat('MMM d · h:mm a', locale)
            .format(event.startsAt!.toLocal());
    final city = event.city?.name;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: const Color(0xFFFFFBEB).withValues(alpha: 0.55),
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFFDE68A).withValues(alpha: 0.9)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: const Color(0xFFFFFBEB),
              borderRadius: BorderRadius.circular(999),
              border: Border.all(color: const Color(0xFFFDE68A)),
            ),
            child: Text(
              l10n.requestPendingShort,
              style: const TextStyle(
                fontSize: 11,
                fontWeight: FontWeight.w800,
                color: Color(0xFFB45309),
              ),
            ),
          ),
          const SizedBox(height: 10),
          InkWell(
            onTap: onView,
            child: Text(
              event.title,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.text,
              ),
            ),
          ),
          if (when.isNotEmpty || (city != null && city.isNotEmpty)) ...[
            const SizedBox(height: 6),
            Text(
              [
                if (when.isNotEmpty) when,
                ?city,
              ].join(' · '),
              style: const TextStyle(
                fontSize: 13,
                color: AppColors.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
          const SizedBox(height: 8),
          Text(
            l10n.pendingReviewNotice,
            style: const TextStyle(
              fontSize: 12,
              color: Color(0xFF92400E),
            ),
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              OutlinedButton(
                onPressed: onView,
                child: Text(l10n.viewEvent),
              ),
              const SizedBox(width: 8),
              CancelStopButton(
                label: l10n.cancelJoin,
                size: CancelStopSize.sm,
                onPressed: onCancel,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class MyEventCardWrapper extends StatelessWidget {
  const MyEventCardWrapper({
    super.key,
    required this.event,
    required this.onView,
    this.onViewTicket,
  });

  final EventItem event;
  final VoidCallback onView;
  final VoidCallback? onViewTicket;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        EventCard(
          event: event,
          onView: onView,
          statusBadge: event.status != null
              ? EventStatusChip(status: event.status!)
              : null,
        ),
        if (onViewTicket != null) ...[
          const SizedBox(height: 8),
          Align(
            alignment: AlignmentDirectional.centerStart,
            child: Material(
              color: AppColors.accent,
              borderRadius: BorderRadius.circular(12),
              child: InkWell(
                onTap: onViewTicket,
                borderRadius: BorderRadius.circular(12),
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 10,
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(
                        Icons.confirmation_number_outlined,
                        size: 16,
                        color: Colors.white,
                      ),
                      const SizedBox(width: 6),
                      Text(
                        l10n.viewTicket,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 12,
                          fontWeight: FontWeight.w800,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}

class EventStatusChip extends StatelessWidget {
  const EventStatusChip({super.key, required this.status});

  final String status;

  @override
  Widget build(BuildContext context) {
    final color = switch (status) {
      'published' => AppColors.success,
      'cancelled' => AppColors.danger,
      'draft' => AppColors.muted,
      _ => AppColors.primary,
    };

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
        border: Border.all(color: color.withValues(alpha: 0.35)),
      ),
      child: Text(
        status,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: color,
        ),
      ),
    );
  }
}
