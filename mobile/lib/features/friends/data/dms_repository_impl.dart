import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/friends/data/dms_remote_source.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:fun_mobile/features/friends/domain/dms_repository.dart';

class DmsRepositoryImpl implements DmsRepository {
  DmsRepositoryImpl(this._remote);

  final DmsRemoteSource _remote;

  @override
  Future<({Failure? failure, List<DmConversation> data, int unreadTotal})>
  list() => _remote.list();

  @override
  Future<({Failure? failure, DmOpenResult? data})> openWith(int userId) =>
      _remote.openWith(userId);

  @override
  Future<({Failure? failure, List<DmMessage> data})> messages(
    int conversationId, {
    CancelToken? cancelToken,
  }) => _remote.messages(conversationId, cancelToken: cancelToken);

  @override
  Future<({Failure? failure, DmMessage? data})> postMessage(
    int conversationId,
    String body,
  ) => _remote.postMessage(conversationId, body);

  @override
  Future<({Failure? failure, void data})> markRead(int conversationId) =>
      _remote.markRead(conversationId);
}
