import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart' hide TextDirection;

/// Uiverse 3D glass card (Smit-Prajapati) adapted like Blade `.event-3d`.
///
/// Structure mirrors the CSS: `parent` → `card` → `logo` circles + `glass` +
/// `content` + `bottom` (pills + view-more). Press ≈ CSS `:hover`.
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
  bool _hover = false;

  void _setHover(bool v) {
    if (_hover == v) return;
    setState(() => _hover = v);
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
    // Match Blade: free label or full "25.00 EGP / person"
    final priceChip = event.isFree
        ? l10n.free
        : l10n.costPerPersonAmount('${event.costPerPerson}');
    final currencyPill = event.isFree ? '✓' : l10n.currencyShort;
    final cityName = event.city?.name.trim() ?? '';
    final venue = event.venue.trim();
    final venueLine = [
      venue,
      if (cityName.isNotEmpty &&
          !venue.toLowerCase().contains(cityName.toLowerCase()))
        cityName,
    ].where((s) => s.isNotEmpty).join(' · ');

    // CSS: .parent:hover .card { transform: rotate3d(1, 1, 0, 30deg); }
    final tilt = _hover ? 0.35 : 0.0; // ~20° feel on mobile (30° is harsh)

    return GestureDetector(
      onTapDown: (_) => _setHover(true),
      onTapUp: (_) {
        _setHover(false);
        widget.onView();
      },
      onTapCancel: () => _setHover(false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 500),
        curve: Curves.easeInOut,
        transformAlignment: Alignment.center,
        // Bound height on the outer widget so ListView gets a finite child.
        height: 360,
        transform: Matrix4.identity()
          ..setEntry(3, 2, 0.0012)
          ..rotateX(tilt)
          ..rotateY(isRtl ? -tilt : tilt),
        child: Container(
          height: 360,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(50),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Color(0xFFF4F7FF),
                Color(0xFFEEF2FF),
                Color(0xFFE8ECFF),
              ],
            ),
            boxShadow: [
              BoxShadow(
                color: Color(0xFF312E81).withValues(alpha: _hover ? 0.28 : 0.0),
                blurRadius: 40,
                offset: const Offset(24, 36),
                spreadRadius: -28,
              ),
              BoxShadow(
                color: Color(0xFF312E81).withValues(alpha: _hover ? 0.14 : 0.2),
                blurRadius: _hover ? 28 : 22,
                offset: Offset(0, _hover ? 22 : 18),
                spreadRadius: _hover ? 0 : -4,
              ),
            ],
          ),
          child: SizedBox(
            height: 360,
            width: double.infinity,
            child: Stack(
            clipBehavior: Clip.none,
            children: [
                // Cover media (Fun adaptation)
                Positioned(
                  top: 0,
                  left: 0,
                  right: 0,
                  height: 140,
                  child: ClipRRect(
                    borderRadius: const BorderRadius.vertical(
                      top: Radius.circular(50),
                    ),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        if (hasImage)
                          AppNetworkImage(
                            url: event.imageUrl!,
                            logicalWidth: 420,
                            logicalHeight: 140,
                            maxCacheWidth: 900,
                            maxCacheHeight: 400,
                          )
                        else
                          const ColoredBox(color: AppColors.primarySoft),
                        DecoratedBox(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.bottomCenter,
                              end: Alignment.topCenter,
                              colors: [
                                const Color(0xFFEEF2FF).withValues(alpha: 0.95),
                                Colors.transparent,
                              ],
                              stops: const [0.0, 0.5],
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),

                // CSS .glass — inset 8px, top-right radius 100%
                Positioned(
                  left: 8,
                  right: 8,
                  top: 8,
                  bottom: 8,
                  child: IgnorePointer(
                    child: ClipRRect(
                      borderRadius: const BorderRadius.only(
                        topLeft: Radius.circular(55),
                        topRight: Radius.circular(999),
                        bottomLeft: Radius.circular(55),
                        bottomRight: Radius.circular(55),
                      ),
                      child: DecoratedBox(
                        decoration: BoxDecoration(
                          gradient: LinearGradient(
                            begin: Alignment.bottomCenter,
                            end: Alignment.topCenter,
                            colors: [
                              Colors.white.withValues(alpha: 0.35),
                              Colors.white.withValues(alpha: 0.82),
                            ],
                          ),
                          border: const Border(
                            left: BorderSide(color: Colors.white, width: 1.2),
                            bottom: BorderSide(color: Colors.white, width: 1.2),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),

                // CSS .logo — five concentric circles
                Positioned(
                  top: 0,
                  right: isRtl ? null : 0,
                  left: isRtl ? 0 : null,
                  child: _UiverseLogoStack(
                    imageUrl: hasImage ? event.imageUrl : null,
                    emoji: emoji,
                    hover: _hover,
                    isRtl: isRtl,
                  ),
                ),

                // Category + price (over glass, start side)
                PositionedDirectional(
                  top: 16,
                  start: 16,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (categoryName.isNotEmpty)
                        _Chip(
                          child: Text(
                            '$emoji $categoryName'.trim(),
                            style: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                              color: Color(0xFF1E1B4B),
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

                // Bookmark — match Blade: below media on end side (not on logo)
                if (widget.onFavorite != null)
                  PositionedDirectional(
                    top: 118,
                    end: 14,
                    child: Material(
                      color: Colors.white.withValues(alpha: 0.95),
                      shape: const CircleBorder(),
                      elevation: 3,
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

                // CSS .content
                Positioned(
                  left: 0,
                  right: 0,
                  top: 118,
                  bottom: 56,
                  child: Padding(
                    padding: EdgeInsets.fromLTRB(
                      28,
                      8,
                      isRtl ? 28 : 56,
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
                            color: Color(0xFF2E3A59),
                            fontWeight: FontWeight.w900,
                            fontSize: 18,
                            height: 1.25,
                          ),
                        ),
                        if (schedule.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            schedule,
                            style: const TextStyle(
                              color: Color(0xFF7D8CC4),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.4,
                            ),
                          ),
                        ],
                        if (venueLine.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            venueLine,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              color: Color(0xFF7D8CC4),
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 12,
                            vertical: 10,
                          ),
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: const Color(0xFF312E81)
                                    .withValues(alpha: 0.10),
                                blurRadius: 14,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              Row(
                                children: [
                                  Text(
                                    l10n.members,
                                    style: const TextStyle(
                                      fontSize: 12,
                                      color: Color(0xFF7D8CC4),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  const Spacer(),
                                  Text(
                                    '${event.membersCount} / ${event.maxParticipants}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w900,
                                      color: Color(0xFF2E3A59),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 8),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(999),
                                child: LinearProgressIndicator(
                                  value: event.fillPercent / 100,
                                  minHeight: 7,
                                  backgroundColor: const Color(0xFFE8ECF4),
                                  valueColor: AlwaysStoppedAnimation(
                                    event.isUrgent
                                        ? AppColors.accent
                                        : const Color(0xFF5C5CFF),
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

                // CSS .bottom — pills match Blade: members + currency + %
                Positioned(
                  left: 20,
                  right: 20,
                  bottom: 18,
                  child: Row(
                    children: [
                      _SocialPill(
                        label:
                            '${event.membersCount}/${event.maxParticipants}',
                        wide: true,
                        hover: _hover,
                      ),
                      const SizedBox(width: 10),
                      _SocialPill(
                        label: currencyPill,
                        hover: _hover,
                      ),
                      const SizedBox(width: 10),
                      _SocialPill(
                        label: '$pct%',
                        hover: _hover,
                      ),
                      const Spacer(),
                      // CSS .view-more
                      AnimatedContainer(
                        duration: const Duration(milliseconds: 200),
                        transform: Matrix4.translationValues(
                          0,
                          0,
                          _hover ? 10 : 0,
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Text(
                              l10n.viewEvent,
                              style: const TextStyle(
                                color: AppColors.accent,
                                fontWeight: FontWeight.w800,
                                fontSize: 12,
                              ),
                            ),
                            Icon(
                              isRtl
                                  ? Icons.chevron_left
                                  : Icons.chevron_right,
                              size: 16,
                              color: AppColors.accent,
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
            ),
        ),
      ),
    );
  }
}

/// CSS `.logo` + `.circle1`…`.circle5` with translateZ depths.
class _UiverseLogoStack extends StatelessWidget {
  const _UiverseLogoStack({
    required this.emoji,
    required this.hover,
    required this.isRtl,
    this.imageUrl,
  });

  final String emoji;
  final bool hover;
  final bool isRtl;
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    // Rest Z: 20,40,60,80,100 — hover: circle2→60, 3→80, 4→100, 5→120
    final sizes = <double>[170, 140, 110, 80, 50];
    final tops = <double>[8, 10, 17, 23, 30];
    final rights = <double>[8, 10, 17, 23, 30];
    final zRest = <double>[20, 40, 60, 80, 100];
    final zHover = <double>[20, 60, 80, 100, 120];

    return SizedBox(
      width: 178,
      height: 178,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          for (var i = 0; i < 5; i++)
            Positioned(
              top: tops[i],
              right: isRtl ? null : rights[i],
              left: isRtl ? rights[i] : null,
              child: AnimatedContainer(
                duration: Duration(milliseconds: 500 + i * 200),
                curve: Curves.easeInOut,
                width: sizes[i],
                height: sizes[i],
                transformAlignment: Alignment.center,
                transform: Matrix4.identity()
                  ..setEntry(3, 2, 0.001)
                  ..translateByDouble(
                    0,
                    0,
                    hover ? zHover[i] : zRest[i],
                    1,
                  ),
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: i == 4
                      ? AppColors.primary.withValues(alpha: 0.55)
                      : Colors.white.withValues(alpha: 0.22),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF64646F).withValues(alpha: 0.22),
                      blurRadius: 20,
                      offset: Offset(isRtl ? 10 : -10, 10),
                    ),
                  ],
                ),
                clipBehavior: Clip.antiAlias,
                child: i == 4
                    ? (imageUrl != null
                        ? AppNetworkImage(
                            url: imageUrl!,
                            logicalWidth: 50,
                            logicalHeight: 50,
                            maxCacheWidth: 100,
                            maxCacheHeight: 100,
                          )
                        : Center(
                            child: Text(
                              emoji,
                              style: const TextStyle(fontSize: 18),
                            ),
                          ))
                    : null,
              ),
            ),
        ],
      ),
    );
  }
}

/// CSS `.social-button` — white circle that lifts on hover.
class _SocialPill extends StatelessWidget {
  const _SocialPill({
    required this.label,
    required this.hover,
    this.wide = false,
  });

  final String label;
  final bool hover;
  final bool wide;

  @override
  Widget build(BuildContext context) {
    return AnimatedContainer(
      duration: const Duration(milliseconds: 200),
      curve: Curves.easeInOut,
      // Stagger feel via delay on rebuild is weak; use shared hover lift.
      transform: Matrix4.translationValues(0, hover ? -4 : 0, hover ? 20 : 0),
      width: wide ? 48 : 36,
      height: 36,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        shape: BoxShape.circle,
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF312E81).withValues(alpha: hover ? 0.22 : 0.18),
            blurRadius: hover ? 12 : 10,
            offset: Offset(hover ? -4 : 0, hover ? 12 : 4),
          ),
        ],
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 11,
          fontWeight: FontWeight.w800,
          color: Color(0xFF5C5CFF),
        ),
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
      decoration: BoxDecoration(
        color: Colors.white.withValues(alpha: 0.92),
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.08),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: child,
    );
  }
}
