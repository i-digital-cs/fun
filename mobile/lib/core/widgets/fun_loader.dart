import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

enum FunLoaderSize { sm, md, lg }

/// System loader — mirrors web `x-ui.loader` (Uiverse santhosh_4929).
class FunLoader extends StatefulWidget {
  const FunLoader({
    super.key,
    this.size = FunLoaderSize.md,
    this.label,
    this.showLabel = true,
  });

  final FunLoaderSize size;
  final String? label;
  final bool showLabel;

  @override
  State<FunLoader> createState() => _FunLoaderState();
}

class _FunLoaderState extends State<FunLoader>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2000),
    )..repeat();
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  double get _diameter => switch (widget.size) {
    FunLoaderSize.sm => 10,
    FunLoaderSize.lg => 40,
    FunLoaderSize.md => 32,
  };

  double get _gap => switch (widget.size) {
    FunLoaderSize.sm => 5,
    FunLoaderSize.lg => 12,
    FunLoaderSize.md => 9,
  };

  int get _count => widget.size == FunLoaderSize.sm ? 3 : 6;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final label = widget.label ?? l10n.loading;
    final textSize = switch (widget.size) {
      FunLoaderSize.sm => 12.0,
      FunLoaderSize.lg => 20.0,
      FunLoaderSize.md => 16.0,
    };

    return Semantics(
      label: label,
      liveRegion: true,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AnimatedBuilder(
            animation: _controller,
            builder: (context, _) {
              return Row(
                mainAxisSize: MainAxisSize.min,
                children: List.generate(_count, (index) {
                  final delay = index * 0.1;
                  final t = (_controller.value + delay) % 1.0;
                  final wave = (t < 0.5)
                      ? Curves.easeInOut.transform(t * 2)
                      : Curves.easeInOut.transform(2 - t * 2);
                  final scale = 1 + (0.2 * wave);
                  final angle = t * 6.28318530718;

                  return Padding(
                    padding: EdgeInsetsDirectional.only(
                      end: index == _count - 1 ? 0 : _gap,
                    ),
                    child: Transform.rotate(
                      angle: angle,
                      child: Transform.scale(
                        scale: scale,
                        child: _NeumorphCircle(size: _diameter),
                      ),
                    ),
                  );
                }),
              );
            },
          ),
          if (widget.showLabel) ...[
            SizedBox(height: widget.size == FunLoaderSize.sm ? 8 : 16),
            AnimatedBuilder(
              animation: _controller,
              builder: (context, child) {
                final pulse =
                    0.4 + 0.6 * (1 - (((_controller.value * 2) % 2) - 1).abs());
                return Opacity(opacity: pulse.clamp(0.4, 1.0), child: child);
              },
              child: Text(
                label,
                style: TextStyle(
                  fontSize: textSize,
                  fontWeight: FontWeight.w400,
                  letterSpacing: 0.6,
                  color: AppColors.muted,
                ),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _NeumorphCircle extends StatelessWidget {
  const _NeumorphCircle({required this.size});

  final double size;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        gradient: const RadialGradient(
          center: Alignment(-0.4, -0.4),
          colors: [Color(0xFFF8FAFC), Color(0xFFC7D2FE)],
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.12),
            offset: Offset(size * 0.12, size * 0.12),
            blurRadius: size * 0.28,
          ),
          BoxShadow(
            color: Colors.white.withValues(alpha: 0.85),
            offset: Offset(-size * 0.12, -size * 0.12),
            blurRadius: size * 0.28,
          ),
        ],
      ),
      child: Align(
        alignment: const Alignment(-0.35, -0.35),
        child: Container(
          width: size * 0.34,
          height: size * 0.34,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            gradient: RadialGradient(
              colors: [
                Colors.white.withValues(alpha: 0.85),
                Colors.white.withValues(alpha: 0),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
