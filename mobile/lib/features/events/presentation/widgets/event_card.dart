import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Mirrors Blade `x-events.card` — Uiverse 3D glass + cover image + members.
class EventCard extends StatefulWidget {
  const EventCard({
    super.key,
    required this.event,
    required this.onView,
  });

  final EventItem event;
  final VoidCallback onView;

  @override
  State<EventCard> createState() => _EventCardState();
}

class _EventCardState extends State<EventCard> {
  bool _pressed = false;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final event = widget.event;
    final schedule = event.startsAt != null
        ? DateFormat('E · MMM d · h:mm a', locale)
            .format(event.startsAt!.toLocal())
        : '';
    final priceLabel =
        event.isFree ? '✓' : l10n.currencyShort;
    final priceTooltip = event.isFree
        ? l10n.free
        : l10n.costPerPersonAmount('${event.costPerPerson}');
    final categoryName = event.category?.name ?? '';
    final emoji = categoryEmoji(categoryName);
    final pct = event.fillPercent.round().clamp(0, 100);
    final isRtl = Directionality.of(context) == TextDirection.rtl;
    final hasImage = event.imageUrl != null && event.imageUrl!.isNotEmpty;

    return GestureDetector(
      onTapDown: (_) => setState(() => _pressed = true),
      onTapUp: (_) {
        setState(() => _pressed = false);
        widget.onView();
      },
      onTapCancel: () => setState(() => _pressed = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 280),
        curve: Curves.easeOut,
        transformAlignment: Alignment.center,
        transform: Matrix4.identity()
          ..setEntry(3, 2, 0.001)
          ..rotateX(_pressed ? -0.16 : 0)
          ..rotateY(_pressed ? (isRtl ? 0.16 : -0.16) : 0),
        child: Container(
          constraints: const BoxConstraints(minHeight: 340),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(44),
            gradient: const LinearGradient(
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
              colors: [
                Color(0xFFEEF2FF),
                Color(0xFFE0E7FF),
                Color(0xFFC7D2FE),
              ],
            ),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF312E81).withValues(alpha: 0.18),
                blurRadius: 24,
                offset: const Offset(0, 16),
              ),
            ],
          ),
          clipBehavior: Clip.none,
          child: Stack(
            clipBehavior: Clip.none,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  SizedBox(
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
                            logicalWidth: 400,
                            logicalHeight: 148,
                            maxCacheWidth: 800,
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
                  Padding(
                    padding: const EdgeInsets.fromLTRB(18, 10, 18, 64),
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
                            height: 1.25,
                          ),
                        ),
                        if (schedule.isNotEmpty) ...[
                          const SizedBox(height: 6),
                          Text(
                            schedule.toUpperCase(),
                            style: TextStyle(
                              color: AppColors.primary.withValues(alpha: 0.85),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                              letterSpacing: 0.4,
                            ),
                          ),
                        ],
                        const SizedBox(height: 6),
                        Text(
                          event.venue,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: TextStyle(
                            color: AppColors.primaryDeep.withValues(alpha: 0.75),
                            fontSize: 13,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 10,
                            vertical: 8,
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
                                      color: AppColors.muted,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                  const Spacer(),
                                  Text(
                                    '${event.membersCount} / ${event.maxParticipants}',
                                    style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w900,
                                      color: AppColors.text,
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(height: 6),
                              ClipRRect(
                                borderRadius: BorderRadius.circular(999),
                                child: LinearProgressIndicator(
                                  value: event.fillPercent / 100,
                                  minHeight: 6,
                                  backgroundColor: const Color(0xFFE2E8F0),
                                  valueColor: AlwaysStoppedAnimation(
                                    event.isUrgent
                                        ? AppColors.accent
                                        : AppColors.primary,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              PositionedDirectional(
                top: 12,
                start: 12,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 10,
                        vertical: 5,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.92),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        '$emoji $categoryName'.trim(),
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          color: AppColors.text,
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
                        priceLabel,
                        semanticsLabel: priceTooltip,
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
              Positioned(
                top: 8,
                right: isRtl ? null : 8,
                left: isRtl ? 8 : null,
                child: _LogoStack(
                  imageUrl: hasImage ? event.imageUrl : null,
                  emoji: emoji,
                  pressed: _pressed,
                ),
              ),
              Positioned(
                left: 14,
                right: 14,
                bottom: 14,
                child: Row(
                  children: [
                    _Pill(label: '${event.membersCount}/${event.maxParticipants}'),
                    const SizedBox(width: 8),
                    _Pill(label: '$pct%'),
                    const Spacer(),
                    Text(
                      l10n.viewEvent,
                      style: const TextStyle(
                        color: AppColors.accent,
                        fontWeight: FontWeight.w800,
                        fontSize: 12,
                      ),
                    ),
                    Icon(
                      isRtl ? Icons.chevron_left : Icons.chevron_right,
                      size: 16,
                      color: AppColors.accent,
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
    this.imageUrl,
  });

  final String emoji;
  final bool pressed;
  final String? imageUrl;

  @override
  Widget build(BuildContext context) {
    // Match Uiverse circle stack: Z depths 20→100 (rest), +20 on press/hover.
    final depths = <double>[20, 40, 60, 80, 100];
    final sizes = <double>[170, 140, 110, 80, 50];
    final tops = <double>[8, 10, 17, 23, 30];
    final rights = <double>[8, 10, 17, 23, 30];
    final boost = pressed ? 20.0 : 0.0;

    Widget circleAt(int i, {Widget? child}) {
      final z = depths[i] + boost;
      return Positioned(
        top: tops[i],
        right: rights[i],
        child: Transform(
          alignment: Alignment.center,
          transform: Matrix4.identity()
            ..setEntry(3, 2, 0.001)
            ..translate(0.0, 0.0, z),
          child: Container(
            width: sizes[i],
            height: sizes[i],
            alignment: Alignment.center,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: i == 4
                  ? AppColors.primary.withValues(alpha: 0.55)
                  : Colors.white.withValues(alpha: 0.22),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withValues(alpha: 0.18 + i * 0.02),
                  blurRadius: 16 + i * 2,
                  offset: Offset(-8, 8 + i.toDouble()),
                ),
              ],
            ),
            clipBehavior: Clip.antiAlias,
            child: child,
          ),
        ),
      );
    }

    return SizedBox(
      width: 178,
      height: 178,
      child: Stack(
        clipBehavior: Clip.none,
        children: [
          for (var i = 0; i < 4; i++) circleAt(i),
          circleAt(
            4,
            child: imageUrl != null
                ? AppNetworkImage(
                    url: imageUrl!,
                    logicalWidth: 50,
                    logicalHeight: 50,
                    maxCacheWidth: 100,
                    maxCacheHeight: 100,
                  )
                : Text(emoji, style: const TextStyle(fontSize: 18)),
          ),
        ],
      ),
    );
  }
}

class _Pill extends StatelessWidget {
  const _Pill({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      height: 30,
      padding: const EdgeInsets.symmetric(horizontal: 8),
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(999),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF312E81).withValues(alpha: 0.28),
            blurRadius: 6,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Text(
        label,
        style: const TextStyle(
          fontSize: 10,
          fontWeight: FontWeight.w800,
          color: AppColors.primaryDeep,
        ),
      ),
    );
  }
}
