import 'dart:io' show Platform;

import 'package:flutter/foundation.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';

/// Sign in with Apple (iOS / macOS). Returns identity token + optional name.
class AppleSignInHelper {
  static bool get isSupported {
    if (kIsWeb) return false;
    try {
      return Platform.isIOS || Platform.isMacOS;
    } catch (_) {
      return false;
    }
  }

  static Future<AuthorizationCredentialAppleID?> signIn() async {
    if (!isSupported) return null;
    final available = await SignInWithApple.isAvailable();
    if (!available) return null;

    return SignInWithApple.getAppleIDCredential(
      scopes: [
        AppleIDAuthorizationScopes.email,
        AppleIDAuthorizationScopes.fullName,
      ],
    );
  }
}
