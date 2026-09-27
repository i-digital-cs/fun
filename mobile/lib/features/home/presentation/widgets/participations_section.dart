import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Mirrors web `x-community.participations` — same card shape, scaled for phone.
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
          LayoutBuilder(
            builder: (context, constraints) {
              final gap = 10.0;
              final cols = crossAxisCount;
              final cardWidth = cols == 1
                  ? constraints.maxWidth
                  : (constraints.maxWidth - gap * (cols - 1)) / cols;

              return Wrap(
                spacing: gap,
                runSpacing: gap,
                children: [
                  for (final item in preview)
                    SizedBox(
                      width: cardWidth,
                      child: _WebStyleParticipationCard(
                        event: item.event,
                        pending: item.pending,
                        when: _when(item.event.startsAt, locale),
                        onOpen: () => onOpenEvent(item.event.id),
                        onChat: () => onOpenChat(item.event.id),
                        onTicket: () => onOpenTicket(item.event.id),
                      ),
                    ),
                ],
              );
            },
          ),
          if (hidden > 0) ...[
            const SizedBox(height: 10),
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

class _WebStyleParticipationCard extends StatelessWidget {
  const _WebStyleParticipationCard({
    required this.event,
    required this.pending,
    required this.when,
    required this.onOpen,
    required this.onChat,
    required this.onTicket,
  });

  final EventItem event;
  final bool pending;
  final String when;
  final VoidCallback onOpen;
  final VoidCallback onChat;
  final VoidCallback onTicket;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final host = event.creator;
    final place = event.placeLine;
    final accent = pending
        ? const [Color(0xFFFBBF24), Color(0xFFFB923C), Color(0xFFFB7185)]
        : const [Color(0xFF6366F1), Color(0xFF8B5CF6), Color(0xFFFB923C)];

    return Material(
      color: AppColors.surface,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onOpen,
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: AppColors.borderSoft),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Container(
                height: 3,
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: accent),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
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
                            color: pending
                                ? const Color(0xFFFFFBEB)
                                : const Color(0xFFECFDF5),
                            borderRadius: BorderRadius.circular(999),
                            border: Border.all(
                              color: pending
                                  ? const Color(0xFFFDE68A)
                                  : const Color(0xFFA7F3D0),
                            ),
                          ),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(
                                pending
                                    ? Icons.schedule_rounded
                                    : Icons.verified_rounded,
                                size: 12,
                                color: pending
                                    ? const Color(0xFFB45309)
                                    : const Color(0xFF047857),
                              ),
                              const SizedBox(width: 4),
                              Text(
                                pending
                                    ? l10n.requestPendingShort
                                    : l10n.confirmedAttendance,
                                style: TextStyle(
                                  fontSize: 10,
                                  fontWeight: FontWeight.w800,
                                  color: pending
                                      ? const Color(0xFFB45309)
                                      : const Color(0xFF047857),
                                ),
                              ),
                            ],
                          ),
                        ),
                        const Spacer(),
                        if (when.isNotEmpty)
                          Text(
                            when,
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: AppColors.muted,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 8),
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
                    if (place.isNotEmpty) ...[
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          const Icon(
                            Icons.place_rounded,
                            size: 14,
                            color: Color(0xFF818CF8),
                          ),
                          const SizedBox(width: 4),
                          Expanded(
                            child: Text(
                              place,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              textDirection: bidiTextDirection(place),
                              style: const TextStyle(
                                fontSize: 11,
                                color: AppColors.muted,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ],
                    if (pending) ...[
                      const SizedBox(height: 8),
                      Container(
                        width: double.infinity,
                        padding: const EdgeInsets.symmetric(
                          horizontal: 10,
                          vertical: 8,
                        ),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFFBEB).withValues(alpha: 0.7),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: const Color(0xFFFDE68A).withValues(alpha: 0.7),
                          ),
                        ),
                        child: Text(
                          l10n.pendingReviewNotice,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w500,
                            color: Color(0xFF92400E),
                          ),
                        ),
                      ),
                    ] else ...[
                      const SizedBox(height: 8),
                      _StepRow(
                        labels: [
                          l10n.stepRequested,
                          l10n.stepAccepted,
                          l10n.stepGathering,
                        ],
                        doneCount: 2,
                      ),
                    ],
                    const SizedBox(height: 10),
                    const Divider(height: 1, color: Color(0xFFF1F5F9)),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        if (host != null) ...[
                          Container(
                            width: 28,
                            height: 28,
                            alignment: Alignment.center,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              gradient: LinearGradient(
                                colors: pending
                                    ? const [
                                        Color(0xFFFB7185),
                                        Color(0xFFFB923C),
                                      ]
                                    : const [
                                        Color(0xFF6366F1),
                                        Color(0xFF7C3AED),
                                      ],
                              ),
                            ),
                            child: Text(
                              host.displayInitials,
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10,
                                fontWeight: FontWeight.w900,
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  host.name,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                                Text(
                                  l10n.participationHost,
                                  style: const TextStyle(
                                    fontSize: 10,
                                    color: AppColors.muted,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ] else
                          const Spacer(),
                        if (pending)
                          _MiniBtn(
                            label: l10n.viewEvent,
                            filled: true,
                            color: const Color(0xFF0F172A),
                            onTap: onOpen,
                          )
                        else ...[
                          _MiniBtn(
                            label: l10n.viewTicket,
                            filled: true,
                            color: AppColors.accent,
                            onTap: onTicket,
                          ),
                          const SizedBox(width: 6),
                          _MiniBtn(
                            label: l10n.openChat,
                            filled: true,
                            color: AppColors.primary,
                            onTap: onChat,
                          ),
                        ],
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

class _StepRow extends StatelessWidget {
  const _StepRow({required this.labels, required this.doneCount});

  final List<String> labels;
  final int doneCount;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        for (var i = 0; i < labels.length; i++) ...[
          if (i > 0)
            Expanded(
              child: Container(
                height: 2,
                margin: const EdgeInsets.symmetric(horizontal: 4),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(99),
                  gradient: LinearGradient(
                    colors: i <= doneCount
                        ? const [Color(0xFF6EE7B7), Color(0xFFE2E8F0)]
                        : const [Color(0xFFE2E8F0), Color(0xFFE2E8F0)],
                  ),
                ),
              ),
            ),
          Column(
            children: [
              Container(
                width: 18,
                height: 18,
                alignment: Alignment.center,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: i < doneCount
                      ? const Color(0xFF10B981)
                      : const Color(0xFFF1F5F9),
                  boxShadow: i < doneCount
                      ? [
                          BoxShadow(
                            color: const Color(0xFF10B981).withValues(alpha: 0.3),
                            blurRadius: 4,
                          ),
                        ]
                      : null,
                ),
                child: Icon(
                  i < doneCount ? Icons.check_rounded : Icons.place_rounded,
                  size: 11,
                  color: i < doneCount ? Colors.white : const Color(0xFF94A3B8),
                ),
              ),
              const SizedBox(height: 2),
              Text(
                labels[i],
                style: TextStyle(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: i < doneCount
                      ? const Color(0xFF047857)
                      : const Color(0xFF94A3B8),
                ),
              ),
            ],
          ),
        ],
      ],
    );
  }
}

class _MiniBtn extends StatelessWidget {
  const _MiniBtn({
    required this.label,
    required this.onTap,
    required this.color,
    this.filled = false,
  });

  final String label;
  final VoidCallback onTap;
  final Color color;
  final bool filled;

  @override
  Widget build(BuildContext context) {
    return Material(
      color: filled ? color : Colors.white,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 6),
          decoration: filled
              ? null
              : BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
          child: Text(
            label,
            style: TextStyle(
              color: filled ? Colors.white : AppColors.text,
              fontSize: 10,
              fontWeight: FontWeight.w800,
            ),
          ),
        ),
      ),
    );
  }
}
