import 'package:flutter/foundation.dart';
import 'package:geolocator/geolocator.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Foreground-only discovery GPS. Never background-tracks; never persists coords.
class DiscoveryLocationService {
  DiscoveryLocationService(this._prefs);

  final SharedPreferences _prefs;

  static const _prefUseDevice = 'discovery_use_device_location';

  bool get useDeviceLocationPreferred =>
      _prefs.getBool(_prefUseDevice) ?? false;

  Future<void> setUseDeviceLocationPreferred(bool value) async {
    await _prefs.setBool(_prefUseDevice, value);
  }

  /// Request when-in-use permission and return a one-shot position, or null.
  Future<({double lat, double lng})?> obtainForegroundPosition() async {
    if (kIsWeb) {
      // Web uses browser geolocation from the Blade/JS path; Flutter web
      // discovery still prefers city unless Geolocator is configured.
    }

    final serviceEnabled = await Geolocator.isLocationServiceEnabled();
    if (!serviceEnabled) {
      return null;
    }

    var permission = await Geolocator.checkPermission();
    if (permission == LocationPermission.denied) {
      permission = await Geolocator.requestPermission();
    }
    if (permission == LocationPermission.denied ||
        permission == LocationPermission.deniedForever) {
      await setUseDeviceLocationPreferred(false);
      return null;
    }

    // Foreground one-shot only — no stream / background.
    final position = await Geolocator.getCurrentPosition(
      locationSettings: const LocationSettings(
        accuracy: LocationAccuracy.medium,
        timeLimit: Duration(seconds: 12),
      ),
    );

    final lat = position.latitude;
    final lng = position.longitude;
    if (lat < -90 || lat > 90 || lng < -180 || lng > 180) {
      return null;
    }

    return (lat: lat, lng: lng);
  }
}
