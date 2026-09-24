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
    String? idToken,
    String? accessToken,
    String? locale,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/auth/google',
        data: {
          if (idToken != null && idToken.isNotEmpty) 'id_token': idToken,
          if (accessToken != null && accessToken.isNotEmpty)
            'access_token': accessToken,
          if (locale != null && locale.isNotEmpty) 'locale': locale,
        },
      );
      return (failure: null, data: _parseSession(response.data!));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, User? data})> verifyEmail(String code) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/email/verify',
        data: {'code': code},
      );
      final body = response.data!;
      final userJson = body['user'];
      if (userJson is Map<String, dynamic>) {
        return (failure: null, data: User.fromJson(userJson));
      }
      return await me();
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> resendEmailVerify() async {
    try {
      await _client.dio.post<void>('/email/verify/resend');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, String? email})> forgotPassword(
    String email,
  ) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/forgot-password',
        data: {'email': email},
      );
      return (
        failure: null,
        email: response.data?['email'] as String? ?? email,
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), email: null);
    }
  }

  Future<({Failure? failure, String? resetToken})> verifyResetCode({
    required String email,
    required String code,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/forgot-password/verify',
        data: {'email': email, 'code': code},
      );
      return (
        failure: null,
        resetToken: response.data?['reset_token'] as String?,
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), resetToken: null);
    }
  }

  Future<({Failure? failure, AuthSessionDto? data})> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/reset-password',
        data: {
          'email': email,
          'reset_token': resetToken,
          'password': password,
          'password_confirmation': passwordConfirmation,
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
      return (failure: null, data: User.fromJson(response.data!));
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
      user: User.fromJson(json['user'] as Map<String, dynamic>),
    );
  }
}
