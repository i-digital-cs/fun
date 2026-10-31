/// Mirrors Laravel `App\Support\UserPublicId` (prefix + 6 digits).
abstract final class UserPublicId {
  static const prefix = 'Funity-F';

  static final RegExp _pattern = RegExp(r'^Funity-F\d{6}$');

  static bool isValid(String? value) {
    final v = value?.trim() ?? '';
    return _pattern.hasMatch(v);
  }

  /// Digits-only or case-insensitive full id → canonical `Funity-F######`.
  static String? normalize(String? value) {
    final raw = value?.trim() ?? '';
    if (raw.isEmpty) return null;
    if (isValid(raw)) return raw;

    final full = RegExp(r'^funity-?f\s*(\d{1,6})$', caseSensitive: false)
        .firstMatch(raw);
    if (full != null) {
      return format(int.parse(full.group(1)!));
    }

    if (RegExp(r'^\d{1,6}$').hasMatch(raw)) {
      return format(int.parse(raw));
    }

    return null;
  }

  static String format(int number) {
    final n = number.clamp(0, 999999);
    return '$prefix${n.toString().padLeft(6, '0')}';
  }
}
