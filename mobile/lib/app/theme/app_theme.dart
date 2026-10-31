import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:google_fonts/google_fonts.dart';

/// Locale-aware Material theme.
///
/// Arabic uses **bundled** [arabicFontFamily] (Tajawal) — no runtime download.
/// English keeps IBM Plex Sans via `google_fonts` (unchanged).
abstract final class AppTheme {
  /// Declared in `pubspec.yaml` → `assets/fonts/Tajawal/*.ttf`.
  static const String arabicFontFamily = 'Tajawal';

  static ThemeData light({required Locale locale}) {
    final isArabic = locale.languageCode == 'ar';
    final textTheme = isArabic
        ? _tajawalTextTheme()
        : GoogleFonts.ibmPlexSansTextTheme();

    final colored = textTheme.apply(
      bodyColor: AppColors.text,
      displayColor: AppColors.text,
    );

    final String? family = isArabic ? arabicFontFamily : null;

    TextStyle? pinFamily(TextStyle? style) {
      if (style == null) return null;
      if (family == null) return style;
      return style.copyWith(fontFamily: family);
    }

    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      fontFamily: family,
      colorScheme: ColorScheme.fromSeed(
        seedColor: AppColors.primary,
        primary: AppColors.primary,
        secondary: AppColors.accent,
        surface: AppColors.surface,
        error: AppColors.danger,
      ),
      scaffoldBackgroundColor: AppColors.canvas,
      textTheme: colored,
      primaryTextTheme: colored,
      appBarTheme: AppBarTheme(
        backgroundColor: AppColors.surface,
        foregroundColor: AppColors.text,
        elevation: 0,
        centerTitle: false,
        titleTextStyle: pinFamily(
          colored.titleLarge?.copyWith(
            fontWeight: FontWeight.w800,
            color: AppColors.text,
          ),
        ),
        toolbarTextStyle: pinFamily(colored.bodyMedium),
      ),
      dialogTheme: DialogThemeData(
        titleTextStyle: pinFamily(
          colored.titleLarge?.copyWith(
            fontWeight: FontWeight.w700,
            color: AppColors.text,
          ),
        ),
        contentTextStyle: pinFamily(colored.bodyMedium),
      ),
      bottomSheetTheme: const BottomSheetThemeData(
        backgroundColor: AppColors.surface,
        surfaceTintColor: Colors.transparent,
      ),
      snackBarTheme: SnackBarThemeData(
        contentTextStyle: pinFamily(colored.bodyMedium?.copyWith(color: Colors.white)),
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          textStyle: pinFamily(
            colored.labelLarge?.copyWith(fontWeight: FontWeight.w700),
          ),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          textStyle: pinFamily(
            colored.labelLarge?.copyWith(fontWeight: FontWeight.w700),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          textStyle: pinFamily(
            colored.labelLarge?.copyWith(fontWeight: FontWeight.w600),
          ),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          textStyle: pinFamily(
            colored.labelLarge?.copyWith(fontWeight: FontWeight.w600),
          ),
        ),
      ),
      navigationBarTheme: NavigationBarThemeData(
        labelTextStyle: WidgetStatePropertyAll(
          pinFamily(
            colored.labelSmall?.copyWith(fontWeight: FontWeight.w600),
          ),
        ),
      ),
      navigationRailTheme: NavigationRailThemeData(
        selectedLabelTextStyle: pinFamily(
          colored.labelMedium?.copyWith(fontWeight: FontWeight.w700),
        ),
        unselectedLabelTextStyle: pinFamily(colored.labelMedium),
      ),
      tabBarTheme: TabBarThemeData(
        labelStyle: pinFamily(
          colored.titleSmall?.copyWith(fontWeight: FontWeight.w700),
        ),
        unselectedLabelStyle: pinFamily(colored.titleSmall),
      ),
      chipTheme: ChipThemeData(
        labelStyle: pinFamily(colored.labelMedium),
      ),
      listTileTheme: ListTileThemeData(
        titleTextStyle: pinFamily(colored.titleMedium),
        subtitleTextStyle: pinFamily(colored.bodyMedium),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 14,
          vertical: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.border),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: BorderSide(
            color: AppColors.border.withValues(alpha: 0.7),
          ),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
        ),
        labelStyle: TextStyle(
          color: AppColors.muted,
          fontWeight: FontWeight.w500,
          fontSize: 14,
          fontFamily: family,
        ),
        hintStyle: TextStyle(
          color: AppColors.muted,
          fontWeight: FontWeight.w400,
          fontSize: 14,
          fontFamily: family,
        ),
        errorStyle: TextStyle(
          color: AppColors.danger,
          fontWeight: FontWeight.w500,
          fontSize: 12,
          fontFamily: family,
        ),
      ),
      dividerColor: AppColors.borderSoft,
    );
  }

  /// Material 3 roles with Tajawal pinned on every style (400/500/700/800 via assets).
  static TextTheme _tajawalTextTheme() {
    return ThemeData(useMaterial3: true).textTheme.apply(
      fontFamily: arabicFontFamily,
    );
  }
}
