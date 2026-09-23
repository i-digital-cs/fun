import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:intl/intl.dart';

/// Light indigo glass — mirrors Blade `x-dashboard.now-playing`.
class NowPlayingCard extends StatelessWidget {
  const NowPlayingCard({
    super.key,
    required this.feed,
    required this.brand,
    required this.onOpenEvent,
    this.logoUrl,
    this.onDiscover,
  });

  final NowPlayingFeed feed;
  final String brand;
  final String? logoUrl;
  final ValueChanged<EventItem> onOpenEvent;
  final VoidCallback? onDiscover;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final locale = Localizations.localeOf(context).toString();
    final isRtl = Directionality.of(context) == TextDirection.rtl;

    String timeLabel(EventItem event) {
      final starts = event.startsAt?.toLocal();
      if (starts == null) return '';
      final now = DateTime.now();
      final today = DateTime(now.year, now.month, now.day);
      final day = DateTime(starts.year, starts.month, starts.day);
      final time = DateFormat('h:mm a', locale).format(starts);
      if (day == today) return '${l10n.today} · $time';
      if (day == today.add(const Duration(days: 1))) {
        return '${l10n.tomorrow} · $time';
      }
      return DateFormat('E · M j · h:mm a', locale).format(starts);
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Colors.white, Color(0xFFF8FAFC), Color(0xFFEEF2FF)],
        ),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF312E81).withValues(alpha: 0.12),
            blurRadius: 28,
            offset: const Offset(0, 14),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              _BrandMark(brand: brand, logoUrl: logoUrl),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.nowPlayingTitle(brand),
                      style: const TextStyle(
                        color: AppColors.text,
                        fontWeight: FontWeight.w800,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 3),
                    Row(
                      children: [
                        if (feed.running != null) ...[
                          Container(
                            width: 7,
                            height: 7,
                            decoration: BoxDecoration(
                              color: AppColors.accent,
                              shape: BoxShape.circle,
                              boxShadow: [
                                BoxShadow(
                                  color: AppColors.accent.withValues(alpha: 0.35),
                                  blurRadius: 6,
                                ),
                              ],
                            ),
                          ),
                          const SizedBox(width: 6),
                        ],
                        Flexible(
                          child: Text(
                            feed.running != null
                                ? l10n.nowPlayingLive
                                : l10n.nowPlayingIdle,
                            style: const TextStyle(
                              color: AppColors.muted,
                              fontWeight: FontWeight.w600,
                              fontSize: 11,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              if (onDiscover != null)
                TextButton(
                  onPressed: onDiscover,
                  style: TextButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    minimumSize: Size.zero,
                    tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                    textStyle: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 11,
                    ),
                  ),
                  child: Text(l10n.navDiscover),
                ),
            ],
          ),
          const SizedBox(height: 12),
          if (feed.running != null)
            _RowItem(
              event: feed.running!,
              subtitle:
                  '${feed.running!.category?.name ?? l10n.eventsLabel}${feed.running!.city != null ? ' · ${feed.running!.city!.name}' : ''}',
              live: true,
              isRtl: isRtl,
              onTap: () => onOpenEvent(feed.running!),
            ),
          ...feed.next.map(
            (e) => Padding(
              padding: const EdgeInsets.only(top: 6),
              child: _RowItem(
                event: e,
                subtitle: '${l10n.nowPlayingUpNext} · ${timeLabel(e)}',
                isRtl: isRtl,
                onTap: () => onOpenEvent(e),
              ),
            ),
          ),
          ...feed.previous.map(
            (e) => Padding(
              padding: const EdgeInsets.only(top: 6),
              child: _RowItem(
                event: e,
                subtitle: '${l10n.nowPlayingPrevious} · ${timeLabel(e)}',
                past: true,
                isRtl: isRtl,
                onTap: () => onOpenEvent(e),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _BrandMark extends StatelessWidget {
  const _BrandMark({required this.brand, this.logoUrl});

  final String brand;
  final String? logoUrl;

  @override
  Widget build(BuildContext context) {
    if (logoUrl != null && logoUrl!.isNotEmpty) {
      return SizedBox(
        width: 42,
        height: 42,
        child: ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: AppNetworkImage(
            url: logoUrl!,
            logicalWidth: 42,
            logicalHeight: 42,
            maxCacheWidth: 84,
            maxCacheHeight: 84,
          ),
        ),
      );
    }
    return Container(
      width: 42,
      height: 42,
      alignment: Alignment.center,
      decoration: const BoxDecoration(
        borderRadius: BorderRadius.all(Radius.circular(12)),
        gradient: AppColors.indigoGradient,
      ),
      child: Text(
        brand.isNotEmpty ? brand.substring(0, 1) : 'F',
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w800,
          fontSize: 16,
        ),
      ),
    );
  }
}

class _RowItem extends StatelessWidget {
  const _RowItem({
    required this.event,
    required this.subtitle,
    required this.onTap,
    required this.isRtl,
    this.live = false,
    this.past = false,
  });

  final EventItem event;
  final String subtitle;
  final VoidCallback onTap;
  final bool isRtl;
  final bool live;
  final bool past;

  @override
  Widget build(BuildContext context) {
    return Opacity(
      opacity: past ? 0.78 : 1,
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(14),
          child: Ink(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
            decoration: BoxDecoration(
              color: live ? const Color(0xFFFFF7ED) : Colors.white.withValues(alpha: 0.75),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(
                color: live
                    ? AppColors.accent.withValues(alpha: 0.25)
                    : Colors.transparent,
              ),
            ),
            child: Row(
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: SizedBox(
                    width: 42,
                    height: 42,
                    child: event.imageUrl != null && event.imageUrl!.isNotEmpty
                        ? AppNetworkImage(
                            url: event.imageUrl!,
                            logicalWidth: 42,
                            logicalHeight: 42,
                            maxCacheWidth: 84,
                            maxCacheHeight: 84,
                          )
                        : const ColoredBox(color: AppColors.primarySoft),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        event.title,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.text,
                          fontWeight: FontWeight.w700,
                          fontSize: 13,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        subtitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          color: AppColors.muted,
                          fontWeight: FontWeight.w600,
                          fontSize: 11,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(width: 6),
                if (live)
                  const _EqBars()
                else
                  Container(
                    width: 26,
                    height: 26,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(
                      color: AppColors.primarySoft,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      isRtl ? Icons.chevron_left : Icons.chevron_right,
                      size: 16,
                      color: AppColors.primary,
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

class _EqBars extends StatefulWidget {
  const _EqBars();

  @override
  State<_EqBars> createState() => _EqBarsState();
}

class _EqBarsState extends State<_EqBars> with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _controller,
      builder: (context, _) {
        return SizedBox(
          height: 16,
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: List.generate(4, (i) {
              final t = (_controller.value + i * 0.18) % 1.0;
              final h = 4.0 + (t < 0.5 ? t : 1 - t) * 12;
              return Container(
                width: 3,
                height: h,
                margin: const EdgeInsets.symmetric(horizontal: 1),
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(4),
                  gradient: const LinearGradient(
                    begin: Alignment.topCenter,
                    end: Alignment.bottomCenter,
                    colors: [AppColors.primary, AppColors.accent],
                  ),
                ),
              );
            }),
          ),
        );
      },
    );
  }
}
