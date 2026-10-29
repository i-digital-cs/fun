import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:go_router/go_router.dart';

/// Compact Home "Explore Around You" entry — opens Discover map.
/// Full embedded flutter_map strip can replace the placeholder without API changes.
class HomeExploreMapCard extends StatelessWidget {
  const HomeExploreMapCard({
    super.key,
    this.locationLabel,
  });

  final String? locationLabel;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Explore Around You',
            style: TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 2),
          Text(
            'Find activities, meet people, and explore events nearby.',
            style: TextStyle(
              fontSize: 12,
              color: AppColors.muted,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 10),
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            child: InkWell(
              onTap: () => context.go('/discover'),
              borderRadius: BorderRadius.circular(16),
              child: Ink(
                height: 240,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE8EAF2)),
                  gradient: const LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [Color(0xFFF1EBFF), Color(0xFFFFF1E7)],
                  ),
                ),
                child: Stack(
                  children: [
                    Center(
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            Icons.map_outlined,
                            size: 36,
                            color: AppColors.primary.withValues(alpha: 0.85),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            locationLabel ?? 'Open the full map',
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF19213D),
                            ),
                          ),
                        ],
                      ),
                    ),
                    Positioned(
                      right: 12,
                      bottom: 12,
                      child: FilledButton(
                        onPressed: () => context.go('/discover'),
                        style: FilledButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 8,
                          ),
                          textStyle: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        child: const Text('Explore Full Map'),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
