import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/features/discover/domain/discovery_geo.dart';
import 'package:fun_mobile/features/discover/domain/map_discovery.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_query_controller.dart';

void main() {
  group('DiscoveryGeo', () {
    test('contains New Cairo-ish coords', () {
      expect(DiscoveryGeo.contains(30.03, 31.47), isTrue);
    });

    test('rejects Mountain View / emulator default', () {
      expect(DiscoveryGeo.contains(37.42, -122.08), isFalse);
    });

    test('boundsIntersectEgypt rejects California viewport', () {
      expect(
        DiscoveryGeo.boundsIntersectEgypt(
          north: 38,
          south: 37,
          east: -121,
          west: -123,
        ),
        isFalse,
      );
    });
  });

  group('DiscoveryQueryController SSOT', () {
    test('setQuery normalizes empty to null', () {
      final c = DiscoveryQueryController();
      c.setQuery('  yoga  ');
      expect(c.state.normalizedQ, 'yoga');
      c.setQuery('   ');
      expect(c.state.normalizedQ, isNull);
    });

    test('apply and clear keep category/when independent of q', () {
      final c = DiscoveryQueryController();
      c.apply(q: 'cafe', categoryId: 3, when: 'today');
      expect(c.state.categoryId, 3);
      expect(c.state.when, 'today');
      c.setQuery('tea');
      expect(c.state.categoryId, 3);
      c.clear();
      expect(c.state, const DiscoveryQueryState());
    });
  });

  group('DiscoveryLocationState filters', () {
    test('manual city with in-region coords is origin-aware', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.manual,
        cityId: 12,
        cityName: 'New Cairo',
        distanceKm: 100,
        deviceLat: 30.03,
        deviceLng: 31.47,
      );
      expect(state.hasInRegionOrigin, isTrue);
      final filters = state.toDiscoverFilters(q: 'run');
      expect(filters.discoveryMode, 'manual');
      expect(filters.cityId, 12);
      expect(filters.latitude, 30.03);
      expect(filters.q, 'run');
      expect(filters.allCities, isFalse);
    });

    test('out-of-region device GPS falls back to nationwide filters', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.device,
        distanceKm: 100,
        deviceLat: 37.42,
        deviceLng: -122.08,
        outsideDiscoveryRegion: true,
        shortLabel: 'Mountain View',
      );
      expect(state.hasInRegionOrigin, isFalse);
      final filters = state.toDiscoverFilters();
      expect(filters.discoveryMode, 'nationwide');
      expect(filters.allCities, isTrue);
      expect(filters.latitude, isNull);
    });

    test('nationwide mode ignores prior city', () {
      const state = DiscoveryLocationState(
        mode: DiscoveryLocationMode.nationwide,
        distanceKm: 25,
      );
      final filters = state.toDiscoverFilters(categoryId: 2);
      expect(filters.allCities, isTrue);
      expect(filters.categoryId, 2);
      expect(filters.cityId, isNull);
    });
  });

  group('MapCityEvent images', () {
    test('parses image_url and starts_at from API payload', () {
      final ev = MapCityEvent.fromJson({
        'id': 9,
        'title': 'Sunset run',
        'has_exact_coordinates': false,
        'image_url': 'https://cdn.example/card.jpg',
        'starts_at': '2026-11-01T17:00:00+00:00',
        'category_name': 'Outdoor',
        'members_count': 4,
        'location_precision': 'city_level',
      });
      expect(ev.imageUrl, 'https://cdn.example/card.jpg');
      expect(ev.startsAt, isNotNull);
      expect(ev.categoryName, 'Outdoor');
      expect(ev.membersCount, 4);
    });

    test('missing image_url stays null (placeholder path)', () {
      final ev = MapCityEvent.fromJson({
        'id': 1,
        'title': 'No cover',
        'has_exact_coordinates': true,
        'latitude': 30.0,
        'longitude': 31.0,
      });
      expect(ev.imageUrl, isNull);
    });
  });

  group('MapBounds stale / zoom clamp', () {
    test('low zoom oversized bounds normalize to Egypt', () {
      const wide = MapBounds(
        north: 40,
        south: 10,
        east: 40,
        west: 10,
      );
      final api = wide.forApiZoom(6);
      expect(api.north, MapBounds.egyptMaxLat);
      expect(api.south, MapBounds.egyptMinLat);
    });
  });
}
