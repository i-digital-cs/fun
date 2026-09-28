import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';

abstract class DmsRepository {
  Future<({Failure? failure, List<DmConversation> data, int unreadTotal})>
  list();

  Future<({Failure? failure, DmOpenResult? data})> openWith(int userId);

  Future<({Failure? failure, List<DmMessage> data})> messages(
    int conversationId, {
    CancelToken? cancelToken,
  });

  Future<({Failure? failure, DmMessage? data})> postMessage(
    int conversationId,
    String body,
  );

  Future<({Failure? failure, void data})> markRead(int conversationId);
}
