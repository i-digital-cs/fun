import 'package:flutter/material.dart';

/// Design tokens mirrored from `DESIGN.md` / `resources/css/app.css`.
abstract final class AppColors {
  static const primary = Color(0xFF4F46E5);
  static const primaryDeep = Color(0xFF4338CA);
  static const primaryViolet = Color(0xFF6D28D9);
  static const primarySoft = Color(0xFFE0E7FF);
  static const primaryInk = Color(0xFF3730A3);

  static const accent = Color(0xFFF97316);
  static const accentAmber = Color(0xFFF59E0B);

  static const success = Color(0xFF10B981);
  static const successTeal = Color(0xFF0D9488);

  static const background = Color(0xFFF8FAFC);
  static const canvas = Color(0xFFF5F7FB);
  static const surface = Color(0xFFFFFFFF);

  static const text = Color(0xFF0F172A);
  static const muted = Color(0xFF64748B);
  static const border = Color(0xFFCBD5E1);
  static const borderSoft = Color(0xFFE7EBF3);

  static const danger = Color(0xFFEF4444);
  static const warning = Color(0xFFF59E0B);

  static const heroOverlayStart = Color(0xF008132C);
  static const heroOverlayMid = Color(0xBA08132C);
  static const heroOverlayEnd = Color(0x1F08132C);

  static const indigoGradient = LinearGradient(
    colors: [primary, primaryViolet],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  static const orangeGradient = LinearGradient(
    colors: [accent, accentAmber],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );

  static const emeraldGradient = LinearGradient(
    colors: [success, successTeal],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );
}
