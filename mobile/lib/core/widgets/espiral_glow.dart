import 'dart:math' as math;

import 'package:flutter/material.dart';

enum EspiralTone { indigo, amber, emerald }

enum EspiralSize { sm, md, lg }

/// Blade `x-ui.espiral` — decorative rotating dot arms (pointer-ignore).
class EspiralGlow extends StatefulWidget {
  const EspiralGlow({
    super.key,
    this.tone = EspiralTone.indigo,
    this.size = EspiralSize.md,
    this.arms = 3,
    this.dots = 12,
    this.opacity,
  });

  final EspiralTone tone;
  final EspiralSize size;
  final int arms;
  final int dots;
  final double? opacity;

  @override
  State<EspiralGlow> createState() => _EspiralGlowState();
}

class _EspiralGlowState extends State<EspiralGlow>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctl;

  @override
  void initState() {
    super.initState();
    _ctl = AnimationController(
      vsync: this,
      duration: const Duration(seconds: 5),
    )..repeat();
  }

  @override
  void dispose() {
    _ctl.dispose();
    super.dispose();
  }

  Color get _color => switch (widget.tone) {
    EspiralTone.amber => const Color(0xFFFB923C),
    EspiralTone.emerald => const Color(0xFF34D399),
    EspiralTone.indigo => const Color(0xFF818CF8),
  };

  double get _scale => switch (widget.size) {
    EspiralSize.sm => 0.42,
    EspiralSize.md => 0.55,
    EspiralSize.lg => 0.72,
  };

  double get _opacity =>
      widget.opacity ??
      switch (widget.size) {
        EspiralSize.sm => 0.4,
        EspiralSize.md => 0.5,
        EspiralSize.lg => 0.45,
      };

  @override
  Widget build(BuildContext context) {
    final arms = widget.arms.clamp(2, 4);
    final dots = widget.dots.clamp(8, 20);
    final reduce = MediaQuery.disableAnimationsOf(context);

    return IgnorePointer(
      child: Opacity(
        opacity: _opacity,
        child: Transform.scale(
          scale: _scale,
          child: AnimatedBuilder(
            animation: _ctl,
            builder: (context, _) {
              final t = reduce ? 0.0 : _ctl.value;
              return CustomPaint(
                painter: _EspiralPainter(
                  t: t,
                  color: _color,
                  arms: arms,
                  dots: dots,
                ),
                child: const SizedBox.expand(),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _EspiralPainter extends CustomPainter {
  _EspiralPainter({
    required this.t,
    required this.color,
    required this.arms,
    required this.dots,
  });

  final double t;
  final Color color;
  final int arms;
  final int dots;

  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = math.min(size.width, size.height) * 0.42;
    final paint = Paint()..color = color;

    for (var r = 1; r <= arms; r++) {
      final armRot = r * (math.pi / 4);
      for (var i = 1; i <= dots; i++) {
        final angle = armRot + i * (18 * math.pi / 180);
        // Pulse travel along arm (CSS espiral-animate)
        final phase = (t + i * 0.1) % 1.0;
        final dist = radius * (0.15 + phase * 0.85);
        final glow = (1 - phase).clamp(0.2, 1.0);
        final pos = Offset(
          center.dx + math.cos(angle) * dist,
          center.dy + math.sin(angle) * dist,
        );
        canvas.drawCircle(
          pos,
          5 * glow + 2,
          paint..color = color.withValues(alpha: 0.35 + 0.55 * glow),
        );
        canvas.drawCircle(
          pos,
          2.5,
          Paint()..color = color.withValues(alpha: 0.9 * glow),
        );
      }
    }
  }

  @override
  bool shouldRepaint(covariant _EspiralPainter oldDelegate) =>
      oldDelegate.t != t ||
      oldDelegate.color != color ||
      oldDelegate.arms != arms ||
      oldDelegate.dots != dots;
}

/// Convenience: end-anchored glow for dark heroes (Blade `espiral--anchor-end`).
class EspiralAnchorEnd extends StatelessWidget {
  const EspiralAnchorEnd({
    super.key,
    this.tone = EspiralTone.amber,
    this.size = EspiralSize.lg,
  });

  final EspiralTone tone;
  final EspiralSize size;

  @override
  Widget build(BuildContext context) {
    return Positioned(
      right: -40,
      top: -40,
      width: 220,
      height: 220,
      child: EspiralGlow(tone: tone, size: size, arms: 3, dots: 14),
    );
  }
}

/// Cover-corner glow (Blade `espiral--anchor-cover`).
class EspiralAnchorCover extends StatelessWidget {
  const EspiralAnchorCover({
    super.key,
    this.tone = EspiralTone.indigo,
    this.size = EspiralSize.md,
  });

  final EspiralTone tone;
  final EspiralSize size;

  @override
  Widget build(BuildContext context) {
    return Positioned(
      right: -24,
      bottom: -24,
      width: 160,
      height: 160,
      child: EspiralGlow(tone: tone, size: size),
    );
  }
}
