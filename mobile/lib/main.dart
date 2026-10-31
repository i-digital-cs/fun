import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/app.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/core/qa/fake_chats_repository.dart';
import 'package:fun_mobile/core/qa/fake_dms_bus.dart';
import 'package:fun_mobile/core/qa/fake_dms_repository.dart';
import 'package:fun_mobile/core/qa/fake_qa_auth_repository.dart';
import 'package:fun_mobile/core/qa/fake_qa_users.dart';
import 'package:fun_mobile/core/qa/in_memory_token_storage.dart';
import 'package:fun_mobile/core/qa/safe_mock.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:shared_preferences/shared_preferences.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  Env.assertSafeForRelease();
  SafeMock.assertIsolatedFromRelease();

  // Debug-only install fingerprint for emulator QA (dart-define BUILD_PROOF).
  assert(() {
    const proof = String.fromEnvironment('BUILD_PROOF', defaultValue: '');
    // ignore: avoid_print
    print(
      'FUNNITY_BUILD_PROOF=${proof.isEmpty ? 'none' : proof} '
      'api=${Env.apiBaseUrl} flavor=${Env.flavor} '
      'safe_mock=${SafeMock.isActive}',
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

  final overrides = <Override>[
    sharedPreferencesProvider.overrideWithValue(prefs),
  ];

  if (SafeMock.isActive) {
    final bus = FakeDmsBus.instance..seed();
    final auth = FakeQaAuthRepository(bus: bus);
    final tokens = InMemoryTokenStorage(initialToken: 'safe-mock-token');
    overrides.addAll([
      tokenStorageProvider.overrideWithValue(tokens),
      authRepositoryProvider.overrideWithValue(auth),
      chatsRepositoryProvider.overrideWithValue(FakeChatsRepository()),
      // Production 401s (e.g. FCM device-token register) must not eject mock session.
      apiClientProvider.overrideWith((ref) {
        final locale = ref.watch(localeCodeProvider);
        return ApiClient(
          tokenStorage: ref.watch(tokenStorageProvider),
          locale: locale,
          onUnauthorized: () {
            if (SafeMock.isActive) return;
            ref.read(authControllerProvider.notifier).markLoggedOut();
          },
        );
      }),
      dmsRepositoryProvider.overrideWith((ref) {
        return FakeDmsRepository(
          bus: bus,
          viewerId: () {
            final state = ref.read(authControllerProvider);
            if (state is Authenticated) return state.user.id;
            return FakeQaUsers.userA.id;
          },
        );
      }),
    ]);
  }

  runApp(
    ProviderScope(
      overrides: overrides,
      child: const FunApp(),
    ),
  );
}
