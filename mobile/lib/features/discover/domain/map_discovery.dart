class MapBounds {
  const MapBounds({
    required this.north,
    required this.south,
    required this.east,
    required this.west,
  });

  final double north;
  final double south;
  final double east;
  final double west;

  /// Egypt envelope — matches DiscoverMapService constants.
  static const egyptMinLat = 21.5;
  static const egyptMaxLat = 32.0;
  static const egyptMinLng = 24.5;
  static const egyptMaxLng = 37.0;

  /// At low zoom, oversized camera bounds are normalized to Egypt
  /// (parity with server nationwide clamp). Higher zoom left unchanged
  /// so the API can still reject unsafe dumps.
  MapBounds forApiZoom(int zoom) {
    final maxLat = zoom <= 7 ? 12.0 : 8.0;
    final maxLng = zoom <= 7 ? 14.0 : 10.0;
    final latSpan = north - south;
    final lngSpan = east - west;
    if (zoom <= 7 && (latSpan > maxLat || lngSpan > maxLng)) {
      return const MapBounds(
        north: egyptMaxLat,
        south: egyptMinLat,
        east: egyptMaxLng,
        west: egyptMinLng,
      );
    }
    return this;
  }

  Map<String, dynamic> toQuery({
    required int zoom,
    int? categoryId,
    String? q,
    String? when,
    bool includeApproximate = false,
    String? discoveryMode,
    double? latitude,
    double? longitude,
    int? distanceKm,
  }) {
    final b = forApiZoom(zoom);
    return {
      'north': b.north,
      'south': b.south,
      'east': b.east,
      'west': b.west,
      'zoom': zoom,
      if (categoryId != null) 'category_id': categoryId,
      if (q != null && q.isNotEmpty) 'q': q,
      if (when != null && when.isNotEmpty) 'when': when,
      'include_approximate': includeApproximate ? 1 : 0,
      if (discoveryMode != null) 'discovery_mode': discoveryMode,
      if (latitude != null) 'latitude': latitude,
      if (longitude != null) 'longitude': longitude,
      if (distanceKm != null) 'distance_km': distanceKm,
    };
  }
}

class MapDiscoveryMarker {
  const MapDiscoveryMarker({
    required this.id,
    required this.latitude,
    required this.longitude,
    required this.title,
    required this.locationPrecision,
    this.startsAt,
    this.categoryName,
    this.imageUrl,
    this.membersCount = 0,
    this.venueLabel,
  });

  final int id;
  final double latitude;
  final double longitude;
  final String title;
  final String locationPrecision;
  final String? startsAt;
  final String? categoryName;
  final String? imageUrl;
  final int membersCount;
  final String? venueLabel;

  factory MapDiscoveryMarker.fromJson(Map<String, dynamic> json) {
    return MapDiscoveryMarker(
      id: json['id'] as int,
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      title: json['title'] as String? ?? '',
      locationPrecision: json['location_precision'] as String? ?? 'exact',
      startsAt: json['starts_at'] as String?,
      categoryName: json['category_name'] as String?,
      imageUrl: json['image_url'] as String?,
      membersCount: (json['members_count'] as num?)?.toInt() ?? 0,
      venueLabel: json['venue_label'] as String?,
    );
  }
}

class MapDiscoveryCluster {
  const MapDiscoveryCluster({
    required this.id,
    required this.latitude,
    required this.longitude,
    required this.count,
    this.expansionZoom = 10,
  });

  final String id;
  final double latitude;
  final double longitude;
  final int count;
  final int expansionZoom;

  factory MapDiscoveryCluster.fromJson(Map<String, dynamic> json) {
    return MapDiscoveryCluster(
      id: json['id']?.toString() ?? '',
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      count: (json['count'] as num?)?.toInt() ?? 0,
      expansionZoom: (json['expansion_zoom'] as num?)?.toInt() ?? 10,
    );
  }
}

class MapCityAggregate {
  const MapCityAggregate({
    required this.id,
    required this.cityId,
    required this.cityName,
    required this.latitude,
    required this.longitude,
    required this.count,
    this.label,
    this.isCluster = false,
    this.expansionZoom = 10,
    this.cityCount = 1,
  });

  final String id;
  final int cityId;
  final String cityName;
  final double latitude;
  final double longitude;
  final int count;
  final String? label;
  final bool isCluster;
  final int expansionZoom;
  final int cityCount;

  factory MapCityAggregate.fromJson(Map<String, dynamic> json) {
    final isCluster = json['type'] == 'city_cluster';
    return MapCityAggregate(
      id: json['id']?.toString() ?? 'city_${json['city_id']}',
      cityId: (json['city_id'] as num?)?.toInt() ?? 0,
      cityName: json['city_name'] as String? ?? '',
      latitude: (json['latitude'] as num).toDouble(),
      longitude: (json['longitude'] as num).toDouble(),
      count: (json['count'] as num?)?.toInt() ?? 0,
      label: json['label'] as String?,
      isCluster: isCluster,
      expansionZoom: (json['expansion_zoom'] as num?)?.toInt() ?? 10,
      cityCount: (json['city_count'] as num?)?.toInt() ?? 1,
    );
  }
}

class MapCityEvent {
  const MapCityEvent({
    required this.id,
    required this.title,
    required this.hasExactCoordinates,
    this.venueLabel,
    this.latitude,
    this.longitude,
    this.cityId,
    this.url,
    this.startsAt,
    this.categoryName,
    this.imageUrl,
    this.membersCount = 0,
    this.locationPrecision,
  });

  final int id;
  final String title;
  final bool hasExactCoordinates;
  final String? venueLabel;
  final double? latitude;
  final double? longitude;
  final int? cityId;
  final String? url;
  final String? startsAt;
  final String? categoryName;
  final String? imageUrl;
  final int membersCount;
  final String? locationPrecision;

  factory MapCityEvent.fromJson(Map<String, dynamic> json) {
    return MapCityEvent(
      id: (json['id'] as num).toInt(),
      title: json['title'] as String? ?? '',
      hasExactCoordinates: json['has_exact_coordinates'] == true,
      venueLabel: json['venue_label'] as String?,
      latitude: (json['latitude'] as num?)?.toDouble(),
      longitude: (json['longitude'] as num?)?.toDouble(),
      cityId: (json['city_id'] as num?)?.toInt(),
      url: json['url'] as String?,
      startsAt: json['starts_at'] as String?,
      categoryName: json['category_name'] as String?,
      imageUrl: json['image_url'] as String?,
      membersCount: (json['members_count'] as num?)?.toInt() ?? 0,
      locationPrecision: json['location_precision'] as String?,
    );
  }
}

class MapDiscoveryPayload {
  const MapDiscoveryPayload({
    this.markers = const [],
    this.clusters = const [],
    this.cityAggregates = const [],
    this.clustered = false,
    this.isSuggestion = false,
    this.selectedRadiusKm,
    this.effectiveRadiusKm,
    this.originAware = false,
  });

  final List<MapDiscoveryMarker> markers;
  final List<MapDiscoveryCluster> clusters;
  final List<MapCityAggregate> cityAggregates;
  final bool clustered;
  final bool isSuggestion;
  final int? selectedRadiusKm;
  final int? effectiveRadiusKm;
  final bool originAware;
}
