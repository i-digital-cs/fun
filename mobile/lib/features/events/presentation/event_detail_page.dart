import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

class EventDetailPage extends ConsumerStatefulWidget {
  const EventDetailPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventDetailPage> createState() => _EventDetailPageState();
}

class _EventDetailPageState extends ConsumerState<EventDetailPage> {
  EventDetail? _detail;
  List<EventRating> _ratings = const [];
  String? _error;
  bool _loading = true;
  bool _acting = false;
  bool _favoriting = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final repo = ref.read(eventsRepositoryProvider);
    final detailResult = await repo.show(widget.eventId);
    final ratingsResult = await repo.ratings(widget.eventId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (detailResult.failure != null) {
        _error = detailResult.failure!.message;
      } else {
        _detail = detailResult.data;
        _ratings = ratingsResult.data ?? const [];
      }
    });
  }

  int? get _currentUserId {
    final auth = ref.read(authControllerProvider);
    return auth is Authenticated ? auth.user.id : null;
  }

  bool get _isOwner {
    final detail = _detail;
    final userId = _currentUserId;
    return detail != null &&
        userId != null &&
        detail.event.createdBy == userId;
  }

  bool get _canEdit {
    final event = _detail?.event;
    if (!_isOwner || event == null) return false;
    return event.status != 'cancelled' && event.status != 'completed';
  }

  bool get _canParticipate {
    final auth = ref.read(authControllerProvider);
    return auth is Authenticated && auth.user.canParticipate;
  }

  Future<void> _toggleFavorite() async {
    final detail = _detail;
    if (detail == null || _favoriting) return;
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      context.push('/login');
      return;
    }
    final previous = detail.event.isFavorited;
    setState(() {
      _favoriting = true;
      _detail = EventDetail(
        event: detail.event.copyWith(isFavorited: !previous),
        joinAction: detail.joinAction,
        readyForRatings: detail.readyForRatings,
        canRate: detail.canRate,
        myRating: detail.myRating,
        creatorRating: detail.creatorRating,
      );
    });
    final result =
        await ref.read(eventsRepositoryProvider).toggleFavorite(widget.eventId);
    if (!mounted) return;
    setState(() => _favoriting = false);
    if (result.failure != null) {
      setState(() {
        _detail = EventDetail(
          event: detail.event.copyWith(isFavorited: previous),
          joinAction: detail.joinAction,
          readyForRatings: detail.readyForRatings,
          canRate: detail.canRate,
          myRating: detail.myRating,
          creatorRating: detail.creatorRating,
        );
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    final favorited = result.data ?? !previous;
    setState(() {
      _detail = EventDetail(
        event: detail.event.copyWith(isFavorited: favorited),
        joinAction: detail.joinAction,
        readyForRatings: detail.readyForRatings,
        canRate: detail.canRate,
        myRating: detail.myRating,
        creatorRating: detail.creatorRating,
      );
    });
  }

  Future<void> _join() async {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated) {
      context.push('/login');
      return;
    }
    setState(() => _acting = true);
    final result =
        await ref.read(eventsRepositoryProvider).join(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _leave() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.leaveEvent),
        content: Text(l10n.confirmLeave),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.leave),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    setState(() => _acting = true);
    final result =
        await ref.read(eventsRepositoryProvider).leave(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _cancelJoinRequest() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.cancelJoin),
        content: Text(l10n.confirmCancelJoin),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.cancelJoin),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    setState(() => _acting = true);
    final result = await ref
        .read(eventsRepositoryProvider)
        .cancelJoinRequest(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _publish() async {
    setState(() => _acting = true);
    final result =
        await ref.read(eventsRepositoryProvider).publish(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).eventPublished)),
    );
    await _load();
  }

  Future<void> _cancelEvent() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.cancelEvent),
        content: Text(l10n.confirmCancel),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.back),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.cancelEvent),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;

    setState(() => _acting = true);
    final result =
        await ref.read(eventsRepositoryProvider).cancel(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _completeEvent() async {
    setState(() => _acting = true);
    final result =
        await ref.read(eventsRepositoryProvider).complete(widget.eventId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _showRateSheet() async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => RateEventSheet(
        eventId: widget.eventId,
        existingRating: _detail?.myRating,
        onRated: _load,
      ),
    );
  }

  Future<void> _blockCreator() async {
    final creatorId = _detail?.event.createdBy;
    if (creatorId == null) return;
    final l10n = AppLocalizations.of(context);
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.blockCreator),
        content: Text(l10n.confirmBlock(l10n.blockCreator)),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.cancel),
          ),
          TextButton(
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.blockUser),
          ),
        ],
      ),
    );
    if (confirm != true || !mounted) return;
    final result =
        await ref.read(safetyRemoteSourceProvider).block(creatorId);
    if (!mounted) return;
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(l10n.userBlocked)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final detail = _detail;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.viewEvent),
        actions: [
          if (detail != null &&
              ref.watch(authControllerProvider) is Authenticated)
            IconButton(
              icon: Icon(
                detail.event.isFavorited
                    ? Icons.bookmark
                    : Icons.bookmark_border,
                color: detail.event.isFavorited ? AppColors.accentAmber : null,
              ),
              tooltip: detail.event.isFavorited
                  ? l10n.unfavorite
                  : l10n.favorite,
              onPressed: _favoriting ? null : _toggleFavorite,
            ),
          if (detail != null && !_isOwner) ...[
            IconButton(
              icon: const Icon(Icons.flag_outlined),
              tooltip: l10n.report,
              onPressed: () => showReportSheet(
                context,
                reportableType: 'event',
                reportableId: widget.eventId,
              ),
            ),
            if (_canParticipate && detail.event.createdBy != null)
              IconButton(
                icon: const Icon(Icons.block_outlined),
                tooltip: l10n.blockCreator,
                onPressed: _blockCreator,
              ),
          ],
          if (_canEdit)
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              onPressed: () =>
                  context.push('/events/${widget.eventId}/edit'),
            ),
        ],
      ),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        child: detail == null
            ? const SizedBox.shrink()
            : ListView(
                padding: const EdgeInsets.all(20),
                children: [
                  EventDetailHero(event: detail.event),
                  const SizedBox(height: 16),
                  EventDetailInfoCard(detail: detail),
                  const SizedBox(height: 16),
                  EventMembersProgress(event: detail.event),
                  const SizedBox(height: 16),
                  CreatorRatingSection(
                    rating: detail.creatorRating,
                    ratings: _ratings,
                    myRating: detail.myRating,
                  ),
                  const SizedBox(height: 20),
                  if (_isOwner)
                    EventOwnerActions(
                      event: detail.event,
                      acting: _acting,
                      onPublish: _publish,
                      onCancel: _cancelEvent,
                      onComplete: _completeEvent,
                      onRequests: () =>
                          context.push('/events/${widget.eventId}/requests'),
                      onMembers: () =>
                          context.push('/events/${widget.eventId}/members'),
                      onChat: () =>
                          context.push('/events/${widget.eventId}/chat'),
                      onViewTicket: () =>
                          context.push('/events/${widget.eventId}/ticket'),
                    )
                  else if (ref.watch(authControllerProvider) is! Authenticated)
                    GradientButton.indigo(
                      label: l10n.loginRequired,
                      onPressed: () => context.push('/login'),
                      icon: Icons.login,
                    )
                  else
                    EventJoinActions(
                      joinAction: detail.joinAction,
                      acting: _acting,
                      onJoin: _join,
                      onLeave: _leave,
                      onCancelJoin: _cancelJoinRequest,
                      onChat: () =>
                          context.push('/events/${widget.eventId}/chat'),
                      onMembers: () =>
                          context.push('/events/${widget.eventId}/members'),
                      onViewTicket: () =>
                          context.push('/events/${widget.eventId}/ticket'),
                    ),
                  if (detail.canRate) ...[
                    const SizedBox(height: 12),
                    GradientButton.indigo(
                      label: l10n.rateEvent,
                      onPressed: _showRateSheet,
                      icon: Icons.star_outline,
                    ),
                  ] else if (detail.myRating != null) ...[
                    const SizedBox(height: 12),
                    TextButton(
                      onPressed: _showRateSheet,
                      child: Text(l10n.yourRating(detail.myRating!.stars)),
                    ),
                  ],
                ],
              ),
      ),
    );
  }
}

class EventDetailHero extends StatelessWidget {
  const EventDetailHero({super.key, required this.event});

  final EventItem event;

  @override
  Widget build(BuildContext context) {
    return ClipRRect(
      borderRadius: BorderRadius.circular(24),
      child: SizedBox(
        height: 200,
        width: double.infinity,
        child: event.imageUrl != null && event.imageUrl!.isNotEmpty
            ? LayoutBuilder(
                builder: (context, constraints) {
                  return AppNetworkImage(
                    url: event.imageUrl!,
                    logicalWidth: constraints.maxWidth,
                    logicalHeight: 200,
                    maxCacheWidth: 1200,
                    maxCacheHeight: 800,
                    error: const ColoredBox(
                      color: AppColors.primarySoft,
                      child: Icon(
                        Icons.image_outlined,
                        color: AppColors.primary,
                        size: 48,
                      ),
                    ),
                  );
                },
              )
            : const ColoredBox(
                color: AppColors.primarySoft,
                child: Icon(Icons.image_outlined,
                    color: AppColors.primary, size: 48),
              ),
      ),
    );
  }
}

class EventDetailInfoCard extends StatelessWidget {
  const EventDetailInfoCard({super.key, required this.detail});

  final EventDetail detail;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final event = detail.event;
    final schedule = event.startsAt != null
        ? DateFormat('EEEE, MMM d · h:mm a', locale)
            .format(event.startsAt!.toLocal())
        : null;

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          if (event.category != null)
            Text(
              event.category!.name,
              style: const TextStyle(
                color: AppColors.primary,
                fontWeight: FontWeight.w700,
                fontSize: 12,
              ),
            ),
          const SizedBox(height: 8),
          Text(
            event.title,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
            ),
          ),
          if (schedule != null) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(Icons.calendar_today_outlined,
                    size: 16, color: AppColors.primary),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(schedule,
                      style: const TextStyle(color: AppColors.muted)),
                ),
              ],
            ),
          ],
          const SizedBox(height: 6),
          Row(
            children: [
              const Icon(Icons.location_on, size: 16, color: AppColors.muted),
              const SizedBox(width: 8),
              Expanded(
                child: Text(event.venue,
                    style: const TextStyle(color: AppColors.muted)),
              ),
            ],
          ),
          if (event.city != null) ...[
            const SizedBox(height: 4),
            Text(
              event.city!.name,
              style: const TextStyle(color: AppColors.muted, fontSize: 12),
            ),
          ],
          if (event.description != null) ...[
            const SizedBox(height: 16),
            Text(event.description!, style: const TextStyle(height: 1.5)),
          ],
          if (event.requirements != null &&
              event.requirements!.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              l10n.requirements,
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text(event.requirements!,
                style: const TextStyle(color: AppColors.muted, height: 1.4)),
          ],
          if (!event.isFree) ...[
            const SizedBox(height: 12),
            Text(
              l10n.costPerPersonAmount('${event.costPerPerson}'),
              style: const TextStyle(fontWeight: FontWeight.w600),
            ),
          ],
        ],
      ),
    );
  }
}

class EventMembersProgress extends StatelessWidget {
  const EventMembersProgress({super.key, required this.event});

  final EventItem event;

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
      child: Column(
        children: [
          Row(
            children: [
              Text(l10n.members,
                  style: const TextStyle(color: AppColors.muted, fontSize: 13)),
              const Spacer(),
              Text(
                '${event.membersCount} / ${event.maxParticipants}',
                style: const TextStyle(fontWeight: FontWeight.w900),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(999),
            child: LinearProgressIndicator(
              value: event.fillPercent / 100,
              minHeight: 8,
              backgroundColor: const Color(0xFFE2E8F0),
              valueColor: AlwaysStoppedAnimation(
                event.isUrgent ? AppColors.accent : AppColors.primary,
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CreatorRatingSection extends StatelessWidget {
  const CreatorRatingSection({
    super.key,
    required this.rating,
    required this.ratings,
    this.myRating,
  });

  final CreatorRating? rating;
  final List<EventRating> ratings;
  final EventRating? myRating;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final summary = rating;

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              const Icon(Icons.star, color: AppColors.primary),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  summary != null && summary.count > 0
                      ? l10n.organizerRating(
                          summary.average?.toStringAsFixed(1) ?? '0',
                          summary.count,
                        )
                      : l10n.noRatingsYet,
                  style: const TextStyle(fontWeight: FontWeight.w700),
                ),
              ),
            ],
          ),
          if (myRating != null) ...[
            const SizedBox(height: 8),
            Text(
              l10n.yourRating(myRating!.stars),
              style: const TextStyle(
                fontSize: 12,
                color: AppColors.muted,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
          if (ratings.isNotEmpty) ...[
            const SizedBox(height: 14),
            Text(
              l10n.recentRatings,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 8),
            ...ratings.take(5).map((r) {
              return Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${r.stars}★',
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            r.raterName ?? '—',
                            style: const TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                          if (r.comment != null && r.comment!.isNotEmpty)
                            Text(
                              r.comment!,
                              style: const TextStyle(
                                fontSize: 12,
                                color: AppColors.muted,
                                height: 1.35,
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ),
              );
            }),
          ],
        ],
      ),
    );
  }
}

class EventJoinActions extends StatelessWidget {
  const EventJoinActions({
    super.key,
    required this.joinAction,
    required this.acting,
    required this.onJoin,
    required this.onLeave,
    required this.onCancelJoin,
    required this.onChat,
    this.onMembers,
    this.onViewTicket,
  });

  final String joinAction;
  final bool acting;
  final VoidCallback onJoin;
  final VoidCallback onLeave;
  final VoidCallback onCancelJoin;
  final VoidCallback onChat;
  final VoidCallback? onMembers;
  final VoidCallback? onViewTicket;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return switch (joinAction) {
      'request_to_join' => GradientButton(
          label: l10n.requestToJoin,
          isLoading: acting,
          onPressed: onJoin,
        ),
      'request_pending' => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            JoinStatusBanner(
              label: l10n.joinPending,
              color: AppColors.muted,
            ),
            const SizedBox(height: 8),
            TextButton(
              onPressed: acting ? null : onCancelJoin,
              child: Text(l10n.cancelJoin),
            ),
          ],
        ),
      'joined' => Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            JoinStatusBanner(
              label: l10n.joined,
              color: AppColors.success,
            ),
            if (onViewTicket != null) ...[
              const SizedBox(height: 12),
              GradientButton(
                label: l10n.viewTicket,
                onPressed: onViewTicket,
                icon: Icons.confirmation_number_outlined,
              ),
            ],
            const SizedBox(height: 12),
            GradientButton.indigo(
              label: l10n.chat,
              onPressed: onChat,
              icon: Icons.chat_bubble_outline,
            ),
            if (onMembers != null) ...[
              const SizedBox(height: 8),
              OutlinedButton(
                onPressed: onMembers,
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(vertical: 14),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(l10n.viewMembers),
              ),
            ],
            const SizedBox(height: 8),
            TextButton(
              onPressed: acting ? null : onLeave,
              child: Text(l10n.leave),
            ),
          ],
        ),
      'event_full' => JoinStatusBanner(
          label: l10n.eventFull,
          color: AppColors.accent,
        ),
      'request_rejected' => JoinStatusBanner(
          label: l10n.requestRejected,
          color: AppColors.danger,
        ),
      _ => JoinStatusBanner(
          label: l10n.notAvailable,
          color: AppColors.muted,
        ),
    };
  }
}

class JoinStatusBanner extends StatelessWidget {
  const JoinStatusBanner({
    super.key,
    required this.label,
    required this.color,
  });

  final String label;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 14, horizontal: 16),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Center(
        child: Text(
          label,
          style: TextStyle(
            fontWeight: FontWeight.w800,
            color: color,
          ),
        ),
      ),
    );
  }
}

class EventOwnerActions extends StatelessWidget {
  const EventOwnerActions({
    super.key,
    required this.event,
    required this.acting,
    required this.onPublish,
    required this.onCancel,
    required this.onComplete,
    required this.onRequests,
    required this.onMembers,
    this.onChat,
    this.onViewTicket,
  });

  final EventItem event;
  final bool acting;
  final VoidCallback onPublish;
  final VoidCallback onCancel;
  final VoidCallback onComplete;
  final VoidCallback onRequests;
  final VoidCallback onMembers;
  final VoidCallback? onChat;
  final VoidCallback? onViewTicket;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (event.isDraft)
          GradientButton(
            label: l10n.publish,
            isLoading: acting,
            onPressed: onPublish,
          ),
        if (event.isPublished) ...[
          if (onViewTicket != null) ...[
            GradientButton(
              label: l10n.viewTicket,
              onPressed: onViewTicket,
              icon: Icons.confirmation_number_outlined,
            ),
            const SizedBox(height: 8),
          ],
          GradientButton.indigo(
            label: l10n.viewRequests,
            onPressed: onRequests,
          ),
          const SizedBox(height: 8),
          GradientButton.indigo(
            label: l10n.viewMembers,
            onPressed: onMembers,
          ),
          if (onChat != null) ...[
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: onChat,
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(l10n.chat),
            ),
          ],
          const SizedBox(height: 8),
          OutlinedButton(
            onPressed: acting ? null : onComplete,
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 14),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(12),
              ),
            ),
            child: Text(l10n.completeEvent),
          ),
          const SizedBox(height: 8),
          TextButton(
            onPressed: acting ? null : onCancel,
            style: TextButton.styleFrom(foregroundColor: AppColors.danger),
            child: Text(l10n.cancelEvent),
          ),
        ],
        if (event.status == 'completed') ...[
          JoinStatusBanner(
            label: l10n.statusCompleted,
            color: AppColors.success,
          ),
          const SizedBox(height: 8),
          GradientButton.indigo(
            label: l10n.viewMembers,
            onPressed: onMembers,
          ),
          if (onChat != null) ...[
            const SizedBox(height: 8),
            OutlinedButton(
              onPressed: onChat,
              style: OutlinedButton.styleFrom(
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              child: Text(l10n.chat),
            ),
          ],
        ],
        if (event.status == 'cancelled')
          JoinStatusBanner(
            label: l10n.statusCancelled,
            color: AppColors.danger,
          ),
      ],
    );
  }
}

class EventMembersSheet extends StatelessWidget {
  const EventMembersSheet({super.key, required this.members});

  final List<EventMember> members;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: const EdgeInsets.all(20),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.viewMembers,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 16),
          ...members.map(
            (m) => ListTile(
              leading: CircleAvatar(
                backgroundColor: AppColors.primarySoft,
                child: Text(
                  m.name.isNotEmpty ? m.name[0].toUpperCase() : '?',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              title: Text(m.name),
              trailing: Text(
                m.role,
                style: const TextStyle(
                  color: AppColors.muted,
                  fontSize: 12,
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class RateEventSheet extends ConsumerStatefulWidget {
  const RateEventSheet({
    super.key,
    required this.eventId,
    required this.onRated,
    this.existingRating,
  });

  final int eventId;
  final EventRating? existingRating;
  final Future<void> Function() onRated;

  @override
  ConsumerState<RateEventSheet> createState() => _RateEventSheetState();
}

class _RateEventSheetState extends ConsumerState<RateEventSheet> {
  int _stars = 5;
  final _comment = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    if (widget.existingRating != null) {
      _stars = widget.existingRating!.stars;
      _comment.text = widget.existingRating!.comment ?? '';
    }
  }

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() => _submitting = true);
    final result = await ref.read(eventsRepositoryProvider).rate(
          widget.eventId,
          stars: _stars,
          comment: _comment.text.trim(),
        );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    Navigator.of(context).pop();
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).thankYouRating)),
    );
    await widget.onRated();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: EdgeInsets.only(
        left: 20,
        right: 20,
        top: 20,
        bottom: MediaQuery.of(context).viewInsets.bottom + 20,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.rateEvent,
            style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 16),
          StarRatingPicker(
            stars: _stars,
            onChanged: (v) => setState(() => _stars = v),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _comment,
            maxLines: 3,
            decoration: InputDecoration(
              labelText: l10n.comment,
              hintText: l10n.optional,
            ),
          ),
          const SizedBox(height: 20),
          GradientButton(
            label: l10n.save,
            isLoading: _submitting,
            onPressed: _submit,
          ),
        ],
      ),
    );
  }
}

class StarRatingPicker extends StatelessWidget {
  const StarRatingPicker({
    super.key,
    required this.stars,
    required this.onChanged,
  });

  final int stars;
  final ValueChanged<int> onChanged;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: List.generate(5, (i) {
        final value = i + 1;
        return IconButton(
          icon: Icon(
            value <= stars ? Icons.star : Icons.star_border,
            color: AppColors.accent,
            size: 36,
          ),
          onPressed: () => onChanged(value),
        );
      }),
    );
  }
}
