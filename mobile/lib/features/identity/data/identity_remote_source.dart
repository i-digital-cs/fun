import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';

class IdentityRemoteSource {
  IdentityRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, String? status})> upload({
    required String idFrontPath,
    required String idBackPath,
    required String selfiePath,
  }) async {
    try {
      final form = FormData.fromMap({
        'id_front': await MultipartFile.fromFile(
          idFrontPath,
          filename: idFrontPath.split(RegExp(r'[\\/]')).last,
        ),
        'id_back': await MultipartFile.fromFile(
          idBackPath,
          filename: idBackPath.split(RegExp(r'[\\/]')).last,
        ),
        'selfie': await MultipartFile.fromFile(
          selfiePath,
          filename: selfiePath.split(RegExp(r'[\\/]')).last,
        ),
      });
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/identity',
        data: form,
      );
      final user = response.data!['user'] as Map<String, dynamic>?;
      return (
        failure: null,
        status: user?['status'] as String? ?? 'active',
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), status: null);
    }
  }
}
