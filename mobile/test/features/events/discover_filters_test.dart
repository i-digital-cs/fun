import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';

void main() {
  group('DiscoverFilters.toQuery', () {
    test('nationwide sends empty city_id and discovery_mode', () {
      const filters = DiscoverFilters(
        allCities: true,
        cityId: 3,
        distanceKm: 25,
        page: 2,
      );
      final q = filters.toQuery();
      expect(q['discovery_mode'], 'nationwide');
      expect(q['city_id'], '');
      expect(q['distance_km'], 25);
      expect(q['page'], 2);
    });

    test('city mode includes city_id and distance', () {
      const filters = DiscoverFilters(
        cityId: 7,
        distanceKm: 50,
        categoryId: 2,
        when: 'weekend',
        q: ' hike ',
      );
      final q = filters.toQuery();
      expect(q['discovery_mode'], 'manual');
      expect(q['city_id'], 7);
      expect(q['distance_km'], 50);
      expect(q['category_id'], 2);
      expect(q['when'], 'weekend');
      expect(q['q'], 'hike');
    });

    test('omitted city without allCities defaults to nationwide city_id=', () {
      const filters = DiscoverFilters(allCities: false);
      final q = filters.toQuery();
      expect(q['discovery_mode'], 'nationwide');
      expect(q['city_id'], '');
    });
  });

  group('DiscoverFilters.toQueryString', () {
    test('keeps city_id= for nationwide', () {
      const filters = DiscoverFilters(allCities: true, page: 1);
      final qs = filters.toQueryString();
      expect(qs.split('&').any((p) => p == 'city_id='), isTrue);
      expect(qs, contains('page=1'));
    });
  });
}
