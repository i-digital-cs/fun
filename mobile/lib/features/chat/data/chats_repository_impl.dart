import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/chat/data/chats_remote_source.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/features/chat/domain/chats_repository.dart';

class ChatsRepositoryImpl implements ChatsRepository {
  ChatsRepositoryImpl(this._remote);

  final ChatsRemoteSource _remote;

  @override
  Future<({Failure? failure, List<ChatThread> data, int unreadTotal})> list() =>
      _remote.list();

  @override
  Future<({Failure? failure, void data})> markRead(int eventId) =>
      _remote.markRead(eventId);
}
