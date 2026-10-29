import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/place_suggestion.dart';

class DiscoverFilters {
  const DiscoverFilters({
    this.q,
    this.cityId,
    this.categoryId,
    this.distanceKm,
    this.latitude,
    this.longitude,
    this.when,
    this.perPage,
    this.page = 1,

    /// When true, send empty `city_id` so the API lists nationwide (Blade "All cities").
    /// When false and [cityId] is null, omit `city_id` (API may use the user's city).
    this.allCities = false,

    /// `manual` | `device` | `nationwide` — exclusive origin for Laravel.
    this.discoveryMode,
  });

  final String? q;
  final int? cityId;
  final int? categoryId;
  final int? distanceKm;

  /// Ephemeral device GPS (never stored). Both required for device origin.
  final double? latitude;
  final double? longitude;

  /// API: `today` | `tomorrow` | `weekend`
  final String? when;
  final int? perPage;
  final int page;
  final bool allCities;
  final String? discoveryMode;

  bool get isNationwide => allCities || discoveryMode == 'nationwide';

  bool get hasDeviceOrigin => latitude != null && longitude != null;

  String get resolvedDiscoveryMode {
    if (discoveryMode != null) return discoveryMode!;
    if (hasDeviceOrigin && latitude != null) return 'device';
    if (allCities) return 'nationwide';
    if (cityId != null) return 'manual';
    return 'nationwide';
  }

  Map<String, dynamic> toQuery() {
    final mode = resolvedDiscoveryMode;
    final sendCoords =
        (mode == 'device' || mode == 'manual') && hasDeviceOrigin;
    return {
      'discovery_mode': mode,
      if (q != null && q!.trim().isNotEmpty) 'q': q!.trim(),
      if (mode == 'nationwide') 'city_id': '',
      if (mode == 'manual' && cityId != null) 'city_id': cityId,
      if (categoryId != null) 'category_id': categoryId,
      if (distanceKm != null) 'distance_km': distanceKm,
      if (sendCoords) 'latitude': latitude,
      if (sendCoords) 'longitude': longitude,
      if (when != null && when!.isNotEmpty) 'when': when,
      if (perPage != null) 'per_page': perPage,
      'page': page,
    };
  }

  /// Query string that always keeps empty `city_id=` for nationwide.
  String toQueryString() {
    final parts = <String>[];
    void add(String key, Object? value) {
      if (value == null) {
        parts.add('$key=');
        return;
      }
      parts.add('$key=${Uri.encodeQueryComponent('$value')}');
    }

    final mode = resolvedDiscoveryMode;
    add('discovery_mode', mode);
    if (q != null && q!.trim().isNotEmpty) add('q', q!.trim());
    if ((mode == 'device' || mode == 'manual') && hasDeviceOrigin) {
      add('latitude', latitude);
      add('longitude', longitude);
      if (mode == 'manual' && cityId != null) add('city_id', cityId);
    } else if (mode == 'nationwide') {
      add('city_id', '');
    } else if (cityId != null) {
      add('city_id', cityId);
    }
    if (categoryId != null) add('category_id', categoryId);
    if (distanceKm != null) add('distance_km', distanceKm);
    if (when != null && when!.isNotEmpty) add('when', when);
    if (perPage != null) add('per_page', perPage);
    add('page', page);
    return parts.join('&');
  }

  DiscoverFilters copyWith({
    String? q,
    int? cityId,
    int? categoryId,
    int? distanceKm,
    double? latitude,
    double? longitude,
    String? when,
    int? perPage,
    int? page,
    bool? allCities,
    String? discoveryMode,
    bool clearQ = false,
    bool clearCity = false,
    bool clearCategory = false,
    bool clearDistance = false,
    bool clearWhen = false,
    bool clearDevice = false,
    bool clearDiscoveryMode = false,
  }) {
    return DiscoverFilters(
      q: clearQ ? null : (q ?? this.q),
      cityId: clearCity ? null : (cityId ?? this.cityId),
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      distanceKm: clearDistance ? null : (distanceKm ?? this.distanceKm),
      latitude: clearDevice ? null : (latitude ?? this.latitude),
      longitude: clearDevice ? null : (longitude ?? this.longitude),
      when: clearWhen ? null : (when ?? this.when),
      perPage: perPage ?? this.perPage,
      page: page ?? this.page,
      allCities: allCities ?? this.allCities,
      discoveryMode: clearDiscoveryMode
          ? null
          : (discoveryMode ?? this.discoveryMode),
    );
  }
}

class HomeDiscoveryBundle {
  const HomeDiscoveryBundle({
    required this.nearby,
    required this.worthTheTrip,
    required this.expanded,
    required this.nationwide,
    this.requestedRadiusKm,
    this.effectiveRadiusKm,
  });

  final List<EventItem> nearby;
  final List<EventItem> worthTheTrip;
  final bool expanded;
  final bool nationwide;
  final int? requestedRadiusKm;
  final int? effectiveRadiusKm;
}

abstract class EventsRepository {
  Future<Result<PaginatedEvents>> discover(DiscoverFilters filters);

  /// Option B homepage bundle (API SoT).
  Future<Result<HomeDiscoveryBundle>> discoverHome(DiscoverFilters filters);
  Future<Result<PaginatedEvents>> mine({int page = 1});
  Future<Result<EventDetail>> show(int id);
  Future<Result<EventItem>> create(EventDraft draft);
  Future<Result<EventItem>> update(int id, EventDraft draft);
  Future<Result<EventItem>> publish(int id);
  Future<Result<EventItem>> cancel(int id);
  Future<Result<EventItem>> complete(int id);

  Future<Result<String>> join(int id, {String? message});
  Future<Result<void>> leave(int id);
  Future<Result<String>> cancelJoinRequest(int id);
  Future<Result<List<JoinRequestItem>>> joinRequests(int id);
  Future<Result<void>> acceptRequest(int eventId, int requestId);
  Future<Result<void>> rejectRequest(int eventId, int requestId);
  Future<Result<List<EventMember>>> members(int id);
  Future<Result<void>> promoteMember(int eventId, int userId);
  Future<Result<void>> demoteMember(int eventId, int userId);
  Future<Result<void>> transferOwnership(int eventId, int userId);

  Future<Result<List<ChatMessage>>> messages(int id, {Object? cancelToken});
  Future<Result<ChatMessage>> postMessage(
    int id,
    String body, {
    int? replyToId,
    String? clientUuid,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  });
  Future<Result<List<int>>> messageMedia(int eventId, int messageId);
  Future<Result<ChatMessage>> reactToMessage(
    int eventId,
    int messageId, {
    String? type,
  });
  Future<Result<MessageReactions>> messageReactions(int eventId, int messageId);

  Future<Result<List<EventRating>>> ratings(int id);
  Future<Result<EventRating?>> myRating(int id);
  Future<Result<EventRating>> rate(
    int id, {
    required int stars,
    String? comment,
  });

  Future<Result<NowPlayingFeed>> nowPlaying({int? cityId});
  Future<Result<PaginatedEvents>> favorites({int page = 1});
  Future<Result<bool>> toggleFavorite(int id);
  Future<Result<EventTicket>> ticket(int id);
  Future<Result<({double lat, double lng, String mapsUrl})>> resolveMapsUrl(
    String url,
  );
  Future<Result<List<PlaceSuggestion>>> suggestPlaces(
    String query, {
    int? cityId,
  });
}
