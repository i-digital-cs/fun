import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';

void main() {
  group('DiscoveryLocationState.toDiscoverFilters', () {
    test('manual city uses city_id and never sends device coords', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.manual,
        cityId: 12,
        cityName: '6th of October City',
        distanceKm: 25,
        deviceLat: 30.0,
        deviceLng: 31.0,
      );

      final filters = state.toDiscoverFilters();

      expect(filters.cityId, 12);
      expect(filters.allCities, isFalse);
      expect(filters.distanceKm, 25);
      expect(filters.latitude, isNull);
      expect(filters.longitude, isNull);
    });

    test('device mode with fix sends lat/lng without city_id', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.device,
        cityId: 12,
        cityName: 'Saved City',
        distanceKm: 10,
        deviceLat: 30.2285,
        deviceLng: 31.4769,
        accuracyM: 35,
      );

      final filters = state.toDiscoverFilters();

      expect(filters.cityId, isNull);
      expect(filters.latitude, 30.2285);
      expect(filters.longitude, 31.4769);
      expect(filters.distanceKm, 10);
      expect(filters.allCities, isFalse);
    });

    test('device mode without fix falls back to nationwide filters', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.device,
        distanceKm: 25,
      );

      final filters = state.toDiscoverFilters();

      expect(filters.allCities, isTrue);
      expect(filters.latitude, isNull);
      expect(filters.cityId, isNull);
    });

    test('summaryLabel distinguishes device accuracy from city name', () {
      const device = DiscoveryLocationState(
        mode: DiscoveryLocationMode.device,
        deviceLat: 1,
        deviceLng: 2,
        accuracyM: 40,
      );
      const manual = DiscoveryLocationState(
        mode: DiscoveryLocationMode.manual,
        cityId: 1,
        cityName: 'Giza',
      );

      expect(device.summaryLabel, contains('Device'));
      expect(device.summaryLabel, contains('40'));
      expect(manual.summaryLabel, 'Giza');
    });
  });
}
