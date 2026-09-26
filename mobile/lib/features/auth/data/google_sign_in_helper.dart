import 'package:flutter/foundation.dart';
import 'package:flutter/services.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:google_sign_in/google_sign_in.dart';

/// Google Sign-In helper shared by login + register.
class GoogleSignInHelper {
  GoogleSignInHelper._();

  static GoogleSignIn client() {
    final serverClientId = Env.googleServerClientId.trim();
    return GoogleSignIn(
      scopes: const ['email', 'profile', 'openid'],
      // Must be the Firebase/Web client from the SAME Google Cloud project as
      // the Android OAuth client (google-services.json client_type 3).
      serverClientId: serverClientId.isEmpty ? null : serverClientId,
    );
  }

  /// Interactive sign-in. Clears any stale Google session first so a previous
  /// wrong `serverClientId` / SHA config does not keep failing silently.
  static Future<GoogleSignInAccount?> signIn() async {
    final google = client();
    try {
      await google.signOut();
    } catch (_) {
      // Ignore — first run or Play Services unavailable.
    }
    return google.signIn();
  }

  /// Human-readable error for [PlatformException] from `google_sign_in`.
  static String messageFor(PlatformException e, String fallback) {
    final code = e.code;
    final details = '${e.message ?? ''} ${e.details ?? ''}'.toLowerCase();

    // ApiException: 10 = DEVELOPER_ERROR (package name / SHA-1 mismatch).
    if (code == '10' ||
        code == 'sign_in_failed' ||
        details.contains('apiexception: 10') ||
        details.contains('developer_error')) {
      // Always include a concrete hint — this is the #1 real-world failure.
      return '$fallback (error 10: add this device build SHA-1 in Firebase)';
    }

    if (code == 'network_error') {
      return e.message?.isNotEmpty == true ? e.message! : fallback;
    }

    if (code == 'sign_in_canceled' || code == '12501') {
      return fallback;
    }

    // Surface Google/Play error codes so we can diagnose without a debugger.
    final detail = e.message?.trim();
    if (detail != null && detail.isNotEmpty) {
      return '$detail ($code)';
    }
    if (kDebugMode) {
      return '$fallback ($code)';
    }
    return fallback;
  }
}
