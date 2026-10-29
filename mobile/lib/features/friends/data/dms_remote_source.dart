import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:http_parser/http_parser.dart';

class DmsRemoteSource {
  DmsRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, List<DmConversation> data, int unreadTotal})>
  list() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/dms');
      final raw = response.data?['data'];
      final meta = response.data?['meta'] as Map<String, dynamic>? ?? {};
      final list = raw is List
          ? raw
                .whereType<Map>()
                .map(
                  (row) =>
                      DmConversation.fromJson(Map<String, dynamic>.from(row)),
                )
                .where((c) => c.id > 0)
                .toList()
          : <DmConversation>[];
      return (
        failure: null,
        data: list,
        unreadTotal: (meta['unread_total'] as num?)?.toInt() ?? 0,
      );
    } on DioException catch (e) {
      return (
        failure: mapDioException(e),
        data: const <DmConversation>[],
        unreadTotal: 0,
      );
    }
  }

  Future<({Failure? failure, DmOpenResult? data})> openWith(int userId) async {
    try {
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/dms/with/$userId',
      );
      final data = response.data?['data'] as Map<String, dynamic>? ?? {};
      return (failure: null, data: DmOpenResult.fromJson(data));
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<DmMessage> data})> messages(
    int conversationId, {
    CancelToken? cancelToken,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/dms/$conversationId/messages',
        cancelToken: cancelToken,
      );
      final raw = response.data?['data'];
      final list = raw is List
          ? raw
                .whereType<Map>()
                .map(
                  (row) => DmMessage.fromJson(Map<String, dynamic>.from(row)),
                )
                .where((m) => m.id > 0)
                .toList()
          : <DmMessage>[];
      return (failure: null, data: list);
    } on DioException catch (e) {
      if (e.type == DioExceptionType.cancel) {
        return (failure: null, data: const <DmMessage>[]);
      }
      return (failure: mapDioException(e), data: const <DmMessage>[]);
    }
  }

  Future<({Failure? failure, DmMessage? data})> postMessage(
    int conversationId,
    String body, {
    String? clientUuid,
    int? replyToId,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  }) async {
    try {
      final fields = <String, dynamic>{
        'body': body,
        'client_uuid': ?clientUuid,
        'reply_to_id': ?replyToId,
        'media_kind': ?mediaKind,
        'duration_ms': ?durationMs,
      };
      if (mediaPath != null) {
        fields['media'] = await MultipartFile.fromFile(
          mediaPath,
          filename: mediaKind == 'voice' ? 'voice.m4a' : 'image.jpg',
          contentType: mediaKind == 'voice'
              ? MediaType('audio', 'mp4')
              : MediaType('image', 'jpeg'),
        );
      }
      final response = await _client.dio.post<Map<String, dynamic>>(
        '/dms/$conversationId/messages',
        data: mediaPath == null ? fields : FormData.fromMap(fields),
        options: mediaPath == null
            ? null
            : Options(
                sendTimeout: const Duration(seconds: 90),
                receiveTimeout: const Duration(seconds: 90),
              ),
      );
      final data = response.data?['data'] as Map<String, dynamic>?;
      return (
        failure: null,
        data: data != null ? DmMessage.fromJson(data) : null,
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> react(
    int conversationId,
    int messageId,
    String emoji,
  ) async {
    try {
      await _client.dio.post<void>(
        '/dms/$conversationId/messages/$messageId/react',
        data: {'emoji': emoji},
      );
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, List<int>? data})> media(int messageId) async {
    try {
      final response = await _client.dio.get<List<int>>(
        '/dms/media/$messageId',
        options: Options(responseType: ResponseType.bytes),
      );
      return (failure: null, data: response.data);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }

  Future<({Failure? failure, void data})> markRead(int conversationId) async {
    try {
      await _client.dio.post<void>('/dms/$conversationId/read');
      return (failure: null, data: null);
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}
