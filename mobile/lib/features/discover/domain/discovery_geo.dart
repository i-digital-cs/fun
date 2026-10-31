import 'package:fun_mobile/features/discover/domain/map_discovery.dart';

/// Shared Egypt discovery envelope (parity with DiscoverMapService).
abstract final class DiscoveryGeo {
  static const minLat = MapBounds.egyptMinLat;
  static const maxLat = MapBounds.egyptMaxLat;
  static const minLng = MapBounds.egyptMinLng;
  static const maxLng = MapBounds.egyptMaxLng;

  static bool contains(double latitude, double longitude) {
    return latitude >= minLat &&
        latitude <= maxLat &&
        longitude >= minLng &&
        longitude <= maxLng;
  }

  static bool boundsIntersectEgypt({
    required double north,
    required double south,
    required double east,
    required double west,
  }) {
    return south <= maxLat &&
        north >= minLat &&
        west <= maxLng &&
        east >= minLng;
  }
}
