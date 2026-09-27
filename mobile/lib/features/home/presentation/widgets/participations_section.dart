import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Same participation card style as before, scaled down — max 4 in a row/grid.
class ParticipationsSection extends StatelessWidget {
  const ParticipationsSection({
    super.key,
    required this.data,
    required this.onDiscover,
    required this.onManageAll,
    required this.onOpenEvent,
    required this.onOpenChat,
    required this.onOpenTicket,
  });

  static const int previewLimit = 4;

  final HomeParticipations data;
  final VoidCallback onDiscover;
  final VoidCallback onManageAll;
  final ValueChanged<int> onOpenEvent;
  final ValueChanged<int> onOpenChat;
  final ValueChanged<int> onOpenTicket;

  String _when(DateTime? starts, String locale) {
    if (starts == null) return '';
    return DateFormat('E · h:mm a', locale).format(starts.toLocal());
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final width = MediaQuery.sizeOf(context).width;
    final crossAxisCount = width >= 1100
        ? 4
        : width >= 700
            ? 2
            : 1;

    final preview = <({bool pending, EventItem event})>[];
    for (final row in data.memberships) {
      if (preview.length >= previewLimit) break;
      preview.add((pending: false, event: row.event));
    }
    for (final row in data.pendingRequests) {
      if (preview.length >= previewLimit) break;
      preview.add((pending: true, event: row.event));
    }
    final hidden = data.scheduledCount - preview.length;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Row(
                children: [
                  Flexible(
                    child: Text(
                      l10n.yourParticipations,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 3,
                    ),
                    decoration: BoxDecoration(
                      color: AppColors.primarySoft,
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '${data.scheduledCount}',
                      style: const TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ],
              ),
            ),
            TextButton(onPressed: onManageAll, child: Text(l10n.manageAll)),
          ],
        ),
        const SizedBox(height: 10),
        if (data.isEmpty)
          Container(
            width: double.infinity,
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(18),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              children: [
                Text(
                  l10n.participationsEmpty,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: 12),
                GradientButton(label: l10n.navDiscover, onPressed: onDiscover),
              ],
            ),
          )
        else ...[
          GridView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: preview.length,
            gridDelegate: SliverGridDelegateWithFixedCrossAxisCount(
              crossAxisCount: crossAxisCount,
              mainAxisSpacing: 10,
              crossAxisSpacing: 10,
              childAspectRatio: crossAxisCount == 1
                  ? 2.4
                  : crossAxisCount == 2
                      ? 1.35
                      : 1.05,
            ),
            itemBuilder: (context, index) {
              final item = preview[index];
              return _ParticipationCard(
                event: item.event,
                badge: item.pending
                    ? l10n.requestPendingShort
                    : l10n.confirmedAttendance,
                badgeColor:
                    item.pending ? AppColors.accent : AppColors.success,
                when: _when(item.event.startsAt, locale),
                notice: item.pending ? l10n.pendingReviewNotice : null,
                onOpen: () => onOpenEvent(item.event.id),
                actions: item.pending
                    ? [
                        _ActionChip(
                          label: l10n.viewEvent,
                          onTap: () => onOpenEvent(item.event.id),
                        ),
                      ]
                    : [
                        _ActionChip(
                          label: l10n.viewTicket,
                          filled: true,
                          onTap: () => onOpenTicket(item.event.id),
                        ),
                        _ActionChip(
                          label: l10n.chat,
                          onTap: () => onOpenChat(item.event.id),
                        ),
                      ],
              );
            },
          ),
          if (hidden > 0) ...[
            const SizedBox(height: 8),
            Center(
              child: TextButton(
                onPressed: onManageAll,
                child: Text(l10n.participationsMore(hidden)),
              ),
            ),
          ],
        ],
      ],
    );
  }
}

class _ParticipationCard extends StatelessWidget {
  const _ParticipationCard({
    required this.event,
    required this.badge,
    required this.badgeColor,
    required this.when,
    required this.onOpen,
    required this.actions,
    this.notice,
  });

  final EventItem event;
  final String badge;
  final Color badgeColor;
  final String when;
  final String? notice;
  final VoidCallback onOpen;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        onTap: onOpen,
        borderRadius: BorderRadius.circular(16),
        child: Container(
          padding: const EdgeInsets.all(12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.borderSoft),
          ),
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
                      color: badgeColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      badge,
                      style: TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: badgeColor,
                      ),
                    ),
                  ),
                  const Spacer(),
                  if (when.isNotEmpty)
                    Flexible(
                      child: Text(
                        when,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.end,
                        style: const TextStyle(
                          fontSize: 10,
                          color: AppColors.muted,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 6),
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
              const SizedBox(height: 3),
              Text(
                [
                  if (event.venue.isNotEmpty) event.venue,
                  if (event.city?.name != null) event.city!.name,
                ].join(' · '),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 11, color: AppColors.muted),
              ),
              if (notice != null) ...[
                const SizedBox(height: 6),
                Text(
                  notice!,
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 10, color: AppColors.muted),
                ),
              ],
              const Spacer(),
              Wrap(spacing: 6, runSpacing: 6, children: actions),
            ],
          ),
        ),
      ),
    );
  }
}

class _ActionChip extends StatelessWidget {
  const _ActionChip({
    required this.label,
    required this.onTap,
    this.filled = false,
  });

  final String label;
  final VoidCallback onTap;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    if (filled) {
      return Material(
        color: AppColors.accent,
        borderRadius: BorderRadius.circular(9),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(9),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            child: Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 10,
                fontWeight: FontWeight.w800,
              ),
            ),
          ),
        ),
      );
    }
    return OutlinedButton(
      onPressed: onTap,
      style: OutlinedButton.styleFrom(
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        textStyle: const TextStyle(fontSize: 10, fontWeight: FontWeight.w700),
        visualDensity: VisualDensity.compact,
      ),
      child: Text(label),
    );
  }
}
