import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
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

  List<EventItem> get _hosting => _items
      .where((e) => e.joinAction == 'owned' || e.createdBy == _myUserId)
      .toList();

  List<EventItem> get _joined => _items
      .where((e) => e.joinAction != 'owned' && e.createdBy != _myUserId)
      .toList();

  int? get _myUserId {
    final auth = ref.read(authControllerProvider);
    return auth is Authenticated ? auth.user.id : null;
  }

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

    final mineFuture = ref
        .read(eventsRepositoryProvider)
        .mine(page: refresh ? 1 : _page);
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
    final result = await ref
        .read(eventsRepositoryProvider)
        .cancelJoinRequest(eventId);
    if (!mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      return;
    }
    setState(() {
      _pending = _pending.where((p) => p.event.id != eventId).toList();
    });
  }

  Widget _sectionGrid({
    required List<Widget> children,
    required double maxWidth,
  }) {
    final cols = maxWidth >= 1000
        ? 3
        : maxWidth >= 560
            ? 2
            : 1;
    final gap = 10.0;
    final cardWidth =
        cols == 1 ? maxWidth : (maxWidth - gap * (cols - 1)) / cols;

    return Wrap(
      spacing: gap,
      runSpacing: gap,
      children: [
        for (final child in children) SizedBox(width: cardWidth, child: child),
      ],
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
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
                child: LayoutBuilder(
                  builder: (context, constraints) {
                    return ListView(
                      padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
                      children: [
                        if (_pending.isNotEmpty) ...[
                          Text(
                            l10n.pendingRequestsSection,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                              color: AppColors.text,
                            ),
                          ),
                          const SizedBox(height: 10),
                          _sectionGrid(
                            maxWidth: constraints.maxWidth - 40,
                            children: [
                              for (final row in _pending)
                                _MineCard(
                                  event: row.event,
                                  kind: _MineKind.pending,
                                  locale: locale,
                                  onView: () =>
                                      context.push('/events/${row.event.id}'),
                                  onCancel: () => _cancelJoin(row.event.id),
                                ),
                            ],
                          ),
                          const SizedBox(height: 20),
                        ],
                        Text(
                          l10n.hostingEventsSection,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: AppColors.text,
                          ),
                        ),
                        const SizedBox(height: 10),
                        if (_hosting.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Text(
                              l10n.hostingEmpty,
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: AppColors.muted),
                            ),
                          )
                        else
                          _sectionGrid(
                            maxWidth: constraints.maxWidth - 40,
                            children: [
                              for (final event in _hosting)
                                _MineCard(
                                  event: event,
                                  kind: _MineKind.hosting,
                                  locale: locale,
                                  onView: () =>
                                      context.push('/events/${event.id}'),
                                  onTicket: () => context.push(
                                    '/events/${event.id}/ticket',
                                  ),
                                ),
                            ],
                          ),
                        const SizedBox(height: 20),
                        Text(
                          l10n.joinedEventsSection,
                          style: const TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w800,
                            color: AppColors.text,
                          ),
                        ),
                        const SizedBox(height: 10),
                        if (_joined.isEmpty)
                          Padding(
                            padding: const EdgeInsets.symmetric(vertical: 12),
                            child: Text(
                              l10n.joinedEmpty,
                              textAlign: TextAlign.center,
                              style: const TextStyle(color: AppColors.muted),
                            ),
                          )
                        else
                          _sectionGrid(
                            maxWidth: constraints.maxWidth - 40,
                            children: [
                              for (final event in _joined)
                                _MineCard(
                                  event: event,
                                  kind: _MineKind.joined,
                                  locale: locale,
                                  onView: () =>
                                      context.push('/events/${event.id}'),
                                  onTicket: () => context.push(
                                    '/events/${event.id}/ticket',
                                  ),
                                ),
                            ],
                          ),
                        if (_hasMore)
                          Padding(
                            padding: const EdgeInsets.all(16),
                            child: Builder(
                              builder: (context) {
                                WidgetsBinding.instance
                                    .addPostFrameCallback((_) {
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
                    );
                  },
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

enum _MineKind { pending, hosting, joined }

class _MineCard extends StatelessWidget {
  const _MineCard({
    required this.event,
    required this.kind,
    required this.locale,
    required this.onView,
    this.onTicket,
    this.onCancel,
  });

  final EventItem event;
  final _MineKind kind;
  final String locale;
  final VoidCallback onView;
  final VoidCallback? onTicket;
  final VoidCallback? onCancel;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final when = event.startsAt == null
        ? ''
        : DateFormat('MMM d · h:mm a', locale)
            .format(event.startsAt!.toLocal());

    final (badge, badgeFg, badgeBg, badgeBorder, bar) = switch (kind) {
      _MineKind.pending => (
          l10n.requestPendingShort,
          const Color(0xFFB45309),
          const Color(0xFFFFFBEB),
          const Color(0xFFFDE68A),
          const [Color(0xFFFBBF24), Color(0xFFFB923C), Color(0xFFFB7185)],
        ),
      _MineKind.hosting => (
          l10n.yourOwnEvent,
          AppColors.primary,
          AppColors.primarySoft,
          AppColors.primary.withValues(alpha: 0.25),
          const [Color(0xFF6366F1), Color(0xFF8B5CF6), Color(0xFFFB923C)],
        ),
      _MineKind.joined => (
          l10n.joined,
          AppColors.success,
          const Color(0xFFECFDF5),
          const Color(0xFFA7F3D0),
          const [Color(0xFF34D399), Color(0xFF2DD4BF), Color(0xFF818CF8)],
        ),
    };

    final meta = [
      if (when.isNotEmpty) when,
      '${event.membersCount} / ${event.maxParticipants}',
      if (event.city?.name != null && event.city!.name.isNotEmpty)
        event.city!.name,
    ].join(' · ');

    return Material(
      color: kind == _MineKind.pending
          ? const Color(0xFFFFFBEB).withValues(alpha: 0.45)
          : AppColors.surface,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onView,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(
              color: kind == _MineKind.pending
                  ? const Color(0xFFFDE68A).withValues(alpha: 0.9)
                  : AppColors.borderSoft,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                height: 3,
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: bar),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 7,
                            vertical: 3,
                          ),
                          decoration: BoxDecoration(
                            color: badgeBg,
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(color: badgeBorder),
                          ),
                          child: Text(
                            badge,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w800,
                              color: badgeFg,
                            ),
                          ),
                        ),
                        const Spacer(),
                        if (event.status != null && kind != _MineKind.pending)
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 7,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(999),
                              border: Border.all(color: AppColors.borderSoft),
                            ),
                            child: Text(
                              event.status!,
                              style: const TextStyle(
                                fontSize: 10,
                                fontWeight: FontWeight.w700,
                                color: AppColors.muted,
                              ),
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      event.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      textDirection: bidiTextDirection(event.title),
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w800,
                        height: 1.25,
                      ),
                    ),
                    if (meta.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        meta,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          color: AppColors.muted,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                    if (kind == _MineKind.pending) ...[
                      const SizedBox(height: 6),
                      Text(
                        l10n.pendingReviewNotice,
                        style: const TextStyle(
                          fontSize: 10,
                          color: Color(0xFF92400E),
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                    const SizedBox(height: 10),
                    const Divider(height: 1, color: Color(0xFFF1F5F9)),
                    const SizedBox(height: 10),
                    Wrap(
                      spacing: 6,
                      runSpacing: 6,
                      children: [
                        if (onTicket != null)
                          _MineAction(
                            label: l10n.viewTicket,
                            filled: true,
                            color: AppColors.accent,
                            onTap: onTicket!,
                          ),
                        _MineAction(
                          label: l10n.viewEvent,
                          onTap: onView,
                        ),
                        if (onCancel != null)
                          CancelStopButton(
                            label: l10n.cancelJoin,
                            size: CancelStopSize.sm,
                            onPressed: onCancel!,
                          ),
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

class _MineAction extends StatelessWidget {
  const _MineAction({
    required this.label,
    required this.onTap,
    this.filled = false,
    this.color,
  });

  final String label;
  final VoidCallback onTap;
  final bool filled;
  final Color? color;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: filled ? (color ?? AppColors.accent) : Colors.white,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
          decoration: filled
              ? null
              : BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
          child: Text(
            label,
            style: TextStyle(
              color: filled ? Colors.white : AppColors.text,
              fontSize: 10,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ),
    );
  }
}
