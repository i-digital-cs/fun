import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/features/home/domain/weather.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Brand glass weather panel — mirrors Blade `x-dashboard.weather-card`.
class WeatherCard extends StatelessWidget {
  const WeatherCard({
    super.key,
    this.weather,
    this.onSetLocation,
  });

  final WeatherSnapshot? weather;
  final VoidCallback? onSetLocation;

  LinearGradient _themeGradient(String theme) {
    return switch (theme) {
      'rain' => const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF312E81), Color(0xFF1E3A5F), Color(0xFF0F172A)],
        ),
      'cloudy' => const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF64748B), Color(0xFF475569), Color(0xFF334155)],
        ),
      _ => const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFFF97316), Color(0xFFF59E0B), Color(0xFF4F46E5)],
        ),
    };
  }

  IconData _iconFor(String icon) {
    return switch (icon) {
      'rain' => Icons.water_drop_outlined,
      'cloud' => Icons.cloud_outlined,
      _ => Icons.wb_sunny_outlined,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final snapshot = weather;

    if (snapshot == null) {
      return Container(
        width: double.infinity,
        padding: const EdgeInsets.all(18),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(22),
          border: Border.all(color: AppColors.borderSoft),
          color: AppColors.surface,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Icon(Icons.place_outlined, color: AppColors.primary, size: 28),
            const SizedBox(height: 12),
            Text(
              l10n.weatherSetLocation,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              l10n.weatherSetLocationHint,
              style: const TextStyle(fontSize: 12, color: AppColors.muted),
            ),
            if (onSetLocation != null) ...[
              const SizedBox(height: 12),
              TextButton(
                onPressed: onSetLocation,
                child: Text(l10n.weatherSetLocation),
              ),
            ],
          ],
        ),
      );
    }

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 14),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(22),
        gradient: _themeGradient(snapshot.theme),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.22),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Icon(
                _iconFor(snapshot.icon),
                color: Colors.white,
                size: 36,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      snapshot.label,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(
                        horizontal: 8,
                        vertical: 3,
                      ),
                      decoration: BoxDecoration(
                        color: Colors.white.withValues(alpha: 0.18),
                        borderRadius: BorderRadius.circular(999),
                      ),
                      child: Text(
                        snapshot.city.name,
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    '${snapshot.temperature}°',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 34,
                      fontWeight: FontWeight.w900,
                      height: 1,
                    ),
                  ),
                  if (snapshot.range != null)
                    Text(
                      snapshot.range!,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.85),
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  if (snapshot.localTime != null || snapshot.localDate != null)
                    Text(
                      [
                        if (snapshot.localTime != null) snapshot.localTime,
                        if (snapshot.localDate != null) snapshot.localDate,
                      ].join(' · '),
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.7),
                        fontSize: 10,
                      ),
                    ),
                ],
              ),
            ],
          ),
          if (snapshot.forecast.isNotEmpty) ...[
            const SizedBox(height: 14),
            Row(
              children: [
                for (final day in snapshot.forecast.take(5))
                  Expanded(
                    child: Column(
                      children: [
                        Text(
                          day.day,
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.85),
                            fontSize: 10,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Icon(
                          _iconFor(day.icon),
                          size: 16,
                          color: Colors.white,
                        ),
                        if (day.high != null) ...[
                          const SizedBox(height: 2),
                          Text(
                            '${day.high}°',
                            style: const TextStyle(
                              color: Colors.white,
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
              ],
            ),
          ],
        ],
      ),
    );
  }
}
