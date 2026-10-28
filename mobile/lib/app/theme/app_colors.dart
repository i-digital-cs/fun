import 'package:flutter/material.dart';

/// Design tokens mirrored from `DESIGN.md` / `resources/css/app.css` (Funnity V2).
abstract final class AppColors {
  static const primary = Color(0xFF7138F5);
  static const primaryDeep = Color(0xFF5825D6);
  static const primaryViolet = Color(0xFF9E65BC);
  static const primarySoft = Color(0xFFF1EBFF);
  static const primaryInk = Color(0xFF3B1577);

  static const accent = Color(0xFFFF762E);
  static const accentAmber = Color(0xFFFF9A5C);
  static const accentSoft = Color(0xFFFFF1E7);

  static const success = Color(0xFF159B76);
  static const successTeal = Color(0xFF0D9488);

  static const background = Color(0xFFF8F9FE);
  static const canvas = Color(0xFFF8F9FE);
  static const surface = Color(0xFFFFFFFF);

  static const text = Color(0xFF19213D);
  static const muted = Color(0xFF64748B);
  static const border = Color(0xFFE8EAF2);
  static const borderSoft = Color(0xFFE8EAF2);

  static const danger = Color(0xFFEF4444);
  static const warning = Color(0xFFF59E0B);

  static const heroOverlayStart = Color(0xEB3B1577);
  static const heroOverlayMid = Color(0xC77138F5);
  static const heroOverlayEnd = Color(0x8CFF762E);

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

  static const heroGradient = LinearGradient(
    colors: [Color(0xFF3B1577), primary, accent],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
