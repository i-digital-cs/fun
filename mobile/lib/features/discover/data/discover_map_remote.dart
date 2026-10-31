import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/discover/domain/map_discovery.dart';

class DiscoverMapRemote {
  DiscoverMapRemote(this._client);

  final ApiClient _client;

  Future<({Failure? failure, MapDiscoveryPayload? data})> fetch(
    MapBounds bounds, {
    required int zoom,
    int? categoryId,
    String? q,
    String? when,
    bool includeApproximate = false,
    String? discoveryMode,
    double? latitude,
    double? longitude,
    int? distanceKm,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/discovery/map',
        queryParameters: bounds.toQuery(
          zoom: zoom,
          categoryId: categoryId,
          q: q,
          when: when,
          includeApproximate: includeApproximate,
          discoveryMode: discoveryMode,
          latitude: latitude,
          longitude: longitude,
          distanceKm: distanceKm,
        ),
      );
      final body = response.data ?? const <String, dynamic>{};
      final meta = body['meta'] is Map
          ? Map<String, dynamic>.from(body['meta'] as Map)
          : const <String, dynamic>{};
      final clustered = meta['clustered'] == true;
      final markers = <MapDiscoveryMarker>[];
      final clusters = <MapDiscoveryCluster>[];
      final cityAggregates = <MapCityAggregate>[];

      for (final raw in (body['markers'] as List<dynamic>? ?? const [])) {
        if (raw is Map<String, dynamic>) {
          markers.add(MapDiscoveryMarker.fromJson(raw));
        } else if (raw is Map) {
          markers.add(MapDiscoveryMarker.fromJson(Map<String, dynamic>.from(raw)));
        }
      }

      for (final raw in (body['clusters'] as List<dynamic>? ?? const [])) {
        final map = raw is Map<String, dynamic>
            ? raw
            : Map<String, dynamic>.from(raw as Map);
        if (map['type'] == 'marker' || map['count'] == 1 && map['title'] != null) {
          markers.add(MapDiscoveryMarker.fromJson(map));
        } else {
          clusters.add(MapDiscoveryCluster.fromJson(map));
        }
      }

      for (final raw in (body['city_aggregates'] as List<dynamic>? ?? const [])) {
        final map = raw is Map<String, dynamic>
            ? raw
            : Map<String, dynamic>.from(raw as Map);
        cityAggregates.add(MapCityAggregate.fromJson(map));
      }

      return (
        failure: null,
        data: MapDiscoveryPayload(
          markers: markers,
          clusters: clusters,
          cityAggregates: cityAggregates,
          clustered: clustered,
          isSuggestion: meta['is_suggestion'] == true,
          selectedRadiusKm: (meta['selected_radius_km'] as num?)?.toInt(),
          effectiveRadiusKm: (meta['effective_radius_km'] as num?)?.toInt(),
          originAware: meta['origin_aware'] == true,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, ({String name, List<MapCityEvent> events})? data})>
      cityEvents(
    int cityId, {
    int? categoryId,
    String? q,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/discovery/city-events',
        queryParameters: {
          'city_id': cityId,
          if (categoryId != null) 'category_id': categoryId,
          if (q != null && q.isNotEmpty) 'q': q,
        },
      );
      final body = response.data ?? const <String, dynamic>{};
      final city = body['city'] is Map
          ? Map<String, dynamic>.from(body['city'] as Map)
          : const <String, dynamic>{};
      final events = <MapCityEvent>[];
      for (final raw in (body['events'] as List<dynamic>? ?? const [])) {
        final map = raw is Map<String, dynamic>
            ? raw
            : Map<String, dynamic>.from(raw as Map);
        events.add(MapCityEvent.fromJson(map));
      }
      return (
        failure: null,
        data: (
          name: city['name'] as String? ?? '',
          events: events,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, Map<String, dynamic>? data})> reverseGeocode({
    required double latitude,
    required double longitude,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/places/reverse',
        queryParameters: {
          'latitude': latitude,
          'longitude': longitude,
        },
      );
      final data = response.data?['data'];
      if (data is Map<String, dynamic>) {
        return (failure: null, data: data);
      }
      if (data is Map) {
        return (failure: null, data: Map<String, dynamic>.from(data));
      }
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, List<Map<String, dynamic>>? data})> searchPlaces(
    String query, {
    int limit = 10,
  }) async {
    final q = query.trim();
    if (q.length < 2) {
      return (failure: null, data: const <Map<String, dynamic>>[]);
    }
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/places/search',
        queryParameters: {'q': q, 'limit': limit},
      );
      final list = (response.data?['data'] as List<dynamic>? ?? [])
          .map((e) => Map<String, dynamic>.from(e as Map))
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }
}
