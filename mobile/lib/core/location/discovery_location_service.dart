import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

enum DiscoveryLocationMode { manual, device, nationwide }

/// Authoritative discovery-location preferences (manual / device / nationwide).
///
/// Device coordinates are **never** persisted — only mode, city id/name, radius.
class DiscoveryLocationService {
  DiscoveryLocationService(this._prefs);

  final SharedPreferences _prefs;

  static const _prefMode = 'discovery_location_mode';
  static const _prefUseDevice = 'discovery_use_device_location'; // legacy
  static const _prefCityId = 'discovery_manual_city_id';
  static const _prefCityName = 'discovery_manual_city_name';
  static const _prefDistanceKm = 'discovery_distance_km';

  DiscoveryLocationMode get mode {
    final raw = _prefs.getString(_prefMode);
    if (raw == 'device') return DiscoveryLocationMode.device;
    if (raw == 'nationwide') return DiscoveryLocationMode.nationwide;
    if (raw == 'manual') return DiscoveryLocationMode.manual;
    // Migrate legacy boolean.
    if (_prefs.getBool(_prefUseDevice) == true) {
      return DiscoveryLocationMode.device;
    }
    return DiscoveryLocationMode.manual;
  }

  bool get useDeviceLocationPreferred => mode == DiscoveryLocationMode.device;

  int? get manualCityId {
    final id = _prefs.getInt(_prefCityId);
    return id != null && id > 0 ? id : null;
  }

  String? get manualCityName {
    final name = _prefs.getString(_prefCityName);
    return (name == null || name.isEmpty) ? null : name;
  }

  int? get distanceKm {
    final km = _prefs.getInt(_prefDistanceKm);
    return km;
  }

  Future<void> setMode(DiscoveryLocationMode mode) async {
    await _prefs.setString(_prefMode, mode.name);
    await _prefs.setBool(
      _prefUseDevice,
      mode == DiscoveryLocationMode.device,
    );
  }

  Future<void> setUseDeviceLocationPreferred(bool value) async {
    await setMode(
      value ? DiscoveryLocationMode.device : DiscoveryLocationMode.manual,
    );
  }

  Future<void> setManualCity({required int cityId, required String name}) async {
    await _prefs.setInt(_prefCityId, cityId);
    await _prefs.setString(_prefCityName, name);
    await setMode(DiscoveryLocationMode.manual);
  }

  Future<void> setDistanceKm(int km) async {
    await _prefs.setInt(_prefDistanceKm, km);
  }

  Future<void> clearManualCity() async {
    await _prefs.remove(_prefCityId);
    await _prefs.remove(_prefCityName);
  }

  /// Request when-in-use permission and return a one-shot position, or null.
  /// [failure] is set for diagnostics: denied | timeout | unavailable | invalid.
  Future<({double lat, double lng, double accuracyM, String? failure})>
  obtainForegroundPosition() async {
    if (kIsWeb) {
      // Flutter web discovery prefers city unless Geolocator is configured.
    }

    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return (lat: 0.0, lng: 0.0, accuracyM: 0.0, failure: 'unavailable');
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      return (lat: 0.0, lng: 0.0, accuracyM: 0.0, failure: 'denied');
    }

    try {
      final position = await Geolocator.getCurrentPosition(
        locationSettings: const LocationSettings(
          accuracy: LocationAccuracy.high,
          timeLimit: Duration(seconds: 12),
        ),
      );

      final lat = position.latitude;
      final lng = position.longitude;
      if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
        return (lat: 0.0, lng: 0.0, accuracyM: 0.0, failure: 'invalid');
      }

      return (
        lat: lat,
        lng: lng,
        accuracyM: position.accuracy,
        failure: null,
      );
    } on Exception {
      return (lat: 0.0, lng: 0.0, accuracyM: 0.0, failure: 'timeout');
    }
  }
}
