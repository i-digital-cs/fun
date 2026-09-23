import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/domain/maps_url_parser.dart';

void main() {
  group('MapsUrlParser', () {
    test('parses google maps q coords', () {
      final parsed = MapsUrlParser.parse(
        'https://www.google.com/maps?q=30.0444,31.2357',
      );
      expect(parsed, isNotNull);
      expect(parsed!.lat, closeTo(30.0444, 0.0001));
      expect(parsed.lng, closeTo(31.2357, 0.0001));
    });

    test('parses osm mlat/mlon', () {
      final parsed = MapsUrlParser.parse(
        'https://www.openstreetmap.org/?mlat=25.2&mlon=55.27#map=16/25.2/55.27',
      );
      expect(parsed, isNotNull);
      expect(parsed!.lat, closeTo(25.2, 0.0001));
      expect(parsed.lng, closeTo(55.27, 0.0001));
    });

    test('returns null for junk', () {
      expect(MapsUrlParser.parse('https://example.com'), isNull);
    });
  });
}
