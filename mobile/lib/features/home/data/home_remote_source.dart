import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/events/data/events_remote_source.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/home/domain/weather.dart';

class HomeRemoteSource {
  HomeRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, WeatherSnapshot? data})> weather({
    int? cityId,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/weather',
        queryParameters: {
          if (cityId != null) 'city_id': cityId,
        },
      );
      final body = response.data ?? const <String, dynamic>{};
      final data = body['data'];
      if (data is! Map) {
        return (
          failure: ServerFailure(
            body['message'] as String? ?? 'Weather unavailable',
          ),
          data: null,
        );
      }
      return (
        failure: null,
        data: _parseWeather(Map<String, dynamic>.from(data)),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  Future<({Failure? failure, HomeParticipations? data})> participations() async {
    try {
      final response = await _client.dio
          .get<Map<String, dynamic>>('/home/participations');
      final body = response.data ?? const <String, dynamic>{};
      return (failure: null, data: _parseParticipations(body));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    } catch (e) {
      return (failure: mapUnexpectedError(e), data: null);
    }
  }

  WeatherSnapshot _parseWeather(Map<String, dynamic> json) {
    final cityRaw = json['city'];
    final cityMap =
        cityRaw is Map ? Map<String, dynamic>.from(cityRaw) : <String, dynamic>{};
    final forecastRaw = json['forecast'];
    final forecast = <WeatherForecastDay>[];
    if (forecastRaw is List) {
      for (final day in forecastRaw) {
        if (day is! Map) continue;
        final m = Map<String, dynamic>.from(day);
        forecast.add(
          WeatherForecastDay(
            day: m['day'] as String? ?? '',
            condition: m['condition'] as String? ?? '',
            icon: m['icon'] as String? ?? 'sun',
            high: _asInt(m['high']),
            low: _asInt(m['low']),
            label: m['label'] as String?,
          ),
        );
      }
    }

    return WeatherSnapshot(
      provider: json['provider'] as String?,
      city: WeatherCity(
        id: _asInt(cityMap['id']) ?? 0,
        name: cityMap['name'] as String? ?? '',
        latitude: _asDouble(cityMap['latitude']),
        longitude: _asDouble(cityMap['longitude']),
      ),
      localTime: json['local_time'] as String?,
      localDate: json['local_date'] as String?,
      temperature: _asInt(json['temperature']) ?? 0,
      high: _asInt(json['high']),
      low: _asInt(json['low']),
      range: json['range'] as String?,
      condition: json['condition'] as String? ?? '',
      label: json['label'] as String? ?? '',
      icon: json['icon'] as String? ?? 'sun',
      theme: json['theme'] as String? ?? 'sunny',
      forecast: forecast,
    );
  }

  HomeParticipations _parseParticipations(Map<String, dynamic> body) {
    HomeParticipationRow? mapMembership(dynamic raw) {
      if (raw is! Map) return null;
      final m = Map<String, dynamic>.from(raw);
      final eventRaw = m['event'];
      if (eventRaw is! Map) return null;
      return HomeParticipationRow(
        role: m['role'] as String?,
        event: EventsRemoteSource.parseEvent(
          Map<String, dynamic>.from(eventRaw),
        ),
      );
    }

    HomeParticipationRow? mapPending(dynamic raw) {
      if (raw is! Map) return null;
      final m = Map<String, dynamic>.from(raw);
      final eventRaw = m['event'];
      if (eventRaw is! Map) return null;
      return HomeParticipationRow(
        requestId: _asInt(m['id']),
        status: m['status'] as String?,
        event: EventsRemoteSource.parseEvent(
          Map<String, dynamic>.from(eventRaw),
        ),
      );
    }

    final memberships = (body['memberships'] as List<dynamic>? ?? [])
        .map(mapMembership)
        .whereType<HomeParticipationRow>()
        .toList();
    final pending = (body['pending_requests'] as List<dynamic>? ?? [])
        .map(mapPending)
        .whereType<HomeParticipationRow>()
        .toList();

    return HomeParticipations(
      memberships: memberships,
      pendingRequests: pending,
    );
  }

  static int? _asInt(dynamic value) {
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value.trim());
    return null;
  }

  static double? _asDouble(dynamic value) {
    if (value is double) return value;
    if (value is num) return value.toDouble();
    if (value is String) return double.tryParse(value.trim());
    return null;
  }
}
