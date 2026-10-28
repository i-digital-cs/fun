import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';

class DiscoveryLocationState {
  const DiscoveryLocationState({
    this.mode = DiscoveryLocationMode.manual,
    this.cityId,
    this.cityName,
    this.distanceKm = 25,
    this.deviceLat,
    this.deviceLng,
    this.accuracyM,
    this.isDetecting = false,
    this.error,
    this.lastDetectedAt,
  });

  final DiscoveryLocationMode mode;
  final int? cityId;
  final String? cityName;
  final int distanceKm;
  final double? deviceLat;
  final double? deviceLng;
  final double? accuracyM;
  final bool isDetecting;
  final String? error;
  final DateTime? lastDetectedAt;

  bool get hasManualCity => cityId != null && cityName != null;
  bool get hasDeviceFix => deviceLat != null && deviceLng != null;

  String get summaryLabel {
    if (mode == DiscoveryLocationMode.device) {
      if (hasDeviceFix) {
        final acc = accuracyM;
        if (acc != null && acc > 0) {
          return 'Device · ±${acc.round()} m';
        }
        return 'Device location';
      }
      return 'Device · locating…';
    }
    if (hasManualCity) return cityName!;
    return 'All cities';
  }

  DiscoveryLocationState copyWith({
    DiscoveryLocationMode? mode,
    int? cityId,
    String? cityName,
    int? distanceKm,
    double? deviceLat,
    double? deviceLng,
    double? accuracyM,
    bool? isDetecting,
    String? error,
    DateTime? lastDetectedAt,
    bool clearCity = false,
    bool clearDevice = false,
    bool clearError = false,
  }) {
    return DiscoveryLocationState(
      mode: mode ?? this.mode,
      cityId: clearCity ? null : (cityId ?? this.cityId),
      cityName: clearCity ? null : (cityName ?? this.cityName),
      distanceKm: distanceKm ?? this.distanceKm,
      deviceLat: clearDevice ? null : (deviceLat ?? this.deviceLat),
      deviceLng: clearDevice ? null : (deviceLng ?? this.deviceLng),
      accuracyM: clearDevice ? null : (accuracyM ?? this.accuracyM),
      isDetecting: isDetecting ?? this.isDetecting,
      error: clearError ? null : (error ?? this.error),
      lastDetectedAt: clearDevice
          ? null
          : (lastDetectedAt ?? this.lastDetectedAt),
    );
  }

  /// Filters for Discover / home — never mixes manual city with GPS.
  DiscoverFilters toDiscoverFilters({
    String? q,
    int? categoryId,
    String? when,
    int page = 1,
  }) {
    if (mode == DiscoveryLocationMode.device && hasDeviceFix) {
      return DiscoverFilters(
        distanceKm: distanceKm,
        latitude: deviceLat,
        longitude: deviceLng,
        q: q,
        categoryId: categoryId,
        when: when,
        page: page,
        allCities: false,
      );
    }
    if (mode == DiscoveryLocationMode.manual && cityId != null) {
      return DiscoverFilters(
        cityId: cityId,
        distanceKm: distanceKm,
        q: q,
        categoryId: categoryId,
        when: when,
        page: page,
        allCities: false,
      );
    }
    return DiscoverFilters(
      allCities: true,
      distanceKm: distanceKm,
      q: q,
      categoryId: categoryId,
      when: when,
      page: page,
    );
  }
}

class DiscoveryLocationController extends StateNotifier<DiscoveryLocationState> {
  DiscoveryLocationController(this._ref) : super(const DiscoveryLocationState()) {
    _hydrate();
  }

  final Ref _ref;

  DiscoveryLocationService get _service =>
      _ref.read(discoveryLocationServiceProvider);

  void _hydrate() {
    final auth = _ref.read(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final service = _service;
    final mode = service.mode;
    final cityId = service.manualCityId ?? user?.cityId;
    final cityName = service.manualCityName ?? user?.cityName;
    final distance =
        service.distanceKm ?? user?.distanceKm ?? 25;
    state = DiscoveryLocationState(
      mode: mode,
      cityId: cityId,
      cityName: cityName,
      distanceKm: distance,
    );
  }

  Future<void> selectManualCity({
    required int cityId,
    required String name,
    int? distanceKm,
  }) async {
    final km = distanceKm ?? state.distanceKm;
    await _service.setManualCity(cityId: cityId, name: name);
    await _service.setDistanceKm(km);
    state = state.copyWith(
      mode: DiscoveryLocationMode.manual,
      cityId: cityId,
      cityName: name,
      distanceKm: km,
      clearDevice: true,
      clearError: true,
    );
  }

  Future<void> setNationwide({int? distanceKm}) async {
    final km = distanceKm ?? state.distanceKm;
    await _service.clearManualCity();
    await _service.setMode(DiscoveryLocationMode.manual);
    await _service.setDistanceKm(km);
    state = state.copyWith(
      mode: DiscoveryLocationMode.manual,
      clearCity: true,
      clearDevice: true,
      distanceKm: km,
      clearError: true,
    );
  }

  Future<void> setDistanceKm(int km) async {
    await _service.setDistanceKm(km);
    state = state.copyWith(distanceKm: km, clearError: true);
  }

  Future<String?> enableDeviceLocation() async {
    state = state.copyWith(
      mode: DiscoveryLocationMode.device,
      isDetecting: true,
      clearError: true,
    );
    await _service.setMode(DiscoveryLocationMode.device);
    final pos = await _service.obtainForegroundPosition();
    if (pos == null) {
      state = state.copyWith(
        isDetecting: false,
        clearDevice: true,
        error: 'Could not detect location. Check permissions and try again.',
      );
      return state.error;
    }
    state = state.copyWith(
      isDetecting: false,
      deviceLat: pos.lat,
      deviceLng: pos.lng,
      accuracyM: pos.accuracyM,
      lastDetectedAt: DateTime.now(),
      clearError: true,
    );
    return null;
  }

  Future<String?> retryDeviceLocation() => enableDeviceLocation();

  Future<void> useManualMode() async {
    await _service.setMode(DiscoveryLocationMode.manual);
    state = state.copyWith(
      mode: DiscoveryLocationMode.manual,
      clearDevice: true,
      clearError: true,
    );
  }
}

final discoveryLocationControllerProvider =
    StateNotifierProvider<DiscoveryLocationController, DiscoveryLocationState>(
      (ref) => DiscoveryLocationController(ref),
    );
