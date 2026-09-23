import 'dart:typed_data';

import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/identity/domain/identity_status.dart';

class IdentityRemoteSource {
  IdentityRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, IdentityStatus? data})> fetch() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/identity');
      return (
        failure: null,
        data: IdentityStatus.fromJson(response.data!),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, Uint8List? data})> documentBytes(int id) async {
    try {
      final response = await _client.dio.get<List<int>>(
        '/identity/documents/$id',
        options: Options(responseType: ResponseType.bytes),
      );
      final bytes = response.data;
      if (bytes == null) {
        return (failure: null, data: Uint8List(0));
      }
      return (failure: null, data: Uint8List.fromList(bytes));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, IdentityStatus? data})> upload({
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
      return (
        failure: null,
        data: IdentityStatus.fromJson(response.data!),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
