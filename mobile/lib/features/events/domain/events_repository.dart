import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';

class DiscoverFilters {
  const DiscoverFilters({
    this.q,
    this.cityId,
    this.categoryId,
    this.distanceKm,
    this.when,
    this.perPage,
    this.page = 1,
    /// When true, send empty `city_id` so the API lists nationwide (Blade "All cities").
    /// When false and [cityId] is null, omit `city_id` (API may use the user's city).
    this.allCities = false,
  });

  final String? q;
  final int? cityId;
  final int? categoryId;
  final int? distanceKm;
  /// API: `today` | `tomorrow` | `weekend`
  final String? when;
  final int? perPage;
  final int page;
  final bool allCities;

  bool get isNationwide => allCities;

  Map<String, dynamic> toQuery() {
    return {
      if (q != null && q!.trim().isNotEmpty) 'q': q!.trim(),
      // Blade: empty city_id key → nationwide. Omitted city_id → user city fallback.
      // Use a real empty string (not null) so Dio encodes `city_id=`.
      if (allCities) 'city_id': '',
      if (!allCities && cityId != null) 'city_id': cityId,
      if (categoryId != null) 'category_id': categoryId,
      if (distanceKm != null && !allCities) 'distance_km': distanceKm,
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

    if (q != null && q!.trim().isNotEmpty) add('q', q!.trim());
    if (allCities) {
      add('city_id', '');
    } else if (cityId != null) {
      add('city_id', cityId);
    }
    if (categoryId != null) add('category_id', categoryId);
    if (distanceKm != null && !allCities) add('distance_km', distanceKm);
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
    String? when,
    int? perPage,
    int? page,
    bool? allCities,
    bool clearQ = false,
    bool clearCity = false,
    bool clearCategory = false,
    bool clearDistance = false,
    bool clearWhen = false,
  }) {
    return DiscoverFilters(
      q: clearQ ? null : (q ?? this.q),
      cityId: clearCity ? null : (cityId ?? this.cityId),
      categoryId: clearCategory ? null : (categoryId ?? this.categoryId),
      distanceKm: clearDistance ? null : (distanceKm ?? this.distanceKm),
      when: clearWhen ? null : (when ?? this.when),
      perPage: perPage ?? this.perPage,
      page: page ?? this.page,
      allCities: allCities ?? this.allCities,
    );
  }
}

abstract class EventsRepository {
  Future<Result<PaginatedEvents>> discover(DiscoverFilters filters);
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

  Future<Result<List<ChatMessage>>> messages(int id);
  Future<Result<ChatMessage>> postMessage(int id, String body);

  Future<Result<List<EventRating>>> ratings(int id);
  Future<Result<EventRating?>> myRating(int id);
  Future<Result<EventRating>> rate(int id, {required int stars, String? comment});
}
