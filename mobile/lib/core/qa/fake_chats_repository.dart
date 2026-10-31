import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/features/chat/domain/chats_repository.dart';

/// Empty event-inbox for SAFE_MOCK (no Production `/chats` calls).
class FakeChatsRepository implements ChatsRepository {
  @override
  Future<({Failure? failure, List<ChatThread> data, int unreadTotal})>
  list() async {
    return (failure: null, data: const <ChatThread>[], unreadTotal: 0);
  }

  @override
  Future<({Failure? failure, void data})> markRead(int eventId) async {
    return (failure: null, data: null);
  }
}
