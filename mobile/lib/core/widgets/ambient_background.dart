import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';

/// Soft aurora blobs — mirrors `x-community.ambient` atmosphere.
class AmbientBackground extends StatelessWidget {
  const AmbientBackground({super.key, required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Stack(
      children: [
        const Positioned.fill(child: ColoredBox(color: AppColors.background)),
        PositionedDirectional(
          top: -80,
          start: -60,
          child: _Blob(
            size: 220,
            color: AppColors.primary.withValues(alpha: 0.18),
          ),
        ),
        PositionedDirectional(
          top: 120,
          end: -40,
          child: _Blob(
            size: 180,
            color: AppColors.primaryViolet.withValues(alpha: 0.14),
          ),
        ),
        PositionedDirectional(
          bottom: 80,
          start: 40,
          child: _Blob(
            size: 160,
            color: AppColors.accent.withValues(alpha: 0.10),
          ),
        ),
        Positioned.fill(child: child),
      ],
    );
  }
}

class _Blob extends StatelessWidget {
  const _Blob({required this.size, required this.color});

  final double size;
  final Color color;

  @override
  Widget build(BuildContext context) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: RadialGradient(
            colors: [
              color,
              color.withValues(alpha: 0),
            ],
          ),
        ),
      ),
    );
  }
}
