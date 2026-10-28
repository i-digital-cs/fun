import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/app.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  Env.assertSafeForRelease();

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
