import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';

class GradientButton extends StatelessWidget {
  const GradientButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.gradient = AppColors.orangeGradient,
    this.shadowColor = AppColors.accent,
    this.isLoading = false,
    this.icon,
  });

  factory GradientButton.indigo({
    Key? key,
    required String label,
    required VoidCallback? onPressed,
    bool isLoading = false,
    IconData? icon,
  }) {
    return GradientButton(
      key: key,
      label: label,
      onPressed: onPressed,
      gradient: AppColors.indigoGradient,
      shadowColor: AppColors.primary,
      isLoading: isLoading,
      icon: icon,
    );
  }

  final String label;
  final VoidCallback? onPressed;
  final Gradient gradient;
  final Color shadowColor;
  final bool isLoading;
  final IconData? icon;

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !isLoading;

    return DecoratedBox(
      decoration: BoxDecoration(
        gradient: enabled ? gradient : null,
        color: enabled ? null : AppColors.border,
        borderRadius: BorderRadius.circular(12),
        boxShadow: enabled
            ? [
                BoxShadow(
                  color: shadowColor.withValues(alpha: 0.30),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ]
            : null,
      ),
      child: Material(
        type: MaterialType.transparency,
        child: InkWell(
          onTap: enabled ? onPressed : null,
          borderRadius: BorderRadius.circular(12),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
            child: Center(
              child: isLoading
                  ? const FunLoader(
                      size: FunLoaderSize.sm,
                      showLabel: false,
                    )
                  : Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        if (icon != null) ...[
                          Icon(icon, color: Colors.white, size: 18),
                          const SizedBox(width: 8),
                        ],
                        Text(
                          label,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.w800,
                            fontSize: 15,
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
