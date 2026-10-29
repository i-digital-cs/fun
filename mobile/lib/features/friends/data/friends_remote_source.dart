import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';

class FriendsRemoteSource {
  FriendsRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, List<FriendUser> data})> listFriends() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/friends');
      final raw = response.data?['data'];
      final list = _mapUsers(raw);
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: const <FriendUser>[]);
    }
  }

  Future<({Failure? failure, FriendRequestsBundle? data})>
  listRequests() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/friends/requests',
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      final incoming = (data['incoming'] as List<dynamic>? ?? [])
          .whereType<Map>()
          .map(
            (row) => FriendRequestRow.fromJson(Map<String, dynamic>.from(row)),
          )
          .toList();
      final outgoing = (data['outgoing'] as List<dynamic>? ?? [])
          .whereType<Map>()
          .map(
            (row) => FriendRequestRow.fromJson(Map<String, dynamic>.from(row)),
          )
          .toList();
      return (
        failure: null,
        data: FriendRequestsBundle(incoming: incoming, outgoing: outgoing),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<FriendUser> data})> search(
    String query,
  ) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/users/search',
        queryParameters: {'q': query},
      );
      final raw = response.data?['data'];
      final list = _mapUsers(raw);
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: const <FriendUser>[]);
    }
  }

  Future<({Failure? failure, List<FriendUser> data})> peopleSuggestions({
    int limit = 8,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/people/suggestions',
        queryParameters: {'limit': limit},
      );
      final raw = response.data?['data'];
      if (raw is! List) {
        return (failure: null, data: const <FriendUser>[]);
      }
      final list = raw
          .whereType<Map>()
          .map(
            (row) =>
                FriendUser.fromSuggestionRow(Map<String, dynamic>.from(row)),
          )
          .where((u) => u.id > 0)
          .toList();
      return (failure: null, data: list);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: const <FriendUser>[]);
    }
  }

  Future<({Failure? failure, void data})> requestFriend(String publicId) async {
    try {
      await _client.dio.post<void>('/friends', data: {'public_id': publicId});
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> accept(int userId) async {
    try {
      await _client.dio.post<void>('/friends/$userId/accept');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> decline(int userId) async {
    try {
      await _client.dio.post<void>('/friends/$userId/decline');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> cancel(int userId) async {
    try {
      await _client.dio.post<void>('/friends/$userId/cancel');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> unfriend(int userId) async {
    try {
      await _client.dio.delete<void>('/friends/$userId');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  List<FriendUser> _mapUsers(Object? raw) {
    if (raw is! List) return const <FriendUser>[];
    return raw
        .whereType<Map>()
        .map((row) => FriendUser.fromJson(Map<String, dynamic>.from(row)))
        .where((u) => u.id > 0)
        .toList();
  }
}
