import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';

class DiscoveryLocationState {
  const DiscoveryLocationState({
    this.mode = DiscoveryLocationMode.nationwide,
    this.cityId,
    this.cityName,
    this.distanceKm = 25,
    this.deviceLat,
    this.deviceLng,
    this.accuracyM,
    this.isDetecting = false,
    this.error,
    this.lastDetectedAt,
    this.isApproximate = false,
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
  final bool isApproximate;

  bool get hasManualCity =>
      mode == DiscoveryLocationMode.manual && cityId != null && cityName != null;
  bool get hasDeviceFix =>
      mode == DiscoveryLocationMode.device &&
      deviceLat != null &&
      deviceLng != null;

  String get summaryLabel {
    if (mode == DiscoveryLocationMode.device) {
      if (hasDeviceFix) {
        final acc = accuracyM;
        if (acc != null && acc > 0) {
          return 'Current location · ±${acc.round()} m';
        }
        return 'Current location';
      }
      return 'Current location · locating…';
    }
    if (hasManualCity) return cityName!;
    return 'All Egypt';
  }

  String radiusLabel() {
    if (distanceKm == 0) return 'All distances';
    return 'Within $distanceKm km';
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
    bool? isApproximate,
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
      isApproximate: clearDevice
          ? false
          : (isApproximate ?? this.isApproximate),
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
        discoveryMode: 'device',
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
        discoveryMode: 'manual',
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
      discoveryMode: 'nationwide',
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

  static const lowAccuracyMeters = 500.0;

  DiscoveryLocationService get _service =>
      _ref.read(discoveryLocationServiceProvider);

  void _hydrate() {
    final auth = _ref.read(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final service = _service;
    var mode = service.mode;
    final cityId = service.manualCityId;
    final cityName = service.manualCityName;
    final distance = service.distanceKm ?? user?.distanceKm ?? 25;

    // Profile city seeds manual mode only when no explicit nationwide/device.
    if (mode == DiscoveryLocationMode.manual && cityId == null && user?.cityId != null) {
      state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.manual,
        cityId: user!.cityId,
        cityName: user.cityName,
        distanceKm: distance,
      );
      return;
    }

    if (mode == DiscoveryLocationMode.manual && cityId == null) {
      mode = DiscoveryLocationMode.nationwide;
    }

    state = DiscoveryLocationState(
      mode: mode,
      cityId: mode == DiscoveryLocationMode.manual ? cityId : null,
      cityName: mode == DiscoveryLocationMode.manual ? cityName : null,
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
    await _service.setMode(DiscoveryLocationMode.nationwide);
    await _service.setDistanceKm(km);
    state = state.copyWith(
      mode: DiscoveryLocationMode.nationwide,
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
      clearCity: true,
      clearDevice: true,
      clearError: true,
    );
    await _service.clearManualCity();
    await _service.setMode(DiscoveryLocationMode.device);
    final pos = await _service.obtainForegroundPosition();
    if (pos.failure != null) {
      final message = switch (pos.failure) {
        'denied' => 'Location permission denied. Pick a city manually or retry.',
        'timeout' => 'Location request timed out. Retry or pick a city manually.',
        'invalid' => 'Invalid coordinates received. Retry or pick a city manually.',
        _ => 'Could not detect location. Check permissions and try again.',
      };
      state = state.copyWith(
        isDetecting: false,
        clearDevice: true,
        error: message,
      );
      return state.error;
    }
    final approximate = pos.accuracyM > lowAccuracyMeters;
    state = state.copyWith(
      isDetecting: false,
      deviceLat: pos.lat,
      deviceLng: pos.lng,
      accuracyM: pos.accuracyM,
      isApproximate: approximate,
      lastDetectedAt: DateTime.now(),
      clearCity: true,
      clearError: !approximate,
      error: approximate
          ? 'Location is approximate (±${pos.accuracyM.round()} m). Retry or pick a city.'
          : null,
    );
    return approximate ? state.error : null;
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
