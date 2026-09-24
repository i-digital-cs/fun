import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/auth/data/auth_remote_source.dart';
import 'package:fun_mobile/features/auth/data/auth_repository_impl.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/data/catalog_remote_source.dart';
import 'package:fun_mobile/features/catalog/data/catalog_repository_impl.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/events/data/events_remote_source.dart';
import 'package:fun_mobile/features/events/data/events_repository_impl.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/home/data/home_remote_source.dart';
import 'package:fun_mobile/features/identity/data/identity_remote_source.dart';
import 'package:fun_mobile/features/profile/data/profile_remote_source.dart';
import 'package:fun_mobile/features/safety/data/safety_remote_source.dart';
import 'package:shared_preferences/shared_preferences.dart';

final sharedPreferencesProvider = Provider<SharedPreferences>((ref) {
  throw UnimplementedError('Override sharedPreferencesProvider in main()');
});

final tokenStorageProvider = Provider<TokenStorage>((ref) {
  return SecureTokenStorage();
});

final localeCodeProvider = StateProvider<String>((ref) {
  final prefs = ref.watch(sharedPreferencesProvider);
  return prefs.getString('locale') ?? 'en';
});

final apiClientProvider = Provider<ApiClient>((ref) {
  final storage = ref.watch(tokenStorageProvider);
  final locale = ref.watch(localeCodeProvider);
  final client = ApiClient(
    tokenStorage: storage,
    locale: locale,
    onUnauthorized: () {
      ref.read(authControllerProvider.notifier).markLoggedOut();
    },
  );
  return client;
});

final authRemoteSourceProvider = Provider<AuthRemoteSource>((ref) {
  return AuthRemoteSource(ref.watch(apiClientProvider));
});

final authRepositoryProvider = Provider<AuthRepository>((ref) {
  return AuthRepositoryImpl(
    remote: ref.watch(authRemoteSourceProvider),
    tokenStorage: ref.watch(tokenStorageProvider),
  );
});

final eventsRemoteSourceProvider = Provider<EventsRemoteSource>((ref) {
  return EventsRemoteSource(ref.watch(apiClientProvider));
});

final eventsRepositoryProvider = Provider<EventsRepository>((ref) {
  return EventsRepositoryImpl(ref.watch(eventsRemoteSourceProvider));
});

final catalogRemoteSourceProvider = Provider<CatalogRemoteSource>((ref) {
  return CatalogRemoteSource(ref.watch(apiClientProvider));
});

final catalogRepositoryProvider = Provider<CatalogRepository>((ref) {
  return CatalogRepositoryImpl(ref.watch(catalogRemoteSourceProvider));
});

/// Active categories for sidebar explore rail (Blade `nav_explore`).
final exploreCategoriesProvider =
    FutureProvider.autoDispose<List<CategoryItem>>((ref) async {
  final result = await ref.watch(catalogRepositoryProvider).categories();
  if (result.failure != null || result.data == null) return const [];
  return result.data!.take(5).toList();
});

final profileRemoteSourceProvider = Provider<ProfileRemoteSource>((ref) {
  return ProfileRemoteSource(ref.watch(apiClientProvider));
});

final identityRemoteSourceProvider = Provider<IdentityRemoteSource>((ref) {
  return IdentityRemoteSource(ref.watch(apiClientProvider));
});

final safetyRemoteSourceProvider = Provider<SafetyRemoteSource>((ref) {
  return SafetyRemoteSource(ref.watch(apiClientProvider));
});

final homeRemoteSourceProvider = Provider<HomeRemoteSource>((ref) {
  return HomeRemoteSource(ref.watch(apiClientProvider));
});
