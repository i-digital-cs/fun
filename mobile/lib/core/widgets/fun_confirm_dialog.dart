import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/cancel_stop_button.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Blade `x-ui.confirm-dialog` + tapping-hand.
Future<bool> showFunConfirm(
  BuildContext context, {
  required String message,
  String? title,
  String? confirmLabel,
}) async {
  final result = await showGeneralDialog<bool>(
    context: context,
    barrierDismissible: true,
    barrierLabel: MaterialLocalizations.of(context).modalBarrierDismissLabel,
    barrierColor: const Color(0x8C020617),
    transitionDuration: const Duration(milliseconds: 220),
    pageBuilder: (ctx, anim, secondary) {
      return FunConfirmDialog(
        message: message,
        title: title,
        confirmLabel: confirmLabel,
      );
    },
    transitionBuilder: (ctx, anim, secondary, child) {
      final curved = CurvedAnimation(parent: anim, curve: Curves.easeOutCubic);
      return FadeTransition(
        opacity: curved,
        child: ScaleTransition(
          scale: Tween<double>(begin: 0.94, end: 1).animate(curved),
          child: child,
        ),
      );
    },
  );
  return result == true;
}

class FunConfirmDialog extends StatelessWidget {
  const FunConfirmDialog({
    super.key,
    required this.message,
    this.title,
    this.confirmLabel,
  });

  final String message;
  final String? title;
  final String? confirmLabel;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Center(
      child: Material(
        color: Colors.transparent,
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 384),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12),
            child: DecoratedBox(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(color: AppColors.borderSoft),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.22),
                    blurRadius: 32,
                    offset: const Offset(0, 16),
                  ),
                ],
              ),
              child: Padding(
                padding: const EdgeInsets.fromLTRB(24, 16, 24, 24),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const TappingHand(),
                    const SizedBox(height: 12),
                    Text(
                      title ?? l10n.confirmTitle,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.3,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      message,
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        fontSize: 14,
                        height: 1.45,
                        color: AppColors.muted.withValues(alpha: 0.95),
                      ),
                    ),
                    const SizedBox(height: 22),
                    Row(
                      children: [
                        Expanded(
                          child: Center(
                            child: CancelStopButton(
                              label: l10n.cancel,
                              size: CancelStopSize.sm,
                              onPressed: () => Navigator.pop(context, false),
                            ),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: DecoratedBox(
                            decoration: BoxDecoration(
                              gradient: AppColors.orangeGradient,
                              borderRadius: BorderRadius.circular(12),
                              boxShadow: [
                                BoxShadow(
                                  color: AppColors.accent.withValues(
                                    alpha: 0.32,
                                  ),
                                  blurRadius: 14,
                                  offset: const Offset(0, 6),
                                ),
                              ],
                            ),
                            child: Material(
                              type: MaterialType.transparency,
                              child: InkWell(
                                onTap: () => Navigator.pop(context, true),
                                borderRadius: BorderRadius.circular(12),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(
                                    vertical: 14,
                                    horizontal: 8,
                                  ),
                                  child: Text(
                                    confirmLabel ?? l10n.confirmYes,
                                    textAlign: TextAlign.center,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w800,
                                      fontSize: 14,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Blade `.tapping-hand` — palm on the end side, four stacked fingers
/// tapping toward the start (CSS inset-inline-end).
class TappingHand extends StatefulWidget {
  const TappingHand({super.key});

  @override
  State<TappingHand> createState() => _TappingHandState();
}

class _TappingHandState extends State<TappingHand>
    with SingleTickerProviderStateMixin {
  late final AnimationController _ctl;

  @override
  void initState() {
    super.initState();
    _ctl = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1200),
    )..repeat();
  }

  @override
  void dispose() {
    _ctl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final rtl = Directionality.of(context) == TextDirection.rtl;

    return SizedBox(
      height: 96,
      width: double.infinity,
      child: Center(
        // Blade flips only in RTL; paint is authored for LTR.
        child: Transform.scale(
          scaleX: rtl ? -1.0 : 1.0,
          child: AnimatedBuilder(
            animation: _ctl,
            builder: (context, _) {
              return CustomPaint(
                size: const Size(168, 88),
                painter: _TappingHandPainter(t: _ctl.value),
              );
            },
          ),
        ),
      ),
    );
  }
}

class _TappingHandPainter extends CustomPainter {
  _TappingHandPainter({required this.t});

  final double t;

  static const _skin = Color(0xFFE4C560);

  @override
  void paint(Canvas canvas, Size size) {
    // Palm sits in the right half (Blade margin-inline-start: 80 on 80×60 hand).
    const palmW = 80.0;
    const palmH = 60.0;
    final palmLeft = size.width - palmW - 8;
    const palmTop = 12.0;
    final palm = Rect.fromLTWH(palmLeft, palmTop, palmW, palmH);

    // Shadow (::before)
    final shadow = RRect.fromRectAndCorners(
      Rect.fromLTWH(
        palm.left - palmW * 0.6,
        palm.top + palmH * 0.7,
        palmW * 1.8,
        palmH * 0.75,
      ),
      topLeft: const Radius.circular(40),
      topRight: const Radius.circular(10),
      bottomLeft: const Radius.circular(40),
      bottomRight: const Radius.circular(10),
    );
    canvas.drawRRect(
      shadow,
      Paint()
        ..color = Colors.black.withValues(alpha: 0.28)
        ..maskFilter = const MaskFilter.blur(BlurStyle.normal, 10),
    );

    // Four fingers — same anchor, different scale/brightness (Blade stacks them).
    for (var i = 0; i < 4; i++) {
      _paintFinger(canvas, palm, i);
    }

    // Palm — border-radius: 10px 40px → TL/BR=10, TR/BL=40
    canvas.drawRRect(
      RRect.fromRectAndCorners(
        palm,
        topLeft: const Radius.circular(10),
        topRight: const Radius.circular(40),
        bottomLeft: const Radius.circular(40),
        bottomRight: const Radius.circular(10),
      ),
      Paint()..color = _skin,
    );

    // Thumb
    _paintThumb(canvas, palm);
  }

  void _paintFinger(Canvas canvas, Rect palm, int index) {
    final stagger = index * 0.08;
    final local = (t - stagger) % 1.0;
    final tap = local < 0.4
        ? Curves.easeInOut.transform(local / 0.4)
        : local < 0.5
            ? 1.0
            : Curves.easeInOut.transform(
                ((1.0 - local) / 0.5).clamp(0.0, 1.0),
              );

    // CSS: rotate(10deg) → rotate(50deg) with scale 0.4 / 0.6 / 0.8 / 1.0
    final scale = 0.4 + index * 0.2;
    final degrees = 10.0 + tap * 40.0;
    final brightness = 0.7 + index * 0.1;
    final color = Color.lerp(Colors.black, _skin, brightness)!;

    // Finger box: width 80% of palm, height 35; right edge at inset-end 64%
    final fingerW = palm.width * 0.8;
    final fingerH = 35.0;
    final fingerRight = palm.right - palm.width * 0.64;
    final fingerBottom = palm.bottom - palm.height * 0.32;
    final origin = Offset(fingerRight, fingerBottom - fingerH + 20);

    canvas.save();
    canvas.translate(origin.dx, origin.dy);
    canvas.rotate(degrees * math.pi / 180);
    canvas.scale(scale);

    // Proximal segment (main finger body) — extends left from origin
    final body = RRect.fromRectAndRadius(
      Rect.fromLTWH(-fingerW, -20, fingerW, fingerH),
      const Radius.circular(12),
    );
    canvas.drawRRect(body, Paint()..color = color);

    // Distal tip (::before) — width 140%, rotate -60deg from near tip joint
    canvas.save();
    final tipOrigin = Offset(-fingerW * 0.65, -20 + fingerH * 0.5);
    canvas.translate(tipOrigin.dx, tipOrigin.dy);
    canvas.rotate(-60 * math.pi / 180);
    final tipW = fingerW * 1.4;
    final tip = RRect.fromRectAndRadius(
      Rect.fromLTWH(-tipW, -15, tipW, 30),
      const Radius.circular(20),
    );
    canvas.drawRRect(tip, Paint()..color = color);
    canvas.restore();

    canvas.restore();
  }

  void _paintThumb(Canvas canvas, Rect palm) {
    final thumbW = palm.width * 1.2;
    final thumbH = 38.0;
    final right = palm.right - palm.width * 0.01;
    final bottom = palm.bottom + palm.height * 0.18;
    // transform-origin ≈ (100% - 20px, 20px) of thumb
    final origin = Offset(right - 20, bottom - thumbH + 20);

    canvas.save();
    canvas.translate(origin.dx, origin.dy);
    canvas.rotate(-20 * math.pi / 180);

    final thumb = RRect.fromRectAndCorners(
      Rect.fromLTWH(-(thumbW - 20), -20, thumbW, thumbH),
      topLeft: const Radius.circular(30),
      topRight: const Radius.circular(20),
      bottomRight: const Radius.circular(20),
      bottomLeft: const Radius.circular(10),
    );
    canvas.drawRRect(thumb, Paint()..color = _skin);

    // Highlight (::after)
    final shine = RRect.fromRectAndCorners(
      Rect.fromLTWH(-(thumbW - 20) + 5, 4, thumbW * 0.2, thumbH * 0.6),
      topLeft: const Radius.circular(18),
      topRight: const Radius.circular(4),
      bottomRight: const Radius.circular(4),
      bottomLeft: const Radius.circular(12),
    );
    canvas.drawRRect(
      shine,
      Paint()..color = Colors.white.withValues(alpha: 0.3),
    );

    canvas.restore();
  }

  @override
  bool shouldRepaint(covariant _TappingHandPainter oldDelegate) =>
      oldDelegate.t != t;
}
