import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';

class ChatsRemoteSource {
  ChatsRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, List<ChatThread> data, int unreadTotal})> list() async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>('/chats');
      final raw = response.data?['data'];
      final meta = response.data?['meta'] as Map<String, dynamic>? ?? {};
      final list = raw is List
          ? raw
              .whereType<Map>()
              .map((row) => ChatThread.fromJson(Map<String, dynamic>.from(row)))
              .toList()
          : <ChatThread>[];
      return (
        failure: null,
        data: list,
        unreadTotal: (meta['unread_total'] as num?)?.toInt() ?? 0,
      );
    } catch (error) {
      return (
        failure: mapError(error),
        data: const <ChatThread>[],
        unreadTotal: 0,
      );
    }
  }
}
