import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Mirrors Blade `x-events.card`.
class EventCard extends StatelessWidget {
  const EventCard({
    super.key,
    required this.event,
    required this.onView,
  });

  final EventItem event;
  final VoidCallback onView;

  String _urgencyLabel(AppLocalizations l10n) {
    final pct = event.fillPercent;
    if (pct >= 80) return l10n.almostFull;
    if (pct >= 60) return l10n.fillingFast;
    if (event.startsAt != null &&
        DateTime.now().difference(event.startsAt!).inDays.abs() <= 3 &&
        event.startsAt!.isAfter(DateTime.now().subtract(const Duration(days: 3)))) {
      // "new" approx — API doesn't send created_at on list; use popular fallback
    }
    if (pct < 40) return l10n.newBadge;
    return l10n.popular;
  }

  Color _urgencyColor() {
    final pct = event.fillPercent;
    if (pct >= 80) return AppColors.accent;
    if (pct >= 60) return const Color(0xFFE11D48);
    if (pct < 40) return AppColors.success;
    return AppColors.primaryViolet;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final schedule = event.startsAt != null
        ? DateFormat('E · MMM d · h:mm a', locale).format(event.startsAt!.toLocal())
        : '';
    final priceLabel = event.isFree
        ? l10n.free
        : event.costPerPerson?.toString() ?? '';
    final urgency = _urgencyLabel(l10n);
    final urgencyColor = _urgencyColor();
    final categoryName = event.category?.name ?? '';

    return Container(
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          SizedBox(
            height: 176,
            child: Stack(
              fit: StackFit.expand,
              children: [
                if (event.imageUrl != null && event.imageUrl!.isNotEmpty)
                  LayoutBuilder(
                    builder: (context, constraints) {
                      return AppNetworkImage(
                        url: event.imageUrl!,
                        logicalWidth: constraints.maxWidth,
                        logicalHeight: 176,
                        maxCacheWidth: 900,
                        maxCacheHeight: 600,
                      );
                    },
                  )
                else
                  const ColoredBox(
                    color: AppColors.primarySoft,
                    child: Icon(Icons.image_outlined, color: AppColors.primary),
                  ),
                const DecoratedBox(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [
                        Color(0x1A0F172A),
                        Colors.transparent,
                        Color(0x800F172A),
                      ],
                    ),
                  ),
                ),
                if (categoryName.isNotEmpty)
                  PositionedDirectional(
                    top: 12,
                    start: 12,
                    child: Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 12,
                        vertical: 6,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.9),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        categoryName,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          color: AppColors.text,
                        ),
                      ),
                    ),
                  ),
                PositionedDirectional(
                  top: 12,
                  end: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      gradient: event.isFree
                          ? AppColors.emeraldGradient
                          : AppColors.orangeGradient,
                      borderRadius: BorderRadius.circular(999),
                      boxShadow: [
                        BoxShadow(
                          color: (event.isFree
                                  ? AppColors.success
                                  : AppColors.accent)
                              .withValues(alpha: 0.4),
                          blurRadius: 8,
                        ),
                      ],
                    ),
                    child: Text(
                      priceLabel,
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w900,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
                PositionedDirectional(
                  bottom: 12,
                  start: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 4,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.95),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      urgency,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w900,
                        color: urgencyColor,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (schedule.isNotEmpty)
                  Row(
                    children: [
                      const Icon(
                        Icons.calendar_today_outlined,
                        size: 14,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 6),
                      Expanded(
                        child: Text(
                          schedule.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            letterSpacing: 0.4,
                            color: AppColors.primary,
                          ),
                        ),
                      ),
                    ],
                  ),
                const SizedBox(height: 8),
                Text(
                  event.title,
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w800,
                    height: 1.25,
                    color: AppColors.text,
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    const Icon(
                      Icons.location_on,
                      size: 14,
                      color: AppColors.muted,
                    ),
                    const SizedBox(width: 4),
                    Expanded(
                      child: Text(
                        event.venue,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 12,
                          color: AppColors.muted,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFF1F5F9)),
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
                              fontWeight: FontWeight.w500,
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
                      const SizedBox(height: 8),
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
                const SizedBox(height: 14),
                GradientButton(
                  label: l10n.viewEvent,
                  onPressed: onView,
                  icon: Icons.arrow_forward,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
