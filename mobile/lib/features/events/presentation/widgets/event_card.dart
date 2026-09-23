import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart' hide TextDirection;

/// Glass event card (Blade `.event-3d` without logo rings).
class EventCard extends StatefulWidget {
  const EventCard({
    super.key,
    required this.event,
    required this.onView,
    this.onFavorite,
    this.statusBadge,
  });

  final EventItem event;
  final VoidCallback onView;
  final VoidCallback? onFavorite;

  /// Optional chip stacked above category (e.g. Published) — never overlays text.
  final Widget? statusBadge;

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
    // Match web: "25.00 EGP / person"
    final amount = n == null ? '0.00' : n.toStringAsFixed(2);
    return l10n.costPerPersonAmount(amount);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final event = widget.event;
    final isRtl = Directionality.of(context) == TextDirection.rtl;
    final hasImage = event.imageUrl != null && event.imageUrl!.isNotEmpty;
    final categoryName = event.category?.name ?? '';
    final emoji = categoryEmoji(categoryName);
    final pct = event.fillPercent.round().clamp(0, 100);
    final schedule = event.startsAt != null
        ? DateFormat('E · MMM d · h:mm a', locale)
            .format(event.startsAt!.toLocal())
            .toUpperCase()
        : '';
    final priceChip = _amountLabel(l10n, event);
    final currencyPill = event.isFree ? '✓' : l10n.currencyShort;
    final venueLine = event.venue.trim();
    final tilt = _pressed ? 0.28 : 0.0;

    return GestureDetector(
      onTapDown: (_) => _setPressed(true),
      onTapUp: (_) {
        _setPressed(false);
        widget.onView();
      },
      onTapCancel: () => _setPressed(false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 480),
        curve: Curves.easeInOut,
        transformAlignment: Alignment.center,
        height: 360,
        transform: Matrix4.identity()
          ..setEntry(3, 2, 0.001)
          ..rotateX(tilt)
          ..rotateY(isRtl ? -tilt : tilt),
        child: Container(
          height: 360,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(44),
            gradient: const LinearGradient(
              begin: Alignment(-0.8, -1),
              end: Alignment(0.8, 1),
              colors: [
                Color(0xFFEEF2FF),
                Color(0xFFE0E7FF),
                Color(0xFFC7D2FE),
              ],
              stops: [0.0, 0.4, 1.0],
            ),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF312E81)
                    .withValues(alpha: _pressed ? 0.32 : 0.0),
                blurRadius: 36,
                offset: const Offset(28, 48),
                spreadRadius: -36,
              ),
              BoxShadow(
                color: const Color(0xFF312E81)
                    .withValues(alpha: _pressed ? 0.14 : 0.18),
                blurRadius: _pressed ? 28 : 24,
                offset: Offset(0, _pressed ? 22 : 18),
                spreadRadius: -6,
              ),
            ],
          ),
          // Web: do not overflow:hidden on the card (clips logo rings / 3D).
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              // Media 148px
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                height: 148,
                child: ClipRRect(
                  borderRadius: const BorderRadius.vertical(
                    top: Radius.circular(44),
                  ),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      if (hasImage)
                        AppNetworkImage(
                          url: event.imageUrl!,
                          logicalWidth: 420,
                          logicalHeight: 148,
                          maxCacheWidth: 900,
                          maxCacheHeight: 400,
                        )
                      else
                        const ColoredBox(color: AppColors.primarySoft),
                      const DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.bottomCenter,
                            end: Alignment.topCenter,
                            colors: [
                              Color(0xF2EEF2FF),
                              Colors.transparent,
                            ],
                            stops: [0.0, 0.45],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),

                // Glass — CSS border-start-end-radius: 100%
                Positioned(
                  left: 8,
                  right: 8,
                  top: 96,
                  bottom: 8,
                  child: IgnorePointer(
                    child: LayoutBuilder(
                      builder: (context, box) {
                        final w = box.maxWidth;
                        final h = box.maxHeight;
                        // Deep top-end scoop like CSS 100%, but clamped so the
                        // cover photo still reads on the start side (web look).
                        final endRadius = Radius.elliptical(
                          (w * 0.55).clamp(100.0, 200.0),
                          (h * 0.40).clamp(90.0, 140.0),
                        );
                        return DecoratedBox(
                          decoration: BoxDecoration(
                            borderRadius: BorderRadius.only(
                              topLeft: isRtl
                                  ? endRadius
                                  : const Radius.circular(38),
                              topRight: isRtl
                                  ? const Radius.circular(38)
                                  : endRadius,
                              bottomLeft: const Radius.circular(38),
                              bottomRight: const Radius.circular(38),
                            ),
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                Colors.white.withValues(alpha: 0.42),
                                Colors.white.withValues(alpha: 0.90),
                              ],
                            ),
                            border: Border(
                              left: isRtl
                                  ? BorderSide.none
                                  : BorderSide(
                                      color: Colors.white.withValues(alpha: 0.95),
                                    ),
                              right: isRtl
                                  ? BorderSide(
                                      color: Colors.white.withValues(alpha: 0.95),
                                    )
                                  : BorderSide.none,
                              bottom: BorderSide(
                                color: Colors.white.withValues(alpha: 0.95),
                              ),
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ),

                // Category + price
                PositionedDirectional(
                  top: 14,
                  start: 14,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (widget.statusBadge != null) ...[
                        widget.statusBadge!,
                        const SizedBox(height: 6),
                      ],
                      if (categoryName.isNotEmpty)
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 5,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.92),
                            borderRadius: BorderRadius.circular(999),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withValues(alpha: 0.12),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Text(
                            '$emoji $categoryName'.trim(),
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                        ),
                      if (categoryName.isNotEmpty) const SizedBox(height: 6),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 5,
                        ),
                        decoration: BoxDecoration(
                          gradient: event.isFree
                              ? AppColors.emeraldGradient
                              : AppColors.orangeGradient,
                          borderRadius: BorderRadius.circular(999),
                        ),
                        child: Text(
                          priceChip,
                          key: const ValueKey('event-card-price-chip'),
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),

                // Favorite — top-end (logo rings removed)
                if (widget.onFavorite != null)
                  PositionedDirectional(
                    top: 14,
                    end: 14,
                    child: Material(
                      color: Colors.white.withValues(alpha: 0.95),
                      shape: const CircleBorder(),
                      elevation: 2,
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

                // Body
                Positioned(
                  left: 0,
                  right: 0,
                  top: 148,
                  bottom: 52,
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          event.title,
                          maxLines: 2,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            color: Color(0xFF312E81),
                            fontWeight: FontWeight.w900,
                            fontSize: 17,
                            height: 1.3,
                            letterSpacing: -0.3,
                          ),
                        ),
                        if (schedule.isNotEmpty) ...[
                          const SizedBox(height: 7),
                          Text(
                            schedule,
                            style: TextStyle(
                              color: const Color(0xFF4F46E5)
                                  .withValues(alpha: 0.85),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.5,
                            ),
                          ),
                        ],
                        if (venueLine.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            venueLine,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: TextStyle(
                              color: const Color(0xFF4338CA)
                                  .withValues(alpha: 0.75),
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                        const SizedBox(height: 12),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 9,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white.withValues(alpha: 0.72),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(
                              color: Colors.white.withValues(alpha: 0.9),
                            ),
                          ),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Text(
                                    l10n.members,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: Color(0xFF64748B),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  const Spacer(),
                                  Text(
                                    '${event.membersCount} / ${event.maxParticipants}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w900,
                                      color: Color(0xFF0F172A),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(999),
                                child: SizedBox(
                                  height: 6,
                                  child: Stack(
                                    fit: StackFit.expand,
                                    children: [
                                      const ColoredBox(
                                        color: Color(0xFFE2E8F0),
                                      ),
                                      FractionallySizedBox(
                                        alignment: isRtl
                                            ? Alignment.centerRight
                                            : Alignment.centerLeft,
                                        widthFactor: (event.fillPercent / 100)
                                            .clamp(0.0, 1.0),
                                        child: DecoratedBox(
                                          decoration: BoxDecoration(
                                            gradient: LinearGradient(
                                              colors: event.isUrgent
                                                  ? const [
                                                      Color(0xFFF97316),
                                                      Color(0xFFF59E0B),
                                                    ]
                                                  : const [
                                                      Color(0xFF6366F1),
                                                      Color(0xFF7C3AED),
                                                    ],
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // Bottom pills + view
                Positioned(
                  left: 14,
                  right: 14,
                  bottom: 14,
                  child: Row(
                    children: [
                      _Pill(
                        label:
                            '${event.membersCount}/${event.maxParticipants}',
                        wide: true,
                        pressed: _pressed,
                      ),
                      const SizedBox(width: 8),
                      _Pill(label: currencyPill, pressed: _pressed),
                      const SizedBox(width: 8),
                      _Pill(label: '$pct%', pressed: _pressed),
                      const Spacer(),
                      Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Text(
                            l10n.viewEvent,
                            style: const TextStyle(
                              color: Color(0xFFEA580C),
                              fontWeight: FontWeight.w800,
                              fontSize: 12,
                            ),
                          ),
                          Icon(
                            isRtl
                                ? Icons.chevron_left
                                : Icons.chevron_right,
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

class _Pill extends StatelessWidget {
  const _Pill({
    required this.label,
    required this.pressed,
    this.wide = false,
  });

  final String label;
  final bool pressed;
  final bool wide;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      transform: Matrix4.translationValues(0, 0, pressed ? 50 : 0),
      constraints: BoxConstraints(minWidth: wide ? 42 : 30, minHeight: 30),
      padding: EdgeInsets.symmetric(horizontal: wide ? 9 : 6),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF312E81)
                .withValues(alpha: pressed ? 0.22 : 0.3),
            blurRadius: pressed ? 12 : 8,
            offset: Offset(pressed ? -5 : 0, pressed ? 14 : 5),
          ),
        ],
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: Color(0xFF4338CA),
        ),
      ),
    );
  }
}
