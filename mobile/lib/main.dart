import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/app.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  Env.assertSafeForRelease();

  // Debug-only install fingerprint for emulator QA (dart-define BUILD_PROOF).
  assert(() {
    const proof = String.fromEnvironment('BUILD_PROOF', defaultValue: '');
    // ignore: avoid_print
    print(
      'FUNNITY_BUILD_PROOF=${proof.isEmpty ? 'none' : proof} '
      'api=${Env.apiBaseUrl} flavor=${Env.flavor}',
    );
    return true;
  }());

  // Cap decoded image memory so long Discover/Home scrolls do not thrash RAM.
  // (Logical sizes on AppNetworkImage still bound individual decodes.)
  final imageCache = PaintingBinding.instance.imageCache;
  imageCache.maximumSize = 120;
  imageCache.maximumSizeBytes = 80 << 20; // 80 MiB

  FlutterError.onError = (details) {
    FlutterError.presentError(details);
  };

  final prefs = await SharedPreferences.getInstance();

  runApp(
    ProviderScope(
      overrides: [sharedPreferencesProvider.overrideWithValue(prefs)],
      child: const FunApp(),
    ),
  );
}
