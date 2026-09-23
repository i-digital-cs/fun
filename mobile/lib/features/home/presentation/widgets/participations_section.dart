import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Confirmed + pending participations — Blade `x-home.participations` / community.
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

  final HomeParticipations data;
  final VoidCallback onDiscover;
  final VoidCallback onManageAll;
  final ValueChanged<int> onOpenEvent;
  final ValueChanged<int> onOpenChat;
  final ValueChanged<int> onOpenTicket;

  String _when(AppLocalizations l10n, DateTime? starts, String locale) {
    if (starts == null) return '';
    final local = starts.toLocal();
    return DateFormat('E · h:mm a', locale).format(local);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();

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
            TextButton(
              onPressed: onManageAll,
              child: Text(l10n.manageAll),
            ),
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
              border: Border.all(
                color: AppColors.border,
                style: BorderStyle.solid,
              ),
            ),
            child: Column(
              children: [
                Text(
                  l10n.participationsEmpty,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: 12),
                GradientButton(
                  label: l10n.navDiscover,
                  onPressed: onDiscover,
                ),
              ],
            ),
          )
        else ...[
          if (data.memberships.isNotEmpty) ...[
            Text(
              l10n.confirmedAttendance,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: AppColors.success,
              ),
            ),
            const SizedBox(height: 8),
            ...data.memberships.map(
              (row) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _ParticipationCard(
                  event: row.event,
                  badge: l10n.confirmedAttendance,
                  badgeColor: AppColors.success,
                  when: _when(l10n, row.event.startsAt, locale),
                  onOpen: () => onOpenEvent(row.event.id),
                  actions: [
                    _ActionChip(
                      label: l10n.viewTicket,
                      filled: true,
                      onTap: () => onOpenTicket(row.event.id),
                    ),
                    _ActionChip(
                      label: l10n.chat,
                      onTap: () => onOpenChat(row.event.id),
                    ),
                    _ActionChip(
                      label: l10n.viewEvent,
                      onTap: () => onOpenEvent(row.event.id),
                    ),
                  ],
                ),
              ),
            ),
          ],
          if (data.pendingRequests.isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              l10n.requestPendingShort,
              style: const TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: AppColors.accent,
              ),
            ),
            const SizedBox(height: 8),
            ...data.pendingRequests.map(
              (row) => Padding(
                padding: const EdgeInsets.only(bottom: 10),
                child: _ParticipationCard(
                  event: row.event,
                  badge: l10n.requestPendingShort,
                  badgeColor: AppColors.accent,
                  when: _when(l10n, row.event.startsAt, locale),
                  notice: l10n.pendingReviewNotice,
                  onOpen: () => onOpenEvent(row.event.id),
                  actions: [
                    _ActionChip(
                      label: l10n.viewEvent,
                      onTap: () => onOpenEvent(row.event.id),
                    ),
                  ],
                ),
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
      borderRadius: BorderRadius.circular(18),
      child: InkWell(
        onTap: onOpen,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(18),
            border: Border.all(color: AppColors.borderSoft),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 8,
                      vertical: 4,
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
                    Text(
                      when,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.muted,
                      ),
                    ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                event.title,
                style: const TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                [
                  if (event.venue.isNotEmpty) event.venue,
                  if (event.city?.name != null) event.city!.name,
                ].join(' · '),
                style: const TextStyle(fontSize: 12, color: AppColors.muted),
              ),
              if (notice != null) ...[
                const SizedBox(height: 8),
                Text(
                  notice!,
                  style: const TextStyle(fontSize: 12, color: AppColors.muted),
                ),
              ],
              const SizedBox(height: 10),
              Wrap(spacing: 8, runSpacing: 8, children: actions),
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
        borderRadius: BorderRadius.circular(10),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(10),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            child: Text(
              label,
              style: const TextStyle(
                color: Colors.white,
                fontSize: 11,
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
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        minimumSize: Size.zero,
        tapTargetSize: MaterialTapTargetSize.shrinkWrap,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
      child: Text(
        label,
        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w700),
      ),
    );
  }
}
