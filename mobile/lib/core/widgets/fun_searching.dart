import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

enum FunSearchingSize { sm, md }

/// Blade `x-ui.searching` — wifi arcs + lowercase “Searching” while typed query awaits API.
class FunSearching extends StatefulWidget {
  const FunSearching({
    super.key,
    this.size = FunSearchingSize.md,
    this.showLabel = true,
    this.compact = false,
  });

  final FunSearchingSize size;
  final bool showLabel;

  /// Inline row (Discover strip) instead of stacked badge.
  final bool compact;

  @override
  State<FunSearching> createState() => _FunSearchingState();
}

class _FunSearchingState extends State<FunSearching>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctrl;

  @override
  void initState() {
    super.initState();
    _ctrl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat();
  }

  @override
  void dispose() {
    _ctrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isSm = widget.size == FunSearchingSize.sm;
    final dial = widget.compact ? (isSm ? 28.0 : 36.0) : (isSm ? 48.0 : 64.0);

    final arcs = SizedBox(
      width: dial,
      height: dial,
      child: AnimatedBuilder(
        animation: _ctrl,
        builder: (context, _) {
          return CustomPaint(painter: _WifiArcsPainter(t: _ctrl.value));
        },
      ),
    );

    final label = widget.showLabel
        ? _SearchingLabel(
            text: l10n.searching,
            animation: _ctrl,
            fontSize: isSm || widget.compact ? 12 : 14,
          )
        : null;

    if (widget.compact) {
      return Semantics(
        liveRegion: true,
        label: l10n.searching,
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            arcs,
            if (label != null) ...[const SizedBox(width: 8), label],
          ],
        ),
      );
    }

    return Semantics(
      liveRegion: true,
      label: l10n.searching,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          arcs,
          if (label != null) ...[SizedBox(height: isSm ? 10 : 14), label],
        ],
      ),
    );
  }
}

class _SearchingLabel extends StatelessWidget {
  const _SearchingLabel({
    required this.text,
    required this.animation,
    required this.fontSize,
  });

  final String text;
  final Animation<double> animation;
  final double fontSize;

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: animation,
      builder: (context, _) {
        // 0→0.5 reveal LTR, 0.5→1 hide from left (mirrors CSS clip-path).
        final t = (animation.value * 2) % 2;
        final reveal = t <= 1 ? t : 2 - t;
        return Stack(
          alignment: Alignment.center,
          children: [
            Text(
              text.toLowerCase(),
              style: TextStyle(
                fontSize: fontSize,
                fontWeight: FontWeight.w500,
                letterSpacing: 0.2,
                color: const Color(0xFF414856),
              ),
            ),
            ClipRect(
              child: Align(
                alignment: AlignmentDirectional.centerStart,
                widthFactor: reveal.clamp(0.0, 1.0),
                child: Text(
                  text.toLowerCase(),
                  style: TextStyle(
                    fontSize: fontSize,
                    fontWeight: FontWeight.w500,
                    letterSpacing: 0.2,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ),
          ],
        );
      },
    );
  }
}

class _WifiArcsPainter extends CustomPainter {
  _WifiArcsPainter({required this.t});

  final double t;

  static const _front = Color(0xFF4F46E5);
  static const _back = Color(0xFFC3C8DE);

  double _dashOffset(
    double t, {
    required double d0,
    required double d25,
    required double d65,
    required double d80,
  }) {
    // Keyframes from CSS wifi-circle-* animations.
    if (t < 0.25) {
      final u = t / 0.25;
      return d0 + (d25 - d0) * Curves.easeOut.transform(u);
    }
    if (t < 0.65) {
      final u = (t - 0.25) / 0.40;
      return d25 + (d65 - d25) * Curves.easeInOut.transform(u);
    }
    if (t < 0.80) {
      final u = (t - 0.65) / 0.15;
      return d65 + (d80 - d65) * Curves.easeOut.transform(u);
    }
    return d80;
  }

  void _paintRing(
    Canvas canvas,
    Offset c,
    double radius,
    double stroke,
    double dash,
    double gap,
    double offset,
    Color color,
  ) {
    final paint = Paint()
      ..color = color
      ..style = PaintingStyle.stroke
      ..strokeWidth = stroke
      ..strokeCap = StrokeCap.round;
    canvas.save();
    canvas.translate(c.dx, c.dy);
    canvas.rotate(-100 * math.pi / 180);
    canvas.translate(-c.dx, -c.dy);
    final path = Path()
      ..addArc(Rect.fromCircle(center: c, radius: radius), 0, math.pi * 2);
    canvas.drawPath(
      dashPath(
        path,
        dashArray: CircularIntervalList([dash, gap]),
        dashOffset: offset,
      ),
      paint,
    );
    canvas.restore();
  }

  // Minimal dash path (avoids path_drawing dependency).
  Path dashPath(
    Path source, {
    required CircularIntervalList dashArray,
    double dashOffset = 0,
  }) {
    final dest = Path();
    for (final metric in source.computeMetrics()) {
      var distance = dashOffset % (dashArray.dash + dashArray.gap);
      var draw = true;
      while (distance < metric.length) {
        final len = draw ? dashArray.dash : dashArray.gap;
        final next = math.min(distance + len, metric.length);
        if (draw) {
          dest.addPath(metric.extractPath(distance, next), Offset.zero);
        }
        distance = next;
        draw = !draw;
      }
    }
    return dest;
  }

  @override
  void paint(Canvas canvas, Size size) {
    final c = Offset(size.width / 2, size.height / 2);
    final scale = size.shortestSide / 64;

    // Outer ~40r in 86 viewBox → ~29.8 in 64 box
    final outerR = 29.8 * scale;
    final midR = 21.6 * scale;
    final innerR = 13.2 * scale;
    final stroke = 4.5 * scale;

    final outerDash = 14.0 * scale;
    final outerGap = 42.0 * scale;
    final midDash = 10.0 * scale;
    final midGap = 30.0 * scale;
    final innerDash = 6.0 * scale;
    final innerGap = 18.0 * scale;

    // Stagger like CSS delays (as phase on the shared 1.8s loop).
    double phase(double delaySeconds) {
      final shift = delaySeconds / 1.8;
      return (t + (1 - shift)) % 1.0;
    }

    final oBack =
        _dashOffset(phase(0.30), d0: 6, d25: 0, d65: 70, d80: 64) * scale;
    final oFront =
        _dashOffset(phase(0.15), d0: 6, d25: 0, d65: 70, d80: 64) * scale;
    final mBack =
        _dashOffset(phase(0.25), d0: 4, d25: 0, d65: 48, d80: 44) * scale;
    final mFront =
        _dashOffset(phase(0.10), d0: 4, d25: 0, d65: 48, d80: 44) * scale;
    final iBack =
        _dashOffset(phase(0.20), d0: 2, d25: 0, d65: 25, d80: 23) * scale;
    final iFront =
        _dashOffset(phase(0.05), d0: 2, d25: 0, d65: 25, d80: 23) * scale;

    _paintRing(canvas, c, outerR, stroke, outerDash, outerGap, oBack, _back);
    _paintRing(canvas, c, outerR, stroke, outerDash, outerGap, oFront, _front);
    _paintRing(canvas, c, midR, stroke, midDash, midGap, mBack, _back);
    _paintRing(canvas, c, midR, stroke, midDash, midGap, mFront, _front);
    _paintRing(canvas, c, innerR, stroke, innerDash, innerGap, iBack, _back);
    _paintRing(canvas, c, innerR, stroke, innerDash, innerGap, iFront, _front);
  }

  @override
  bool shouldRepaint(covariant _WifiArcsPainter oldDelegate) =>
      oldDelegate.t != t;
}

class CircularIntervalList {
  CircularIntervalList(List<double> values) : dash = values[0], gap = values[1];

  final double dash;
  final double gap;
}
