import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/domain/events_repository.dart';
import 'package:fun_mobile/features/events/domain/place_suggestion.dart';
import 'package:http_parser/http_parser.dart';

/// Multipart field map for `POST /api/events`.
///
/// `publish_intent` is serialized as `"1"` / `"0"` so Laravel
/// `filter_var(..., FILTER_VALIDATE_BOOLEAN)` accepts it (Dart `bool` in
/// FormData is not reliable across Dio encodings).
@visibleForTesting
Map<String, dynamic> eventDraftMultipartFields(
  EventDraft draft, {
  String? methodOverride,
  bool includePublishIntent = true,
}) {
  return <String, dynamic>{
    'title': draft.title,
    'category_id': draft.categoryId,
    'city_id': draft.cityId,
    'starts_at': draft.startsAt.toIso8601String(),
    'max_participants': draft.maxParticipants,
    'sex': draft.sex,
    'description': ?draft.description,
    'venue': draft.venue ?? '',
    if (draft.latitude != null) 'latitude': draft.latitude,
    if (draft.longitude != null) 'longitude': draft.longitude,
    'maps_url': ?draft.mapsUrl,
    if (draft.endsAt != null) 'ends_at': draft.endsAt!.toIso8601String(),
    'requirements': ?draft.requirements,
    'expected_cost': ?draft.expectedCost,
    if (draft.costPerPerson != null) 'cost_per_person': draft.costPerPerson,
    if (includePublishIntent) 'publish_intent': draft.publishIntent ? '1' : '0',
    '_method': ?methodOverride,
  };
}

class EventsRemoteSource {
  EventsRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, PaginatedEvents? data})> discover(
    DiscoverFilters filters,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events',
        queryParameters: filters.toQuery(),
      );
      final body = response.data;
      if (body == null) {
        return (
          failure: const ServerFailure('Empty response from server'),
          data: null,
        );
      }
      return (failure: null, data: _parsePage(body));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, HomeDiscoveryBundle? data})> discoverHome(
    DiscoverFilters filters,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/discover/home',
        queryParameters: filters.toQuery(),
      );
      final body = response.data;
      if (body == null) {
        return (
          failure: const ServerFailure('Empty response from server'),
          data: null,
        );
      }
      final meta = body['meta'] is Map
          ? Map<String, dynamic>.from(body['meta'] as Map)
          : const <String, dynamic>{};
      List<EventItem> parseList(Object? raw) {
        if (raw is! List) return const [];
        return raw
            .whereType<Map>()
            .map((e) => parseEvent(Map<String, dynamic>.from(e)))
            .toList();
      }

      return (
        failure: null,
        data: HomeDiscoveryBundle(
          nearby: parseList(body['nearby']),
          worthTheTrip: parseList(body['worth_the_trip']),
          expanded: meta['expanded'] == true,
          nationwide: meta['nationwide'] == true,
          requestedRadiusKm: (meta['requested_radius_km'] as num?)?.toInt(),
          effectiveRadiusKm: (meta['effective_radius_km'] as num?)?.toInt(),
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, PaginatedEvents? data})> mine({
    int page = 1,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/my-events',
        queryParameters: {'page': page},
      );
      return (failure: null, data: _parsePage(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventDetail? data})> show(int id) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id',
      );
      final body = response.data!;
      final rawData = body['data'];
      final data = Map<String, dynamic>.from(rawData is Map ? rawData : body);
      // Show payload also mirrors creator at the top level — keep either.
      if (data['creator'] == null && body['creator'] is Map) {
        data['creator'] = Map<String, dynamic>.from(body['creator'] as Map);
      }
      final event = parseEvent(data);
      final myRatingJson = body['my_rating'];
      final creatorJson = body['creator_rating'];
      return (
        failure: null,
        data: EventDetail(
          event: event.copyWith(
            joinAction: body['join_action'] as String? ?? event.joinAction,
            readyForRatings: body['ready_for_ratings'] as bool? ?? false,
            isFavorited: body.containsKey('is_favorited')
                ? _asBool(body['is_favorited'])
                : event.isFavorited,
          ),
          joinAction:
              body['join_action'] as String? ??
              event.joinAction ??
              'not_available',
          readyForRatings: body['ready_for_ratings'] as bool? ?? false,
          canRate: body['can_rate'] as bool? ?? false,
          myRating: myRatingJson is Map
              ? _parseRating(Map<String, dynamic>.from(myRatingJson))
              : null,
          creatorRating: creatorJson is Map
              ? CreatorRating(
                  average: (creatorJson['average'] as num?)?.toDouble(),
                  count: creatorJson['count'] as int? ?? 0,
                )
              : null,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventItem? data})> create(EventDraft draft) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events',
        data: await _formData(draft, includePublishIntent: true),
      );
      final body = response.data!;
      final data = body['data'] as Map<String, dynamic>? ?? body;
      return (failure: null, data: parseEvent(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventItem? data})> update(
    int id,
    EventDraft draft,
  ) async {
    try {
      final response = await _client.dio.put<dynamic>(
        '/events/$id',
        data: await _formData(draft, includePublishIntent: false),
      );
      final body = response.data;
      if (body is Map) {
        final map = Map<String, dynamic>.from(body);
        final data = map['data'] is Map
            ? Map<String, dynamic>.from(map['data'] as Map)
            : map;
        return (failure: null, data: parseEvent(data));
      }
      return (failure: const ServerFailure('Unexpected response'), data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventItem? data})> _transition(String path) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(path);
      final body = response.data!;
      final data = body['data'] as Map<String, dynamic>? ?? body;
      return (failure: null, data: parseEvent(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventItem? data})> publish(int id) =>
      _transition('/events/$id/publish');

  Future<({Failure? failure, EventItem? data})> cancel(int id) =>
      _transition('/events/$id/cancel');

  Future<({Failure? failure, EventItem? data})> complete(int id) =>
      _transition('/events/$id/complete');

  Future<({Failure? failure, String? data})> join(
    int id, {
    String? message,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$id/join',
        data: {if (message != null && message.isNotEmpty) 'message': message},
      );
      return (
        failure: null,
        data: response.data?['join_action'] as String? ?? 'request_pending',
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> leave(int id) async {
    try {
      await _client.dio.post<void>('/events/$id/leave');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, String? data})> cancelJoinRequest(int id) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$id/join/cancel',
      );
      return (
        failure: null,
        data: response.data?['join_action'] as String? ?? 'request_to_join',
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<JoinRequestItem>? data})> joinRequests(
    int id,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/requests',
      );
      final list = (response.data!['data'] as List<dynamic>? ?? []).map((e) {
        final m = e as Map<String, dynamic>;
        final user = m['user'] as Map<String, dynamic>? ?? {};
        return JoinRequestItem(
          id: m['id'] as int,
          status: m['status'] as String? ?? 'pending',
          userId: user['id'] as int? ?? m['user_id'] as int? ?? 0,
          userName: user['name'] as String? ?? '',
        );
      }).toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> acceptRequest(
    int eventId,
    int requestId,
  ) async {
    try {
      await _client.dio.post<void>(
        '/events/$eventId/requests/$requestId/accept',
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> rejectRequest(
    int eventId,
    int requestId,
  ) async {
    try {
      await _client.dio.post<void>(
        '/events/$eventId/requests/$requestId/reject',
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<EventMember>? data})> members(int id) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/members',
      );
      final list = (response.data!['data'] as List<dynamic>? ?? []).map((e) {
        final m = e as Map<String, dynamic>;
        return EventMember(
          userId: m['user_id'] as int? ?? m['id'] as int? ?? 0,
          name:
              m['name'] as String? ??
              (m['user'] is Map
                  ? (m['user'] as Map)['name'] as String?
                  : null) ??
              '',
          role: m['role'] as String? ?? 'member',
        );
      }).toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> promoteMember(
    int eventId,
    int userId,
  ) async {
    try {
      await _client.dio.post<void>('/events/$eventId/members/$userId/promote');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> demoteMember(
    int eventId,
    int userId,
  ) async {
    try {
      await _client.dio.post<void>('/events/$eventId/members/$userId/demote');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> transferOwnership(
    int eventId,
    int userId,
  ) async {
    try {
      await _client.dio.post<void>(
        '/events/$eventId/transfer',
        data: {'user_id': userId},
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<ChatMessage>? data})> messages(
    int id, {
    Object? cancelToken,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/messages',
        cancelToken: cancelToken is CancelToken ? cancelToken : null,
      );
      final raw = response.data!['data'] as List<dynamic>? ?? [];
      final list = raw
          .map((e) => _parseMessage(e as Map<String, dynamic>))
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      if (CancelToken.isCancel(e)) {
        return (failure: null, data: null);
      }
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, ChatMessage? data})> postMessage(
    int id,
    String body, {
    int? replyToId,
    String? clientUuid,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  }) async {
    try {
      final fields = <String, dynamic>{
        'body': body,
        'reply_to_id': ?replyToId,
        'client_uuid': ?clientUuid,
        'media_kind': ?mediaKind,
        'duration_ms': ?durationMs,
      };
      if (mediaPath != null) {
        fields['media'] = await MultipartFile.fromFile(
          mediaPath,
          filename: mediaKind == 'voice' ? 'voice.m4a' : 'image.jpg',
          contentType: mediaKind == 'voice'
              ? MediaType('audio', 'mp4')
              : MediaType('image', 'jpeg'),
        );
      }
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$id/messages',
        data: mediaPath == null ? fields : FormData.fromMap(fields),
        options: mediaPath == null
            ? null
            : Options(
                sendTimeout: const Duration(seconds: 90),
                receiveTimeout: const Duration(seconds: 90),
              ),
      );
      final data =
          response.data!['data'] as Map<String, dynamic>? ?? response.data!;
      return (failure: null, data: _parseMessage(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<int>? data})> messageMedia(
    int eventId,
    int messageId,
  ) async {
    try {
      final response = await _client.dio.get<List<int>>(
        '/events/$eventId/messages/$messageId/media',
        options: Options(responseType: ResponseType.bytes),
      );
      return (failure: null, data: response.data);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, ChatMessage? data})> reactToMessage(
    int eventId,
    int messageId, {
    String? type,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$eventId/messages/$messageId/react',
        data: {'type': type},
      );
      final data =
          response.data!['data'] as Map<String, dynamic>? ?? response.data!;
      return (
        failure: null,
        data: ChatMessage(
          id: (data['id'] as num?)?.toInt() ?? messageId,
          eventId: (data['event_id'] as num?)?.toInt() ?? eventId,
          body: '',
          isSystem: false,
          likesCount: (data['likes_count'] as num?)?.toInt() ?? 0,
          dislikesCount: (data['dislikes_count'] as num?)?.toInt() ?? 0,
          myReaction: data['my_reaction'] as String?,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, MessageReactions? data})> messageReactions(
    int eventId,
    int messageId,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$eventId/messages/$messageId/reactions',
      );
      final data =
          response.data!['data'] as Map<String, dynamic>? ?? response.data!;
      return (
        failure: null,
        data: MessageReactions.fromJson(Map<String, dynamic>.from(data)),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<EventRating>? data})> ratings(int id) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/ratings',
      );
      final list = (response.data!['data'] as List<dynamic>? ?? []).map((e) {
        return _parseRating(e as Map<String, dynamic>);
      }).toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventRating? data})> myRating(int id) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/ratings/me',
      );
      final data = response.data!['data'];
      if (data == null) return (failure: null, data: null);
      return (failure: null, data: _parseRating(data as Map<String, dynamic>));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, EventRating? data})> rate(
    int id, {
    required int stars,
    String? comment,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$id/ratings',
        data: {
          'stars': stars,
          if (comment != null && comment.isNotEmpty) 'comment': comment,
        },
      );
      final data =
          response.data!['data'] as Map<String, dynamic>? ?? response.data!;
      return (failure: null, data: _parseRating(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  PaginatedEvents _parsePage(Map<String, dynamic> body) {
    final rawList = body['data'];
    final list = <EventItem>[];
    if (rawList is List) {
      for (final e in rawList) {
        if (e is Map) {
          list.add(parseEvent(Map<String, dynamic>.from(e)));
        }
      }
    }
    final meta = _asStringKeyedMap(body['meta']);
    final discovery = _asStringKeyedMap(body['discovery']);
    final currentPage =
        _asInt(meta['current_page']) ?? _asInt(body['current_page']) ?? 1;
    final lastPage =
        _asInt(meta['last_page']) ?? _asInt(body['last_page']) ?? currentPage;
    return PaginatedEvents(
      items: list,
      currentPage: currentPage,
      lastPage: lastPage,
      isSuggestion: discovery['is_suggestion'] == true,
      selectedRadiusKm: _asInt(discovery['selected_radius_km']),
      effectiveRadiusKm: _asInt(discovery['effective_radius_km']),
    );
  }

  static Map<String, dynamic> _asStringKeyedMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) return Map<String, dynamic>.from(value);
    return {};
  }

  Future<({Failure? failure, NowPlayingFeed? data})> nowPlaying({
    int? cityId,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/now-playing',
        queryParameters: {'city_id': ?cityId},
      );
      final body = response.data ?? const <String, dynamic>{};
      final data = body['data'] is Map<String, dynamic>
          ? body['data'] as Map<String, dynamic>
          : body;

      EventItem? parseOne(dynamic raw) {
        if (raw is! Map) return null;
        return parseEvent(Map<String, dynamic>.from(raw));
      }

      List<EventItem> parseMany(dynamic raw) {
        if (raw is! List) return const [];
        return raw
            .whereType<Map>()
            .map((e) => parseEvent(Map<String, dynamic>.from(e)))
            .toList();
      }

      return (
        failure: null,
        data: NowPlayingFeed(
          running: parseOne(data['running']),
          next: parseMany(data['next']),
          previous: parseMany(data['previous']),
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, PaginatedEvents? data})> favorites({
    int page = 1,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/favorites',
        queryParameters: {'page': page},
      );
      final body = response.data;
      if (body == null) {
        return (
          failure: const ServerFailure('Empty response from server'),
          data: null,
        );
      }
      return (failure: null, data: _parsePage(body));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, bool? data})> toggleFavorite(int id) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/events/$id/favorite',
      );
      final body = response.data ?? const <String, dynamic>{};
      return (failure: null, data: _asBool(body['is_favorited']));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, EventTicket? data})> ticket(int id) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/events/$id/ticket',
      );
      final body = response.data ?? const <String, dynamic>{};
      final data = body['data'] is Map
          ? Map<String, dynamic>.from(body['data'] as Map)
          : body;
      return (failure: null, data: _parseTicket(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  static EventTicket _parseTicket(Map<String, dynamic> json) {
    final memberRaw = json['member'];
    final eventRaw = json['event'];
    final member = memberRaw is Map
        ? Map<String, dynamic>.from(memberRaw)
        : null;
    final event = eventRaw is Map ? Map<String, dynamic>.from(eventRaw) : null;

    return EventTicket(
      code: json['code'] as String? ?? '',
      role: json['role'] as String? ?? 'member',
      passLabel: json['pass_label'] as String? ?? '',
      title: json['title'] as String? ?? '',
      dateLabel: json['date_label'] as String? ?? '',
      dateIso: json['date_iso'] as String?,
      venueLine: json['venue_line'] as String? ?? '',
      barcodeShadow: json['barcode_shadow'] as String?,
      member: member != null
          ? EventTicketMember(
              id: _asInt(member['id']) ?? 0,
              name: member['name'] as String? ?? '',
            )
          : null,
      event: event != null
          ? EventTicketEventRef(
              id: _asInt(event['id']) ?? 0,
              title: event['title'] as String? ?? '',
              status: event['status'] as String? ?? '',
            )
          : null,
    );
  }

  static int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value.trim());
    return null;
  }

  static bool _asBool(dynamic value) {
    if (value is bool) return value;
    if (value is num) return value != 0;
    if (value is String) {
      final v = value.trim().toLowerCase();
      return v == '1' || v == 'true' || v == 'yes';
    }
    return false;
  }

  static num? _asNum(dynamic value) {
    if (value == null) return null;
    if (value is num) return value;
    if (value is String) return num.tryParse(value.trim());
    return null;
  }

  Future<({Failure? failure, ({double lat, double lng, String mapsUrl})? data})>
  resolveMapsUrl(String url) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/maps/resolve',
        data: {'url': url},
      );
      final body = response.data;
      final data = body?['data'];
      if (data is! Map) {
        return (
          failure: const ServerFailure('Could not resolve maps link'),
          data: null,
        );
      }
      final map = Map<String, dynamic>.from(data);
      final lat = _asNum(map['latitude'])?.toDouble();
      final lng = _asNum(map['longitude'])?.toDouble();
      if (lat == null || lng == null) {
        return (
          failure: const ServerFailure('Could not resolve maps link'),
          data: null,
        );
      }
      return (
        failure: null,
        data: (lat: lat, lng: lng, mapsUrl: map['maps_url'] as String? ?? url),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<PlaceSuggestion>? data})> suggestPlaces(
    String query, {
    int? cityId,
  }) async {
    final q = query.trim();
    if (q.length < 2) {
      return (failure: null, data: const <PlaceSuggestion>[]);
    }
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/places/suggest',
        queryParameters: {'q': q, 'limit': 8, 'city_id': ?cityId},
      );
      final raw = response.data?['data'];
      if (raw is! List) {
        return (failure: null, data: const <PlaceSuggestion>[]);
      }
      final list = raw
          .whereType<Map>()
          .map((e) => PlaceSuggestion.fromJson(Map<String, dynamic>.from(e)))
          .where((s) => s.label.isNotEmpty)
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  static EventItem parseEvent(Map<String, dynamic> json) {
    final categoryRaw = json['category'];
    final cityRaw = json['city'];
    final locationRaw = json['location'];
    final category = categoryRaw is Map
        ? Map<String, dynamic>.from(categoryRaw)
        : null;
    final city = cityRaw is Map ? Map<String, dynamic>.from(cityRaw) : null;
    final location = locationRaw is Map
        ? Map<String, dynamic>.from(locationRaw)
        : null;
    final locationCity = location?['city'] is Map
        ? Map<String, dynamic>.from(location!['city'] as Map)
        : null;
    final creatorRaw = json['creator'];
    final creator = creatorRaw is Map
        ? Map<String, dynamic>.from(creatorRaw)
        : null;

    final latitude =
        _asNum(json['latitude'])?.toDouble() ??
        _asNum(location?['latitude'])?.toDouble();
    final longitude =
        _asNum(json['longitude'])?.toDouble() ??
        _asNum(location?['longitude'])?.toDouble();
    final hasPlacePin =
        _asBool(json['has_place_pin']) ||
        _asBool(location?['has_place_pin']) ||
        (latitude != null && longitude != null);

    final venueFromLocation =
        location?['venue_raw'] as String? ?? location?['venue'] as String?;
    final venue = (json['venue'] as String?)?.trim().isNotEmpty == true
        ? json['venue'] as String
        : (venueFromLocation ?? '');

    return EventItem(
      id: _asInt(json['id']) ?? 0,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      requirements: json['requirements'] as String?,
      expectedCost: json['expected_cost']?.toString(),
      venue: venue,
      locationLabel:
          json['location_label'] as String? ?? location?['label'] as String?,
      latitude: latitude,
      longitude: longitude,
      mapsUrl: json['maps_url'] as String? ?? location?['maps_url'] as String?,
      mapsOpenUrl:
          json['maps_open_url'] as String? ??
          location?['maps_open_url'] as String?,
      hasPlacePin: hasPlacePin,
      startsAt: json['starts_at'] is String
          ? DateTime.tryParse(json['starts_at'] as String)
          : null,
      endsAt: json['ends_at'] is String
          ? DateTime.tryParse(json['ends_at'] as String)
          : null,
      maxParticipants: _asInt(json['max_participants']) ?? 0,
      membersCount: _asInt(json['members_count']) ?? 0,
      sex: json['sex'] as String?,
      costPerPerson: _asNum(json['cost_per_person']),
      imageUrl: json['image_url'] as String?,
      status: json['status'] as String?,
      distanceKm: _asNum(json['distance_km'])?.toDouble(),
      distancePrecision: json['distance_precision'] as String?,
      joinAction: json['join_action'] as String?,
      createdBy: _asInt(json['created_by']),
      readyForRatings: json['ready_for_ratings'] as bool? ?? false,
      isFavorited: _asBool(json['is_favorited']),
      categoryId: _asInt(json['category_id']) ?? _asInt(category?['id']),
      cityId:
          _asInt(json['city_id']) ??
          _asInt(city?['id']) ??
          _asInt(locationCity?['id']),
      category: category != null
          ? EventCategory(
              id: _asInt(category['id']) ?? 0,
              name: category['name'] as String? ?? '',
              nameEn: category['name_en'] as String?,
              nameAr: category['name_ar'] as String?,
            )
          : null,
      city: (city ?? locationCity) != null
          ? EventCity(
              id: _asInt((city ?? locationCity)!['id']) ?? 0,
              name: (city ?? locationCity)!['name'] as String? ?? '',
              nameEn: (city ?? locationCity)!['name_en'] as String?,
              nameAr: (city ?? locationCity)!['name_ar'] as String?,
              latitude: _asNum((city ?? locationCity)!['latitude'])?.toDouble(),
              longitude: _asNum((city ?? locationCity)!['longitude'])
                  ?.toDouble(),
            )
          : null,
      creator: creator != null
          ? EventCreator(
              id: _asInt(creator['id']) ?? 0,
              name: creator['name'] as String? ?? '',
              initials: creator['initials'] as String?,
              profileImagePath: creator['profile_image_path'] as String?,
              publicId: creator['public_id'] as String?,
            )
          : null,
    );
  }

  EventRating _parseRating(Map<String, dynamic> json) {
    final rater = json['rater'];
    return EventRating(
      id: json['id'] as int,
      stars: json['stars'] as int? ?? 0,
      comment: json['comment'] as String?,
      raterName: rater is Map ? rater['name'] as String? : null,
    );
  }

  ChatMessage _parseMessage(Map<String, dynamic> json) {
    return ChatMessage.fromJson(json);
  }

  Future<FormData> _formData(
    EventDraft draft, {
    String? methodOverride,
    bool includePublishIntent = true,
  }) async {
    // Always include venue (even empty) so the API can reverse-geocode when a pin is set.
    final map = eventDraftMultipartFields(
      draft,
      methodOverride: methodOverride,
      includePublishIntent: includePublishIntent,
    );

    final form = FormData.fromMap(map);
    if (draft.imagePath != null && draft.imagePath!.isNotEmpty) {
      form.files.add(
        MapEntry(
          'image',
          await MultipartFile.fromFile(
            draft.imagePath!,
            filename: draft.imagePath!.split(RegExp(r'[\\/]')).last,
          ),
        ),
      );
    }
    return form;
  }
}
