import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/discover/data/discover_map_remote.dart';
import 'package:fun_mobile/features/discover/domain/map_discovery.dart';

class DiscoverMapState {
  const DiscoverMapState({
    this.markers = const [],
    this.clusters = const [],
    this.cityAggregates = const [],
    this.isLoading = false,
    this.error,
    this.selectedMarker,
    this.selectedCity,
    this.cityEvents = const [],
    this.showSearchArea = false,
  });

  final List<MapDiscoveryMarker> markers;
  final List<MapDiscoveryCluster> clusters;
  final List<MapCityAggregate> cityAggregates;
  final bool isLoading;
  final String? error;
  final MapDiscoveryMarker? selectedMarker;
  final MapCityAggregate? selectedCity;
  final List<MapCityEvent> cityEvents;
  final bool showSearchArea;

  DiscoverMapState copyWith({
    List<MapDiscoveryMarker>? markers,
    List<MapDiscoveryCluster>? clusters,
    List<MapCityAggregate>? cityAggregates,
    bool? isLoading,
    String? error,
    MapDiscoveryMarker? selectedMarker,
    MapCityAggregate? selectedCity,
    List<MapCityEvent>? cityEvents,
    bool clearSelected = false,
    bool clearError = false,
    bool? showSearchArea,
  }) {
    return DiscoverMapState(
      markers: markers ?? this.markers,
      clusters: clusters ?? this.clusters,
      cityAggregates: cityAggregates ?? this.cityAggregates,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
      selectedMarker:
          clearSelected ? null : (selectedMarker ?? this.selectedMarker),
      selectedCity: clearSelected ? null : (selectedCity ?? this.selectedCity),
      cityEvents: clearSelected ? const [] : (cityEvents ?? this.cityEvents),
      showSearchArea: showSearchArea ?? this.showSearchArea,
    );
  }
}

class DiscoverMapController extends StateNotifier<DiscoverMapState> {
  DiscoverMapController(this._ref) : super(const DiscoverMapState());

  final Ref _ref;
  int _requestId = 0;

  DiscoverMapRemote get _remote =>
      DiscoverMapRemote(_ref.read(apiClientProvider));

  Future<void> searchArea(
    MapBounds bounds, {
    required int zoom,
    int? categoryId,
    String? q,
    String? when,
    bool includeApproximate = false,
    String? discoveryMode,
    double? latitude,
    double? longitude,
    int? distanceKm,
  }) async {
    final id = ++_requestId;
    state = state.copyWith(isLoading: true, clearError: true, showSearchArea: false);
    final result = await _remote.fetch(
      bounds,
      zoom: zoom,
      categoryId: categoryId,
      q: q,
      when: when,
      includeApproximate: includeApproximate,
      discoveryMode: discoveryMode,
      latitude: latitude,
      longitude: longitude,
      distanceKm: distanceKm,
    );
    if (id != _requestId) return;
    if (result.failure != null) {
      state = state.copyWith(isLoading: false, error: result.failure!.message);
      return;
    }
    state = state.copyWith(
      isLoading: false,
      markers: result.data!.markers,
      clusters: result.data!.clusters,
      cityAggregates: result.data!.cityAggregates,
      clearError: true,
    );
  }

  void markMapMoved() {
    if (!state.showSearchArea) {
      state = state.copyWith(showSearchArea: true);
    }
  }

  void selectMarker(MapDiscoveryMarker marker) {
    state = state.copyWith(
      selectedMarker: marker,
      selectedCity: null,
      cityEvents: const [],
    );
  }

  Future<void> selectCityAggregate(
    MapCityAggregate aggregate, {
    int? categoryId,
    String? q,
  }) async {
    if (aggregate.isCluster || aggregate.cityId <= 0) {
      state = DiscoverMapState(
        markers: state.markers,
        clusters: state.clusters,
        cityAggregates: state.cityAggregates,
        selectedCity: aggregate,
        showSearchArea: state.showSearchArea,
      );
      return;
    }
    state = DiscoverMapState(
      markers: state.markers,
      clusters: state.clusters,
      cityAggregates: state.cityAggregates,
      isLoading: true,
      selectedCity: aggregate,
      showSearchArea: state.showSearchArea,
    );
    final result = await _remote.cityEvents(
      aggregate.cityId,
      categoryId: categoryId,
      q: q,
    );
    if (result.failure != null) {
      state = state.copyWith(isLoading: false, error: result.failure!.message);
      return;
    }
    state = DiscoverMapState(
      markers: state.markers,
      clusters: state.clusters,
      cityAggregates: state.cityAggregates,
      selectedCity: aggregate,
      cityEvents: result.data?.events ?? const [],
      showSearchArea: state.showSearchArea,
    );
  }

  void clearSelection() {
    state = state.copyWith(clearSelected: true);
  }

  /// Focus map selection from a list/sheet event (exact or city-level).
  void focusListEvent({
    required int eventId,
    double? latitude,
    double? longitude,
    int? cityId,
    String? title,
    String? venueLabel,
  }) {
    if (latitude != null && longitude != null) {
      selectMarker(
        MapDiscoveryMarker(
          id: eventId,
          latitude: latitude,
          longitude: longitude,
          title: title ?? '',
          locationPrecision: 'exact',
          venueLabel: venueLabel,
        ),
      );
      return;
    }
    if (cityId != null) {
      final agg = state.cityAggregates
          .where((a) => !a.isCluster && a.cityId == cityId)
          .cast<MapCityAggregate?>()
          .firstWhere((a) => a != null, orElse: () => null);
      if (agg != null) {
        selectCityAggregate(agg);
      }
    }
  }
}

final discoverMapControllerProvider =
    StateNotifierProvider<DiscoverMapController, DiscoverMapState>(
      (ref) => DiscoverMapController(ref),
    );
