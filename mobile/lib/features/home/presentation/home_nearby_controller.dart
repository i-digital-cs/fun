import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/presentation/event_favorite_actions.dart';
import 'package:fun_mobile/features/home/domain/weather.dart';

class HomeNearbyState {
  const HomeNearbyState({
    this.items = const [],
    this.startingSoon = const [],
    this.categories = const [],
    this.weather,
    this.nowPlaying,
    this.participations,
    this.isLoading = false,
    this.error,
    this.nationwideFallback = false,
  });

  final List<EventItem> items;
  final List<EventItem> startingSoon;
  final List<CategoryItem> categories;
  final WeatherSnapshot? weather;
  final NowPlayingFeed? nowPlaying;
  final HomeParticipations? participations;
  final bool isLoading;
  final String? error;

  /// Blade dashboard: nearby empty → show nationwide + notice.
  final bool nationwideFallback;

  HomeNearbyState copyWith({
    List<EventItem>? items,
    List<EventItem>? startingSoon,
    List<CategoryItem>? categories,
    WeatherSnapshot? weather,
    NowPlayingFeed? nowPlaying,
    HomeParticipations? participations,
    bool? isLoading,
    String? error,
    bool clearError = false,
    bool clearWeather = false,
    bool? nationwideFallback,
  }) {
    return HomeNearbyState(
      items: items ?? this.items,
      startingSoon: startingSoon ?? this.startingSoon,
      categories: categories ?? this.categories,
      weather: clearWeather ? null : (weather ?? this.weather),
      nowPlaying: nowPlaying ?? this.nowPlaying,
      participations: participations ?? this.participations,
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
      final home = _ref.read(homeRepositoryProvider);

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
      final nowPlayingResult = await repo.nowPlaying(cityId: user?.cityId);

      WeatherSnapshot? weather;
      if (hasCity) {
        final weatherResult = await home.weather(cityId: user!.cityId);
        weather = weatherResult.data;
      }

      HomeParticipations? participations;
      if (user != null && user.canParticipate) {
        final partResult = await home.participations();
        participations = partResult.data;
      }

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
        weather: weather,
        clearWeather: !hasCity,
        nowPlaying: nowPlayingResult.data,
        participations: participations,
        nationwideFallback: nationwideFallback,
        error: error,
        clearError: error == null,
      );
    } catch (_) {
      state = state.copyWith(
        isLoading: false,
        error: 'Network error',
        nationwideFallback: false,
      );
    }
  }

  Future<void> toggleFavorite(int eventId) async {
    final outcome = await toggleFavoriteInList(
      repository: _ref.read(eventsRepositoryProvider),
      items: state.items,
      eventId: eventId,
      onOptimistic: (items) => state = state.copyWith(items: items),
    );
    state = state.copyWith(items: outcome.items);
  }

  /// Returns error message on failure; otherwise updates local join_action.
  Future<String?> cancelJoinRequest(int eventId) async {
    final result =
        await _ref.read(eventsRepositoryProvider).cancelJoinRequest(eventId);
    if (result.failure != null) return result.failure!.message;
    final next = result.data ?? 'request_to_join';
    state = state.copyWith(
      items: [
        for (final e in state.items)
          if (e.id == eventId) e.copyWith(joinAction: next) else e,
      ],
    );
    return null;
  }
}

final homeNearbyControllerProvider =
    StateNotifierProvider<HomeNearbyController, HomeNearbyState>((ref) {
  return HomeNearbyController(ref);
});
