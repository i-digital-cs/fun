import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/core/widgets/espiral_glow.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';
import 'package:fun_mobile/features/events/presentation/event_detail_controller.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_place_map.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:fun_mobile/core/utils/safe_url.dart';

class EventDetailPage extends ConsumerStatefulWidget {
  const EventDetailPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventDetailPage> createState() => _EventDetailPageState();
}

class _EventDetailPageState extends ConsumerState<EventDetailPage> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(eventDetailControllerProvider(widget.eventId).notifier).load();
    });
  }

  EventDetailController get _ctrl =>
      ref.read(eventDetailControllerProvider(widget.eventId).notifier);

  Future<void> _handle(EventDetailMutation result) async {
    if (!mounted) return;
    final l10n = AppLocalizations.of(context);
    switch (result.gate) {
      case EventDetailGate.needLogin:
        context.push('/login');
        return;
      case EventDetailGate.needIdentity:
        ScaffoldMessenger.of(context)
            .showSnackBar(SnackBar(content: Text(l10n.notActiveAccount)));
        context.push('/identity');
        return;
      case EventDetailGate.none:
        break;
    }
    if (result.error != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.error!)));
      return;
    }
    if (result.successMessageKey == 'eventPublished') {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.eventPublished)));
    } else if (result.successMessageKey == 'userBlocked') {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.userBlocked)));
    }
  }

  Future<void> _toggleFavorite() async {
    await _handle(await _ctrl.toggleFavorite());
  }

  Future<void> _join() async {
    await _handle(await _ctrl.join());
  }

  Future<void> _leave() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(context, message: l10n.confirmLeave);
    if (confirm != true || !mounted) return;
    await _handle(await _ctrl.leave());
  }

  Future<void> _cancelJoinRequest() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmCancelJoin,
    );
    if (confirm != true || !mounted) return;
    await _handle(await _ctrl.cancelJoinRequest());
  }

  Future<void> _publish() async {
    await _handle(await _ctrl.publish());
  }

  Future<void> _cancelEvent() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(context, message: l10n.confirmCancel);
    if (confirm != true || !mounted) return;
    await _handle(await _ctrl.cancelEvent());
  }

  Future<void> _completeEvent() async {
    await _handle(await _ctrl.completeEvent());
  }

  Future<void> _showRateSheet() async {
    final detail = ref
        .read(eventDetailControllerProvider(widget.eventId))
        .detail;
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => RateEventSheet(
        eventId: widget.eventId,
        existingRating: detail?.myRating,
        onRated: () => _ctrl.load(),
      ),
    );
  }

  Future<void> _blockCreator() async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmBlock(l10n.blockCreator),
    );
    if (confirm != true || !mounted) return;
    await _handle(await _ctrl.blockCreator());
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final state = ref.watch(eventDetailControllerProvider(widget.eventId));
    final detail = state.detail;
    final isOwner = _ctrl.isOwner;
    final canEdit = _ctrl.canEdit;
    final canParticipate = _ctrl.canParticipate;

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
              onPressed: state.favoriting ? null : _toggleFavorite,
            ),
          if (detail != null && !isOwner) ...[
            IconButton(
              icon: const Icon(Icons.flag_outlined),
              tooltip: l10n.report,
              onPressed: () => showReportSheet(
                context,
                reportableType: 'event',
                reportableId: widget.eventId,
              ),
            ),
            if (canParticipate && detail.event.createdBy != null)
              IconButton(
                icon: const Icon(Icons.block),
                tooltip: l10n.blockCreator,
                onPressed: _blockCreator,
              ),
          ],
          if (canEdit)
            IconButton(
              icon: const Icon(Icons.edit_outlined),
              tooltip: l10n.editEvent,
              onPressed: () => context.push('/events/${widget.eventId}/edit'),
            ),
        ],
      ),
      body: AsyncBody(
        isLoading: state.isLoading,
        error: state.error,
        onRetry: () => _ctrl.load(),
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
                    ratings: state.ratings,
                    myRating: detail.myRating,
                    creator: detail.event.creator,
                  ),
                  const SizedBox(height: 20),
                  if (isOwner)
                    EventOwnerActions(
                      event: detail.event,
                      acting: state.acting,
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
                  else if (!canParticipate)
                    GradientButton.indigo(
                      label: l10n.notActiveAccount,
                      onPressed: () => context.push('/identity'),
                      icon: Icons.verified_user_outlined,
                    )
                  else
                    EventJoinActions(
                      joinAction: detail.joinAction,
                      acting: state.acting,
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
        child: Stack(
          fit: StackFit.expand,
          children: [
            if (event.imageUrl != null && event.imageUrl!.isNotEmpty)
              LayoutBuilder(
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
            else
              const ColoredBox(
                color: AppColors.primarySoft,
                child: Icon(
                  Icons.image_outlined,
                  color: AppColors.primary,
                  size: 48,
                ),
              ),
            const EspiralAnchorCover(
              tone: EspiralTone.indigo,
              size: EspiralSize.md,
            ),
          ],
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
        ? DateFormat(
            'EEEE, MMM d · h:mm a',
            locale,
          ).format(event.startsAt!.toLocal())
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
            textDirection: bidiTextDirection(event.title),
            style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800),
          ),
          if (schedule != null) ...[
            const SizedBox(height: 12),
            Row(
              children: [
                const Icon(
                  Icons.calendar_today_outlined,
                  size: 16,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    schedule,
                    style: const TextStyle(color: AppColors.muted),
                  ),
                ),
              ],
            ),
          ],
          const SizedBox(height: 6),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Icon(Icons.location_on, size: 16, color: AppColors.muted),
              const SizedBox(width: 8),
              Expanded(
                child: Text(
                  event.placeLine.isEmpty
                      ? l10n.locationTba
                      : event.placeLine,
                  style: const TextStyle(color: AppColors.muted),
                ),
              ),
            ],
          ),
          if ((event.mapsOpenUrl != null && event.mapsOpenUrl!.isNotEmpty) ||
              event.hasPlacePin) ...[
            const SizedBox(height: 8),
            Align(
              alignment: AlignmentDirectional.centerStart,
              child: TextButton.icon(
                onPressed: () async {
                  await launchSafeExternalUrl(event.openMapsUrl);
                },
                icon: const Icon(Icons.map_outlined, size: 18),
                label: Text(l10n.openInMaps),
              ),
            ),
          ],
          if (event.creator != null) ...[
            const SizedBox(height: 14),
            EventHostCard(creator: event.creator!),
          ],
          if (event.hasPlacePin &&
              event.latitude != null &&
              event.longitude != null) ...[
            const SizedBox(height: 12),
            EventPlaceMapViewer(
              latitude: event.latitude!,
              longitude: event.longitude!,
              onTap: () => launchSafeExternalUrl(
                MapsUrlParser.googleOpenUrl(
                  event.latitude!,
                  event.longitude!,
                ),
              ),
            ),
          ],
          if (event.city != null &&
              event.placeLine.isNotEmpty &&
              !event.placeLine
                  .toLowerCase()
                  .contains(event.city!.name.toLowerCase())) ...[
            const SizedBox(height: 4),
            Text(
              event.city!.name,
              style: const TextStyle(color: AppColors.muted, fontSize: 12),
            ),
          ],
          if (event.sex != null && event.sex!.isNotEmpty) ...[
            const SizedBox(height: 10),
            Align(
              alignment: AlignmentDirectional.centerStart,
              child: Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: AppColors.primary.withValues(alpha: 0.2),
                  ),
                ),
                child: Text(
                  '${l10n.eventSex}: ${switch (event.sex) {
                    'male' => l10n.sexMale,
                    'female' => l10n.sexFemale,
                    _ => l10n.sexMix,
                  }}',
                  style: const TextStyle(
                    color: AppColors.primary,
                    fontWeight: FontWeight.w700,
                    fontSize: 13,
                  ),
                ),
              ),
            ),
          ],
          if (event.city != null && event.placeLine.isEmpty) ...[
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
          if (event.requirements != null && event.requirements!.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              l10n.requirements,
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            const SizedBox(height: 4),
            Text(
              event.requirements!,
              style: const TextStyle(color: AppColors.muted, height: 1.4),
            ),
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
              Text(
                l10n.members,
                style: const TextStyle(color: AppColors.muted, fontSize: 13),
              ),
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

class EventHostCard extends StatelessWidget {
  const EventHostCard({super.key, required this.creator});

  final EventCreator creator;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final publicId = creator.publicId?.trim();
    final photo = creator.profileImagePath?.trim();

    return Material(
      color: AppColors.primarySoft.withValues(alpha: 0.55),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: publicId != null && publicId.isNotEmpty
            ? () => context.push('/u/$publicId')
            : null,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.primary.withValues(alpha: 0.18)),
          ),
          child: Row(
            children: [
              CircleAvatar(
                radius: 20,
                backgroundColor: AppColors.primary,
                child: photo != null && photo.isNotEmpty
                    ? ClipOval(
                        child: SizedBox(
                          width: 40,
                          height: 40,
                          child: AppNetworkImage(
                            url: photo,
                            logicalWidth: 40,
                            logicalHeight: 40,
                            maxCacheWidth: 120,
                          ),
                        ),
                      )
                    : Text(
                        creator.displayInitials,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w900,
                          fontSize: 12,
                        ),
                      ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      creator.name,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 14,
                      ),
                    ),
                    Text(
                      l10n.participationHost,
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 0.4,
                        color: AppColors.primary,
                      ),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(999),
                  border: Border.all(
                    color: AppColors.primary.withValues(alpha: 0.2),
                  ),
                ),
                child: Text(
                  l10n.participationHost,
                  style: const TextStyle(
                    fontSize: 10,
                    fontWeight: FontWeight.w900,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ],
          ),
        ),
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
    this.creator,
  });

  final CreatorRating? rating;
  final List<EventRating> ratings;
  final EventRating? myRating;
  final EventCreator? creator;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final summary = rating;
    final publicId = creator?.publicId?.trim();

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
          Text(
            l10n.participationHost,
            style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 15),
          ),
          if (creator != null) ...[
            const SizedBox(height: 10),
            InkWell(
              onTap: publicId != null && publicId.isNotEmpty
                  ? () => context.push('/u/$publicId')
                  : null,
              borderRadius: BorderRadius.circular(12),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: AppColors.primary,
                    child: Text(
                      creator!.displayInitials,
                      style: const TextStyle(
                        color: Colors.white,
                        fontWeight: FontWeight.w900,
                        fontSize: 11,
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          creator!.name,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        Text(
                          publicId != null && publicId.isNotEmpty
                              ? l10n.publicProfile
                              : l10n.participationHost,
                          style: const TextStyle(
                            fontSize: 11,
                            color: AppColors.primary,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
          const SizedBox(height: 10),
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
              style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800),
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
          JoinStatusBanner(label: l10n.joinPending, color: AppColors.muted),
          const SizedBox(height: 10),
          CancelStopButton(
            label: l10n.cancelJoin,
            size: CancelStopSize.block,
            onPressed: acting ? null : onCancelJoin,
          ),
        ],
      ),
      'joined' => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          JoinStatusBanner(label: l10n.joined, color: AppColors.success),
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
          const SizedBox(height: 10),
          CancelStopButton(
            label: l10n.leaveEvent,
            size: CancelStopSize.block,
            onPressed: acting ? null : onLeave,
          ),
        ],
      ),
      'owned' => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          JoinStatusBanner(label: l10n.yourOwnEvent, color: AppColors.primary),
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
        ],
      ),
      'event_full' => JoinStatusBanner(
        label: l10n.eventFull,
        color: AppColors.accent,
      ),
      'sex_mismatch' => JoinStatusBanner(
        label: l10n.eventSexMismatch,
        color: const Color(0xFFB45309),
      ),
      'request_rejected' => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          JoinStatusBanner(
            label: l10n.requestRejected,
            color: const Color(0xFFB45309),
          ),
          const SizedBox(height: 12),
          GradientButton(
            label: l10n.requestToJoin,
            isLoading: acting,
            onPressed: onJoin,
          ),
        ],
      ),
      _ => JoinStatusBanner(label: l10n.notAvailable, color: AppColors.muted),
    };
  }
}

class JoinStatusBanner extends StatelessWidget {
  const JoinStatusBanner({super.key, required this.label, required this.color});

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
          style: TextStyle(fontWeight: FontWeight.w800, color: color),
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
          GradientButton.indigo(label: l10n.viewMembers, onPressed: onMembers),
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
          CancelStopButton(
            label: l10n.cancelEvent,
            size: CancelStopSize.block,
            onPressed: acting ? null : onCancel,
          ),
        ],
        if (event.status == 'completed') ...[
          JoinStatusBanner(
            label: l10n.statusCompleted,
            color: AppColors.success,
          ),
          const SizedBox(height: 8),
          GradientButton.indigo(label: l10n.viewMembers, onPressed: onMembers),
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
                style: const TextStyle(color: AppColors.muted, fontSize: 12),
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
    final result = await ref
        .read(eventsRepositoryProvider)
        .rate(widget.eventId, stars: _stars, comment: _comment.text.trim());
    if (!mounted) return;
    setState(() => _submitting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
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
