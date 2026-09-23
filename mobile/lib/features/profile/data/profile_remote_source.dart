import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

class ProfileRemoteSource {
  ProfileRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, User? data})> updateLocation({
    required int cityId,
    required int distanceKm,
  }) async {
    try {
      final response = await _client.dio.put<Map<String, dynamic>>(
        '/settings/location',
        data: {
          'city_id': cityId,
          'distance_km': distanceKm,
        },
      );
      final userJson = response.data!['user'] as Map<String, dynamic>;
      final city = userJson['city'];
      return (
        failure: null,
        data: User(
          id: userJson['id'] as int,
          name: '', // filled by refresh me
          email: '',
          status: 'active',
          locale: 'en',
          cityId: userJson['city_id'] as int?,
          distanceKm: userJson['distance_km'] as int?,
          cityName: city is Map ? city['name'] as String? : null,
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
