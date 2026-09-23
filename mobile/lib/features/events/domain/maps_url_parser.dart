/// Extract lat/lng from Google Maps / OSM share links (no API key).
class MapsUrlParser {
  const MapsUrlParser._();

  static ({double lat, double lng})? parse(String raw) {
    var url = raw.trim();
    if (url.isEmpty) return null;
    if (!RegExp(r'^https?://', caseSensitive: false).hasMatch(url)) {
      url = 'https://$url';
    }

    final patterns = <RegExp>[
      RegExp(r'@(-?\d+\.?\d*),(-?\d+\.?\d*)(?:,|/|$)'),
      RegExp(r'!3d(-?\d+\.?\d*)!4d(-?\d+\.?\d*)'),
      RegExp(r'[?&](?:q|query)=(?:loc:)?(-?\d+\.?\d*),(-?\d+\.?\d*)'),
      RegExp(r'[?&](?:ll|center)=(-?\d+\.?\d*),(-?\d+\.?\d*)'),
      RegExp(r'[?&]destination=(-?\d+\.?\d*),(-?\d+\.?\d*)'),
      RegExp(r'[?&]mlat=(-?\d+\.?\d*).*?[?&]mlon=(-?\d+\.?\d*)'),
      RegExp(r'#map=\d+/(-?\d+\.?\d*)/(-?\d+\.?\d*)'),
      RegExp(r'(-?\d{1,2}\.\d{3,}),\s*(-?\d{1,3}\.\d{3,})'),
    ];

    for (final pattern in patterns) {
      final m = pattern.firstMatch(url);
      if (m == null) continue;
      final lat = double.tryParse(m.group(1)!);
      final lng = double.tryParse(m.group(2)!);
      if (lat == null || lng == null) continue;
      if (lat < -90 || lat > 90 || lng < -180 || lng > 180) continue;
      if (lat == 0 && lng == 0) continue;
      return (lat: lat, lng: lng);
    }
    return null;
  }

  static String openStreetMapUrl(double lat, double lng) {
    return 'https://www.openstreetmap.org/?mlat=$lat&mlon=$lng#map=16/$lat/$lng';
  }

  static String googleOpenUrl(double lat, double lng) {
    return 'https://www.google.com/maps?q=$lat,$lng';
  }
}
