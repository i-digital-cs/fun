import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/notifications/domain/app_notification.dart';

class NotificationsRemoteSource {
  NotificationsRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, List<AppNotification> data, int unreadCount})>
  list({int perPage = 30}) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/notifications',
        queryParameters: {'per_page': perPage},
      );
      final raw = response.data?['data'];
      final meta = response.data?['meta'] as Map<String, dynamic>? ?? {};
      final list = raw is List
          ? raw
                .whereType<Map>()
                .map(
                  (row) =>
                      AppNotification.fromJson(Map<String, dynamic>.from(row)),
                )
                .toList()
          : <AppNotification>[];
      return (
        failure: null,
        data: list,
        unreadCount: (meta['unread_count'] as num?)?.toInt() ?? 0,
      );
    } on DioException catch (e) {
      return (
        failure: mapDioException(e),
        data: const <AppNotification>[],
        unreadCount: 0,
      );
    }
  }

  Future<({Failure? failure, int unreadCount})> unreadCount() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/notifications/unread-count',
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      return (
        failure: null,
        unreadCount: (data['unread_count'] as num?)?.toInt() ?? 0,
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), unreadCount: 0);
    }
  }

  Future<({Failure? failure, AppNotification? data})> markRead(
    String id,
  ) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/notifications/$id/read',
      );
      final raw = response.data?['data'];
      return (
        failure: null,
        data: raw is Map
            ? AppNotification.fromJson(Map<String, dynamic>.from(raw))
            : null,
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> markAllRead() async {
    try {
      await _client.dio.post<void>('/notifications/read-all');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> registerDeviceToken({
    required String token,
    String platform = 'android',
  }) async {
    try {
      await _client.dio.post<void>(
        '/device-tokens',
        data: {'token': token, 'platform': platform},
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> forgetDeviceToken(String token) async {
    try {
      await _client.dio.delete<void>(
        '/device-tokens',
        data: {'token': token},
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
