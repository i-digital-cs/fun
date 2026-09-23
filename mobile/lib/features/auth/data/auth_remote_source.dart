import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

class AuthSessionDto {
  const AuthSessionDto({required this.token, required this.user});

  final String token;
  final User user;
}

class AuthRemoteSource {
  AuthRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, AuthSessionDto? data})> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/login',
        data: {'email': email, 'password': password},
      );
      return (failure: null, data: _parseSession(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, AuthSessionDto? data})> loginWithGoogle({
    required String accessToken,
    String? locale,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/auth/google',
        data: {
          'access_token': accessToken,
          if (locale != null && locale.isNotEmpty) 'locale': locale,
        },
      );
      return (failure: null, data: _parseSession(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, AuthSessionDto? data})> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String locale,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/register',
        data: {
          'name': name,
          'email': email,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'locale': locale,
        },
      );
      return (failure: null, data: _parseSession(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, User? data})> me() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/user');
      return (failure: null, data: _parseUser(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> logout() async {
    try {
      await _client.dio.post<void>('/logout');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  AuthSessionDto _parseSession(Map<String, dynamic> json) {
    return AuthSessionDto(
      token: json['token'] as String,
      user: _parseUser(json['user'] as Map<String, dynamic>),
    );
  }

  User _parseUser(Map<String, dynamic> json) {
    final city = json['city'];
    return User(
      id: json['id'] as int,
      publicId: json['public_id'] as String?,
      name: json['name'] as String,
      email: json['email'] as String,
      status: json['status'] as String? ?? 'active',
      locale: json['locale'] as String? ?? 'en',
      platformRole: json['platform_role'] as String?,
      cityId: json['city_id'] as int?,
      distanceKm: json['distance_km'] as int?,
      cityName: city is Map<String, dynamic> ? city['name'] as String? : null,
    );
  }
}
