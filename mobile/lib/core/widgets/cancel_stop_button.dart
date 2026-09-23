import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

enum CancelStopSize { sm, md, block }

/// Blade `x-ui.cancel-btn`: dark control that flips idle label → Stop on press.
class CancelStopButton extends StatefulWidget {
  const CancelStopButton({
    super.key,
    required this.label,
    this.stopLabel,
    required this.onPressed,
    this.size = CancelStopSize.md,
  });

  final String label;
  final String? stopLabel;
  final VoidCallback? onPressed;
  final CancelStopSize size;

  @override
  State<CancelStopButton> createState() => _CancelStopButtonState();
}

class _CancelStopButtonState extends State<CancelStopButton> {
  bool _stopping = false;

  void _setStopping(bool value) {
    if (_stopping == value || !mounted) return;
    setState(() => _stopping = value);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final stop = widget.stopLabel ?? l10n.stop;
    final enabled = widget.onPressed != null;
    final isSm = widget.size == CancelStopSize.sm;
    final isBlock = widget.size == CancelStopSize.block;

    final height = isSm ? 38.0 : 44.0;
    final minWidth = isSm ? 104.0 : 136.0;
    final radius = isSm ? 10.0 : 12.0;
    final fontSize = isSm ? 13.0 : 14.0;

    return Opacity(
      opacity: enabled ? 1 : 0.5,
      child: Listener(
        onPointerDown: enabled ? (_) => _setStopping(true) : null,
        onPointerUp: enabled ? (_) => _setStopping(false) : null,
        onPointerCancel: enabled ? (_) => _setStopping(false) : null,
        child: Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: enabled ? widget.onPressed : null,
            borderRadius: BorderRadius.circular(radius),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              curve: Curves.easeOut,
              height: height,
              width: isBlock ? double.infinity : null,
              constraints: isBlock
                  ? const BoxConstraints(minWidth: 0)
                  : BoxConstraints(minWidth: minWidth),
              padding: EdgeInsets.symmetric(horizontal: isSm ? 14 : 18),
              decoration: BoxDecoration(
                borderRadius: BorderRadius.circular(radius),
                border: Border.all(
                  color: _stopping
                      ? AppColors.danger
                      : const Color(0xFFFB923C),
                  width: 2,
                ),
                gradient: const LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Color(0xFF37353E), Color(0xFF1E1D24)],
                ),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.22),
                    blurRadius: 14,
                    offset: const Offset(0, 6),
                  ),
                  if (_stopping)
                    BoxShadow(
                      color: AppColors.danger.withValues(alpha: 0.28),
                      blurRadius: 0,
                      spreadRadius: 3,
                    ),
                ],
              ),
              child: Stack(
                alignment: Alignment.center,
                children: [
                  AnimatedOpacity(
                    duration: const Duration(milliseconds: 180),
                    opacity: _stopping ? 0 : 1,
                    child: AnimatedSlide(
                      duration: const Duration(milliseconds: 180),
                      offset: _stopping ? const Offset(0, -0.15) : Offset.zero,
                      child: Text(
                        widget.label,
                        style: TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: fontSize,
                        ),
                      ),
                    ),
                  ),
                  AnimatedOpacity(
                    duration: const Duration(milliseconds: 180),
                    opacity: _stopping ? 1 : 0,
                    child: AnimatedSlide(
                      duration: const Duration(milliseconds: 180),
                      offset: _stopping ? Offset.zero : const Offset(0, 0.15),
                      child: Text(
                        stop,
                        style: TextStyle(
                          color: const Color(0xFFFED7AA),
                          fontWeight: FontWeight.w700,
                          fontSize: fontSize,
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
