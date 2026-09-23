import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';

class HomeNearbyState {
  const HomeNearbyState({
    this.items = const [],
    this.startingSoon = const [],
    this.categories = const [],
    this.isLoading = false,
    this.error,
    this.nationwideFallback = false,
  });

  final List<EventItem> items;
  final List<EventItem> startingSoon;
  final List<CategoryItem> categories;
  final bool isLoading;
  final String? error;

  /// Blade dashboard: nearby empty → show nationwide + notice.
  final bool nationwideFallback;

  HomeNearbyState copyWith({
    List<EventItem>? items,
    List<EventItem>? startingSoon,
    List<CategoryItem>? categories,
    bool? isLoading,
    String? error,
    bool clearError = false,
    bool? nationwideFallback,
  }) {
    return HomeNearbyState(
      items: items ?? this.items,
      startingSoon: startingSoon ?? this.startingSoon,
      categories: categories ?? this.categories,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
      nationwideFallback: nationwideFallback ?? this.nationwideFallback,
    );
  }
}

class HomeNearbyController extends StateNotifier<HomeNearbyState> {
  HomeNearbyController(this._ref) : super(const HomeNearbyState());

  final Ref _ref;

  Future<void> load() async {
    state = state.copyWith(isLoading: true, clearError: true);
    try {
      final auth = _ref.read(authControllerProvider);
      final user = auth is Authenticated ? auth.user : null;
      final repo = _ref.read(eventsRepositoryProvider);
      final catalog = _ref.read(catalogRepositoryProvider);

      final hasCity = user?.cityId != null;
      final nearbyFilters = hasCity
          ? DiscoverFilters(
              cityId: user!.cityId,
              distanceKm: user.distanceKm,
              page: 1,
              allCities: false,
            )
          : const DiscoverFilters(allCities: true, page: 1);

      const soonFilters = DiscoverFilters(
        allCities: true,
        page: 1,
        perPage: 30,
      );

      final nearbyResult = await repo.discover(nearbyFilters);
      final soonResult = await repo.discover(soonFilters);
      final categoriesResult = await catalog.categories();

      final categories = categoriesResult.data ?? state.categories;

      var items = nearbyResult.data?.items ?? const <EventItem>[];
      var nationwideFallback = false;
      String? error = nearbyResult.failure?.message;

      // Nearby failed or empty (with a city set) → nationwide fallback (Blade).
      final shouldFallback =
          hasCity && (items.isEmpty || nearbyResult.failure != null);
      if (shouldFallback) {
        final fallback = await repo.discover(
          const DiscoverFilters(allCities: true, page: 1),
        );
        if (fallback.data != null && fallback.data!.items.isNotEmpty) {
          items = fallback.data!.items;
          nationwideFallback = true;
          error = null;
        } else if (items.isEmpty) {
          error = fallback.failure?.message ?? error;
        }
      }

      final now = DateTime.now();
      final cutoff = now.add(const Duration(hours: 48));
      final soonSource = soonResult.data?.items ?? const <EventItem>[];
      final soonItems = soonSource.where((e) {
        final starts = e.startsAt;
        if (starts == null) return false;
        return !starts.isBefore(now) && !starts.isAfter(cutoff);
      }).toList()
        ..sort((a, b) => a.startsAt!.compareTo(b.startsAt!));

      state = state.copyWith(
        isLoading: false,
        items: items,
        startingSoon: soonItems.take(4).toList(),
        categories: categories,
        nationwideFallback: nationwideFallback,
        error: error,
        clearError: error == null,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString(),
        nationwideFallback: false,
      );
    }
  }
}

final homeNearbyControllerProvider =
    StateNotifierProvider<HomeNearbyController, HomeNearbyState>((ref) {
  return HomeNearbyController(ref);
});
