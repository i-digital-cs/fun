import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart' hide TextDirection;

/// Mirrors Blade `x-events.card` / `.event-3d` (glass + logo rings).
class EventCard extends StatefulWidget {
  const EventCard({
    super.key,
    required this.event,
    required this.onView,
    this.onFavorite,
  });

  final EventItem event;
  final VoidCallback onView;
  final VoidCallback? onFavorite;

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
    final amount = n == null ? '0' : n.toStringAsFixed(1);
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
    // Blade shows venue only (city is often already in the string).
    final venueLine = event.venue.trim();

    // Web: rotate3d(1, 1, 0, 22deg) on hover
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
          // Do not clip — logo rings + 3D depth must overflow card edge.
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              // .event-3d__media
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

              // .event-3d__glass — deep top-end radius like CSS 100%
              Positioned(
                left: 8,
                right: 8,
                top: 96,
                bottom: 8,
                child: IgnorePointer(
                  child: DecoratedBox(
                    decoration: BoxDecoration(
                      borderRadius: BorderRadius.only(
                        topLeft: Radius.circular(isRtl ? 180 : 38),
                        topRight: Radius.circular(isRtl ? 38 : 180),
                        bottomLeft: const Radius.circular(38),
                        bottomRight: const Radius.circular(38),
                      ),
                      gradient: LinearGradient(
                        begin: Alignment.bottomCenter,
                        end: Alignment.topCenter,
                        colors: [
                          Colors.white.withValues(alpha: 0.42),
                          Colors.white.withValues(alpha: 0.88),
                        ],
                      ),
                      border: Border(
                        left: isRtl
                            ? BorderSide.none
                            : const BorderSide(color: Colors.white),
                        right: isRtl
                            ? const BorderSide(color: Colors.white)
                            : BorderSide.none,
                        bottom: const BorderSide(color: Colors.white),
                      ),
                    ),
                  ),
                ),
              ),

              // .event-3d__logo — above glass so rings stay visible
              Positioned(
                top: 0,
                right: isRtl ? null : 0,
                left: isRtl ? 0 : null,
                child: _LogoStack(
                  imageUrl: hasImage ? event.imageUrl : null,
                  emoji: emoji,
                  pressed: _pressed,
                  isRtl: isRtl,
                ),
              ),

              // .event-3d__chip + price
              PositionedDirectional(
                top: 14,
                start: 14,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
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
                    const SizedBox(height: 6),
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

              // .event-3d__favorite — top: 7.6rem ≈ 122
              if (widget.onFavorite != null)
                PositionedDirectional(
                  top: 122,
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

              // .event-3d__body
              Positioned(
                left: 0,
                right: 0,
                top: 148,
                bottom: 52,
                child: Padding(
                  padding: EdgeInsets.fromLTRB(
                    20,
                    10,
                    isRtl ? 20 : 72,
                    0,
                  ),
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
                                    const ColoredBox(color: Color(0xFFE2E8F0)),
                                    FractionallySizedBox(
                                      alignment: Alignment.centerLeft,
                                      widthFactor:
                                          (event.fillPercent / 100).clamp(0.0, 1.0),
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

              // .event-3d__bottom
              Positioned(
                left: 14,
                right: 14,
                bottom: 14,
                child: Row(
                  children: [
                    _Pill(
                      label: '${event.membersCount}/${event.maxParticipants}',
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
                          isRtl ? Icons.chevron_left : Icons.chevron_right,
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

class _LogoStack extends StatelessWidget {
  const _LogoStack({
    required this.emoji,
    required this.pressed,
    required this.isRtl,
    this.imageUrl,
  });

  final String emoji;
  final bool pressed;
  final bool isRtl;
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    // CSS .event-3d__circle--1…5
    final sizes = <double>[170, 140, 110, 80, 50];
    final tops = <double>[8, 10, 17, 23, 30];
    final ends = <double>[8, 10, 17, 23, 30];
    final zRest = <double>[20, 40, 60, 80, 100];
    final zHover = <double>[20, 60, 80, 100, 120];
    final blurSigmas = <double>[5, 1, 5, 5, 0];

    return SizedBox(
      width: 178,
      height: 178,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          for (var i = 0; i < 5; i++)
            Positioned(
              top: tops[i],
              right: isRtl ? null : ends[i],
              left: isRtl ? ends[i] : null,
              child: AnimatedContainer(
                duration: Duration(milliseconds: 500 + (i * 200)),
                curve: Curves.easeInOut,
                width: sizes[i],
                height: sizes[i],
                transformAlignment: Alignment.center,
                transform: Matrix4.identity()
                  ..setEntry(3, 2, 0.001)
                  ..translateByDouble(
                    0,
                    0,
                    pressed ? zHover[i] : zRest[i],
                    1,
                  ),
                child: i == 4
                    ? _CenterThumb(
                        imageUrl: imageUrl,
                        emoji: emoji,
                        isRtl: isRtl,
                      )
                    : _GlassRing(
                        blurSigma: blurSigmas[i],
                        isRtl: isRtl,
                      ),
              ),
            ),
        ],
      ),
    );
  }
}

class _GlassRing extends StatelessWidget {
  const _GlassRing({
    required this.blurSigma,
    required this.isRtl,
  });

  final double blurSigma;
  final bool isRtl;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF64646F).withValues(alpha: 0.22),
            blurRadius: 20,
            offset: Offset(isRtl ? 10 : -10, 10),
          ),
        ],
      ),
      child: ClipOval(
        child: BackdropFilter(
          filter: ImageFilter.blur(sigmaX: blurSigma, sigmaY: blurSigma),
          child: DecoratedBox(
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: Colors.white.withValues(alpha: 0.28),
              border: Border.all(
                color: Colors.white.withValues(alpha: 0.55),
                width: 1.2,
              ),
            ),
            child: const SizedBox.expand(),
          ),
        ),
      ),
    );
  }
}

class _CenterThumb extends StatelessWidget {
  const _CenterThumb({
    required this.emoji,
    required this.isRtl,
    this.imageUrl,
  });

  final String emoji;
  final bool isRtl;
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    return DecoratedBox(
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: const Color(0xFF4F46E5).withValues(alpha: 0.55),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF64646F).withValues(alpha: 0.28),
            blurRadius: 18,
            offset: Offset(isRtl ? 8 : -8, 12),
          ),
          BoxShadow(
            color: const Color(0xFF312E81).withValues(alpha: 0.35),
            blurRadius: 16,
            offset: const Offset(0, 8),
            spreadRadius: -6,
          ),
        ],
      ),
      child: ClipOval(
        child: imageUrl != null
            ? AppNetworkImage(
                url: imageUrl!,
                logicalWidth: 50,
                logicalHeight: 50,
                maxCacheWidth: 100,
                maxCacheHeight: 100,
              )
            : Center(
                child: Text(emoji, style: const TextStyle(fontSize: 18)),
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
