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
      scopes: const ['email', 'profile'],
      // Web client ID → backend verifies `aud` against GOOGLE_CLIENT_ID.
      serverClientId: serverClientId.isEmpty ? null : serverClientId,
    );
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
      return fallback;
    }

    if (code == 'network_error') {
      return e.message?.isNotEmpty == true ? e.message! : fallback;
    }

    if (kDebugMode) {
      return '${e.message ?? fallback} ($code)';
    }
    return e.message?.isNotEmpty == true ? e.message! : fallback;
  }
}
