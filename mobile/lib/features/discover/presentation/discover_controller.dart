import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/presentation/event_favorite_actions.dart';

class DiscoverState {
  const DiscoverState({
    this.items = const [],
    this.filters = const DiscoverFilters(allCities: true),
    this.isLoading = false,
    this.isSearching = false,
    this.error,
    this.hasMore = false,
  });

  final List<EventItem> items;
  final DiscoverFilters filters;
  final bool isLoading;

  /// True while a typed `q` request is in flight (wifi Searching UI).
  final bool isSearching;
  final String? error;
  final bool hasMore;

  bool get isNationwide => filters.allCities;

  DiscoverState copyWith({
    List<EventItem>? items,
    DiscoverFilters? filters,
    bool? isLoading,
    bool? isSearching,
    String? error,
    bool clearError = false,
    bool? hasMore,
  }) {
    return DiscoverState(
      items: items ?? this.items,
      filters: filters ?? this.filters,
      isLoading: isLoading ?? this.isLoading,
      isSearching: isSearching ?? this.isSearching,
      error: clearError ? null : (error ?? this.error),
      hasMore: hasMore ?? this.hasMore,
    );
  }
}

class DiscoverController extends StateNotifier<DiscoverState> {
  DiscoverController(this._ref) : super(const DiscoverState());

  final Ref _ref;
  bool _bootstrapped = false;
  bool _loadingMore = false;

  /// Latest-request-wins generation (NET-RACE-001).
  int _loadGeneration = 0;

  Future<void> ensureLoaded() async {
    if (_bootstrapped && state.items.isNotEmpty) return;
    _bootstrapped = true;
    await loadNationwide();
  }

  Future<void> load({DiscoverFilters? filters, bool append = false}) async {
    final nextFilters = filters ?? state.filters;
    final typedSearch =
        nextFilters.q != null && nextFilters.q!.trim().length >= 2;
    final generation = ++_loadGeneration;
    state = state.copyWith(
      isLoading: !typedSearch || state.items.isEmpty,
      isSearching: typedSearch,
      clearError: true,
      filters: nextFilters,
    );

    final result = await _ref
        .read(eventsRepositoryProvider)
        .discover(nextFilters);

    if (generation != _loadGeneration) return;

    if (result.failure != null || result.data == null) {
      state = state.copyWith(
        isLoading: false,
        isSearching: false,
        error: result.failure?.message,
        hasMore: append ? false : state.hasMore,
      );
      return;
    }

    final page = result.data!;
    state = state.copyWith(
      isLoading: false,
      isSearching: false,
      items: append ? [...state.items, ...page.items] : page.items,
      hasMore: page.hasMore,
      filters: nextFilters,
    );
  }

  Future<void> loadMore() async {
    if (!state.hasMore || state.isLoading || _loadingMore) return;
    _loadingMore = true;
    try {
      await load(
        filters: state.filters.copyWith(page: state.filters.page + 1),
        append: true,
      );
    } finally {
      _loadingMore = false;
    }
  }

  Future<void> applyFilters({
    String? q,
    int? cityId,
    int? categoryId,
    int? distanceKm,
    String? when,
    required bool allCities,
  }) {
    return load(
      filters: DiscoverFilters(
        q: q,
        cityId: allCities ? null : cityId,
        categoryId: categoryId,
        distanceKm: allCities ? null : distanceKm,
        when: when,
        page: 1,
        allCities: allCities,
      ),
    );
  }

  Future<void> clearFilters() {
    return loadNationwide();
  }

  Future<void> loadNationwide({String? q, int? categoryId}) {
    return load(
      filters: DiscoverFilters(
        q: q,
        categoryId: categoryId,
        page: 1,
        allCities: true,
      ),
    );
  }

  Future<void> loadNearby({
    required int cityId,
    int? distanceKm,
    String? q,
    int? categoryId,
  }) {
    return load(
      filters: DiscoverFilters(
        q: q,
        cityId: cityId,
        categoryId: categoryId,
        distanceKm: distanceKm,
        page: 1,
        allCities: false,
      ),
    );
  }

  Future<void> search(String q) {
    return load(
      filters: state.filters.copyWith(q: q, page: 1, clearQ: q.trim().isEmpty),
    );
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
    final result = await _ref
        .read(eventsRepositoryProvider)
        .cancelJoinRequest(eventId);
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

final discoverControllerProvider =
    StateNotifierProvider<DiscoverController, DiscoverState>((ref) {
      return DiscoverController(ref);
    });
