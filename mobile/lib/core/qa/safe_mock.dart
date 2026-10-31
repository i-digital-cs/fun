import 'package:flutter/foundation.dart';

/// Debug-only messaging / auth fixtures for production-safe QA.
///
/// Activate explicitly:
/// ```bash
/// flutter run --dart-define=SAFE_MOCK_MESSAGING=true --dart-define=BUILD_PROOF=phase5
/// ```
///
/// Never enable in release/store builds. [assertIsolatedFromRelease] throws if
/// the define is set while [kReleaseMode] is true.
abstract final class SafeMock {
  static const messagingEnabled = bool.fromEnvironment(
    'SAFE_MOCK_MESSAGING',
    defaultValue: false,
  );

  /// True only when the define is set **and** this is not a release binary.
  static bool get isActive => messagingEnabled && !kReleaseMode;

  /// Call from [main] before [runApp].
  static void assertIsolatedFromRelease() {
    if (kReleaseMode && messagingEnabled) {
      throw StateError(
        'SAFE_MOCK_MESSAGING must never be enabled in release builds. '
        'Omit the dart-define for store / production APKs.',
      );
    }
  }
}
