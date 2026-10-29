import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/utils/rtl.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart' hide TextDirection;

/// Compact premium event card for mobile (V2 glass language, reduced height).
class EventCard extends StatefulWidget {
  const EventCard({
    super.key,
    required this.event,
    required this.onView,
    this.onFavorite,
    this.onCancelJoin,
    this.statusBadge,
  });

  final EventItem event;
  final VoidCallback onView;
  final VoidCallback? onFavorite;
  final VoidCallback? onCancelJoin;
  final Widget? statusBadge;

  /// Controlled cover aspect for mobile lists (~16:9 on typical phone widths).
  static const double imageAspectRatio = 16 / 9;
  static const double imageMaxHeight = 140;

  @override
  State<EventCard> createState() => _EventCardState();
}

class _EventCardState extends State<EventCard> {
  bool _pressed = false;

  void _setPressed(bool v) {
    if (_pressed == v) return;
    setState(() => _pressed = v);
  }

  String _amountLabel(AppLocalizations l10n, EventItem event) {
    if (event.isFree) return l10n.free;
    final n = event.costPerPerson;
    final amount = n == null ? '0.00' : n.toStringAsFixed(2);
    return l10n.costPerPersonAmount(amount);
  }

  bool _showsJoinStatus(String? action) {
    return action == 'request_pending' ||
        action == 'owned' ||
        action == 'joined' ||
        action == 'request_rejected' ||
        action == 'event_full' ||
        action == 'sex_mismatch';
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final event = widget.event;
    final hasImage = event.imageUrl != null && event.imageUrl!.isNotEmpty;
    final categoryName = event.category?.name ?? '';
    final emoji = categoryEmoji(categoryName);
    final pct = event.fillPercent.round().clamp(0, 100);
    final schedule = event.startsAt != null
        ? DateFormat(
            'E · MMM d · h:mm a',
            locale,
          ).format(event.startsAt!.toLocal()).toUpperCase()
        : '';
    final priceChip = _amountLabel(l10n, event);
    final venueLine = event.placeLine;
    final distance = event.distanceKm;
    final action = event.joinAction;
    final showJoinStatus = _showsJoinStatus(action);
    final showCancel =
        action == 'request_pending' && widget.onCancelJoin != null;
    final hasAudience = event.sex != null && event.sex!.isNotEmpty;

    return GestureDetector(
      onTapDown: (_) => _setPressed(true),
      onTapUp: (_) {
        _setPressed(false);
        widget.onView();
      },
      onTapCancel: () => _setPressed(false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        curve: Curves.easeOut,
        // No 3D tilt — it inflated layout bounds on mobile and wasted vertical space.
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(24),
          gradient: const LinearGradient(
            begin: Alignment(-0.8, -1),
            end: Alignment(0.8, 1),
            colors: [Color(0xFFEEF2FF), Color(0xFFE8EDFF), Color(0xFFDDE4FF)],
          ),
          boxShadow: [
            BoxShadow(
              color: const Color(0xFF312E81).withValues(alpha: 0.12),
              blurRadius: _pressed ? 16 : 12,
              offset: Offset(0, _pressed ? 10 : 6),
              spreadRadius: -4,
            ),
          ],
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              LayoutBuilder(
                builder: (context, constraints) {
                  final width = constraints.maxWidth.isFinite
                      ? constraints.maxWidth
                      : MediaQuery.sizeOf(context).width;
                  final ideal =
                      width / EventCard.imageAspectRatio;
                  final imageHeight =
                      ideal.clamp(120.0, EventCard.imageMaxHeight);
                  return SizedBox(
                    height: imageHeight,
                    width: width,
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        if (hasImage)
                          AppNetworkImage(
                            url: event.imageUrl!,
                            // List thumbs: decode for phone width, not full cover art.
                            logicalWidth: 390,
                            logicalHeight: imageHeight,
                            maxCacheWidth: 720,
                            maxCacheHeight: 360,
                            fit: BoxFit.cover,
                          )
                        else
                          const ColoredBox(color: AppColors.primarySoft),
                        const DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                Color(0x9919213D),
                                Colors.transparent,
                              ],
                              stops: [0.0, 0.55],
                            ),
                          ),
                        ),
                        PositionedDirectional(
                          top: 10,
                          start: 10,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              if (widget.statusBadge != null) ...[
                                widget.statusBadge!,
                                const SizedBox(height: 6),
                              ],
                              if (categoryName.isNotEmpty)
                                _Chip(
                                  label: '$emoji $categoryName'.trim(),
                                  filled: false,
                                ),
                              if (categoryName.isNotEmpty)
                                const SizedBox(height: 6),
                              _Chip(
                                label: priceChip,
                                filled: true,
                                free: event.isFree,
                                key: const ValueKey('event-card-price-chip'),
                              ),
                            ],
                          ),
                        ),
                        if (widget.onFavorite != null)
                          PositionedDirectional(
                            top: 10,
                            end: 10,
                            child: Material(
                              color: Colors.white.withValues(alpha: 0.95),
                              shape: const CircleBorder(),
                              child: InkWell(
                                customBorder: const CircleBorder(),
                                onTap: widget.onFavorite,
                                child: Padding(
                                  padding: const EdgeInsets.all(8),
                                  child: Icon(
                                    event.isFavorited
                                        ? Icons.bookmark
                                        : Icons.bookmark_border,
                                    size: 18,
                                    color: event.isFavorited
                                        ? AppColors.accentAmber
                                        : const Color(0xFF64748B),
                                  ),
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
                  );
                },
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      event.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      textDirection: bidiTextDirection(event.title),
                      style: const TextStyle(
                        color: Color(0xFF312E81),
                        fontWeight: FontWeight.w900,
                        fontSize: 15,
                        height: 1.2,
                        letterSpacing: -0.2,
                      ),
                    ),
                    if (schedule.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Text(
                        schedule,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: AppColors.primary.withValues(alpha: 0.9),
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          letterSpacing: 0.3,
                        ),
                      ),
                    ],
                    if (venueLine.isNotEmpty) ...[
                      const SizedBox(height: 3),
                      Text(
                        venueLine,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: AppColors.primaryDeep.withValues(alpha: 0.75),
                          fontSize: 12,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    if (distance != null || hasAudience) ...[
                      const SizedBox(height: 3),
                      Text(
                        [
                          if (distance != null)
                            event.distancePrecision == 'city_level'
                                ? '≈${distance.toStringAsFixed(distance >= 10 ? 0 : 1)} km · city-level'
                                : event.distancePrecision == 'exact'
                                ? '${distance.toStringAsFixed(distance >= 10 ? 0 : 1)} km'
                                : '~${distance.toStringAsFixed(distance >= 10 ? 0 : 1)} km',
                          if (hasAudience)
                            '${l10n.eventSex}: ${switch (event.sex) {
                              'male' => l10n.sexMale,
                              'female' => l10n.sexFemale,
                              _ => l10n.sexMix,
                            }}',
                        ].join(' · '),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                          color: AppColors.primary.withValues(alpha: 0.8),
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        Text(
                          l10n.members,
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF64748B),
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const Spacer(),
                        Text(
                          '${event.membersCount} / ${event.maxParticipants}',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: AppColors.text,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Text(
                          '$pct%',
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: AppColors.primaryDeep,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 5),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(999),
                      child: SizedBox(
                        height: 4,
                        child: LinearProgressIndicator(
                          value: (event.fillPercent / 100).clamp(0.0, 1.0),
                          backgroundColor: const Color(0xFFE2E8F0),
                          valueColor: AlwaysStoppedAnimation<Color>(
                            event.isUrgent
                                ? AppColors.accent
                                : const Color(0xFF6366F1),
                          ),
                        ),
                      ),
                    ),
                    if (showJoinStatus) ...[
                      const SizedBox(height: 6),
                      _JoinStatusStrip(
                        action: action!,
                        onCancelJoin: showCancel ? widget.onCancelJoin : null,
                      ),
                    ],
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        const Spacer(),
                        Text(
                          l10n.viewEvent,
                          style: const TextStyle(
                            color: Color(0xFFEA580C),
                            fontWeight: FontWeight.w800,
                            fontSize: 12,
                          ),
                        ),
                        Icon(
                          directionalChevronForward(context),
                          size: 16,
                          color: const Color(0xFFEA580C),
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

class _Chip extends StatelessWidget {
  const _Chip({
    super.key,
    required this.label,
    required this.filled,
    this.free = false,
  });

  final String label;
  final bool filled;
  final bool free;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
      decoration: BoxDecoration(
        color: filled ? null : Colors.white.withValues(alpha: 0.92),
        gradient: filled
            ? (free ? AppColors.emeraldGradient : AppColors.orangeGradient)
            : null,
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.1),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Text(
        label,
        style: TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: filled ? Colors.white : AppColors.text,
        ),
      ),
    );
  }
}

class _JoinStatusStrip extends StatelessWidget {
  const _JoinStatusStrip({required this.action, this.onCancelJoin});

  final String action;
  final VoidCallback? onCancelJoin;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    final (label, bg, fg, border) = switch (action) {
      'owned' => (
        l10n.yourOwnEvent,
        const Color(0xFFEEF2FF),
        AppColors.primaryDeep,
        Colors.transparent,
      ),
      'joined' => (
        l10n.joined,
        const Color(0xFFECFDF5),
        const Color(0xFF047857),
        Colors.transparent,
      ),
      'request_rejected' => (
        l10n.requestRejected,
        const Color(0xFFFFFBEB),
        const Color(0xFFB45309),
        const Color(0xFFFDE68A),
      ),
      'event_full' => (
        l10n.eventFull,
        const Color(0xFFF8FAFC),
        AppColors.muted,
        const Color(0xFFE2E8F0),
      ),
      'sex_mismatch' => (
        l10n.eventSexMismatch,
        const Color(0xFFFFFBEB),
        const Color(0xFFB45309),
        const Color(0xFFFDE68A),
      ),
      _ => (
        l10n.requestPending,
        const Color(0xFFF8FAFC),
        AppColors.muted,
        const Color(0xFFE2E8F0),
      ),
    };

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: border),
          ),
          child: Text(
            label,
            textAlign: TextAlign.center,
            maxLines: 2,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.w700,
              color: fg,
            ),
          ),
        ),
        if (onCancelJoin != null) ...[
          const SizedBox(height: 6),
          CancelStopButton(
            label: l10n.cancelJoin,
            size: CancelStopSize.sm,
            onPressed: onCancelJoin,
          ),
        ],
      ],
    );
  }
}
