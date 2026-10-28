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

      expect(filters.discoveryMode, 'manual');
      expect(filters.cityId, 12);
      expect(filters.allCities, isFalse);
      expect(filters.distanceKm, 25);
      expect(filters.latitude, isNull);
      expect(filters.longitude, isNull);
      expect(filters.toQuery().containsKey('latitude'), isFalse);
      expect(filters.toQuery()['city_id'], 12);
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
      final query = filters.toQuery();

      expect(filters.discoveryMode, 'device');
      expect(query['discovery_mode'], 'device');
      expect(query.containsKey('city_id'), isFalse);
      expect(query['latitude'], 30.2285);
      expect(query['longitude'], 31.4769);
      expect(query['distance_km'], 10);
    });

    test('nationwide mode clears city and coords', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.nationwide,
        distanceKm: 0,
      );

      final filters = state.toDiscoverFilters();
      final query = filters.toQuery();

      expect(filters.discoveryMode, 'nationwide');
      expect(query['discovery_mode'], 'nationwide');
      expect(query['city_id'], '');
      expect(query.containsKey('latitude'), isFalse);
      expect(state.summaryLabel, 'All Egypt');
    });

    test('summaryLabel distinguishes current location from city name', () {
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

      expect(device.summaryLabel, contains('Current location'));
      expect(device.summaryLabel, contains('40'));
      expect(manual.summaryLabel, 'Giza');
      expect(manual.radiusLabel(), 'Within 25 km');
    });
  });
}
