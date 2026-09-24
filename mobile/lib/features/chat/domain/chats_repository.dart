import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';

/// Inbox / chats list boundary (ARCH-REPO-CHAT-001).
abstract class ChatsRepository {
  Future<({Failure? failure, List<ChatThread> data, int unreadTotal})> list();

  Future<({Failure? failure, void data})> markRead(int eventId);
}
