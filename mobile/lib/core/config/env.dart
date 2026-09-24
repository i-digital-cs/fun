import 'package:flutter/foundation.dart';

/// Compile-time config via `--dart-define`.
///
/// ## Flavors
///
/// | FLAVOR | Typical API | Use |
/// |--------|-------------|-----|
/// | `prod` (default) | `https://funnity.com/api` | Store / daily device runs |
/// | `dev` | Local or staging URL | Emulator against XAMPP / staging |
///
/// Examples:
/// ```bash
/// flutter run
/// flutter run --dart-define=FLAVOR=dev --dart-define=API_BASE_URL=http://10.0.2.2/Funity/fun/public/api
/// flutter build apk --release --obfuscate --split-debug-info=build/debug-info
/// ```
///
/// **Never** ship a release/store build with a localhost / emulator loopback API.
abstract final class Env {
  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://funnity.com/api',
  );

  static const flavor = String.fromEnvironment('FLAVOR', defaultValue: 'prod');

  /// Web OAuth client ID for Google Sign-In `serverClientId` (id_token audience).
  /// Public client ID (not a secret). Override with `--dart-define` if needed.
  static const googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue:
        '702014926680-h8o7h7pih3ttuo1g2abf5aj4ekqeje97.apps.googleusercontent.com',
  );

  /// Optional Reverb overrides (prefer `/api/site` `broadcast` when unset).
  static const reverbAppKey = String.fromEnvironment('REVERB_APP_KEY');
  static const reverbHost = String.fromEnvironment('REVERB_HOST');
  static const reverbPort = int.fromEnvironment('REVERB_PORT', defaultValue: 0);
  static const reverbScheme = String.fromEnvironment('REVERB_SCHEME');

  static bool get isProd => flavor == 'prod';

  static bool get isDev => flavor == 'dev';

  static bool get usesLocalApi {
    final url = apiBaseUrl.toLowerCase();
    return url.contains('localhost') ||
        url.contains('127.0.0.1') ||
        url.contains('10.0.2.2');
  }

  /// Throws in release if [apiBaseUrl] points at a loopback host.
  static void assertSafeForRelease() {
    if (kReleaseMode && usesLocalApi) {
      throw StateError(
        'Release builds must not use a local API_BASE_URL ($apiBaseUrl). '
        'Omit the define or pass the production URL.',
      );
    }
  }
}
