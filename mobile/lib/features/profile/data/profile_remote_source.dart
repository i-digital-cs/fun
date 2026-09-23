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
      return (
        failure: null,
        data: User.fromJson(userJson),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  /// PUT /settings/profile — JSON when no photo; multipart POST+_method when photo/remove.
  Future<
      ({
        Failure? failure,
        User? data,
        bool emailChanged,
      })> updateProfile({
    required String name,
    required String email,
    String? locale,
    String? currentPassword,
    String? password,
    String? passwordConfirmation,
    String? photoPath,
    bool removePhoto = false,
  }) async {
    try {
      final fields = <String, dynamic>{
        'name': name,
        'email': email,
        if (locale != null && locale.isNotEmpty) 'locale': locale,
        if (currentPassword != null && currentPassword.isNotEmpty)
          'current_password': currentPassword,
        if (password != null && password.isNotEmpty) 'password': password,
        if (passwordConfirmation != null && passwordConfirmation.isNotEmpty)
          'password_confirmation': passwordConfirmation,
      };

      final useMultipart =
          (photoPath != null && photoPath.isNotEmpty) || removePhoto;

      final Response<Map<String, dynamic>> response;
      if (useMultipart) {
        final form = FormData.fromMap({
          ...fields,
          if (removePhoto) 'remove_photo': true,
          '_method': 'PUT',
        });
        if (photoPath != null && photoPath.isNotEmpty) {
          form.files.add(
            MapEntry(
              'photo',
              await MultipartFile.fromFile(
                photoPath,
                filename: photoPath.split(RegExp(r'[\\/]')).last,
              ),
            ),
          );
        }
        response = await _client.dio.post<Map<String, dynamic>>(
          '/settings/profile',
          data: form,
        );
      } else {
        response = await _client.dio.put<Map<String, dynamic>>(
          '/settings/profile',
          data: fields,
        );
      }

      final data = response.data!;
      final userJson = data['user'] as Map<String, dynamic>?;
      final emailChanged = data['email_changed'] as bool? ?? false;
      final emailVerified = data['email_verified'] as bool?;

      User? user;
      if (userJson != null) {
        user = User.fromJson({
          ...userJson,
          'email_verified': ?emailVerified,
          if (emailChanged) 'needs_email_verification': true,
        });
      }

      return (failure: null, data: user, emailChanged: emailChanged);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null, emailChanged: false);
    }
  }
}
