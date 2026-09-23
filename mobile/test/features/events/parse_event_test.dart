import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/data/events_remote_source.dart';

void main() {
  group('EventsRemoteSource.parseEvent', () {
    test('coerces string numbers for cost and distance', () {
      final event = EventsRemoteSource.parseEvent({
        'id': '12',
        'title': 'Night walk',
        'venue': 'Corniche',
        'max_participants': '10',
        'members_count': '3',
        'cost_per_person': '15.5',
        'distance_km': '2.25',
        'created_by': '9',
        'category': {'id': '4', 'name': 'Outdoors'},
        'city': {'id': '1', 'name': 'Manama'},
      });

      expect(event.id, 12);
      expect(event.maxParticipants, 10);
      expect(event.membersCount, 3);
      expect(event.costPerPerson, 15.5);
      expect(event.distanceKm, 2.25);
      expect(event.createdBy, 9);
      expect(event.category?.id, 4);
      expect(event.city?.id, 1);
      expect(event.isFree, isFalse);
    });

    test('parses place pin fields', () {
      final event = EventsRemoteSource.parseEvent({
        'id': 2,
        'title': 'Pinned meetup',
        'venue': 'Park',
        'max_participants': 5,
        'members_count': 1,
        'latitude': '30.04',
        'longitude': '31.23',
        'maps_url': 'https://www.google.com/maps?q=30.04,31.23',
        'maps_open_url': 'https://www.google.com/maps?q=30.04,31.23',
        'has_place_pin': true,
      });
      expect(event.hasPlacePin, isTrue);
      expect(event.latitude, closeTo(30.04, 0.001));
      expect(event.longitude, closeTo(31.23, 0.001));
      expect(event.mapsOpenUrl, isNotNull);
    });

    test('treats null cost as free', () {
      final event = EventsRemoteSource.parseEvent({
        'id': 1,
        'title': 'Free meetup',
        'venue': 'Park',
        'max_participants': 5,
        'members_count': 1,
        'cost_per_person': null,
      });
      expect(event.isFree, isTrue);
    });
  });
}
