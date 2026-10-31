import 'package:dio/dio.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/qa/fake_dms_bus.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';
import 'package:fun_mobile/features/friends/domain/dms_repository.dart';

/// In-memory [DmsRepository] for SAFE_MOCK_MESSAGING / unit tests only.
///
/// Wired from [main] only when [SafeMock.isActive]. Never selected in release.
class FakeDmsRepository implements DmsRepository {
  FakeDmsRepository({
    required this.bus,
    required int Function() viewerId,
  }) : _viewerId = viewerId;

  final FakeDmsBus bus;
  final int Function() _viewerId;

  int get viewerId => _viewerId();

  @override
  Future<({Failure? failure, List<DmConversation> data, int unreadTotal})>
  list() async {
    final data = bus.listFor(viewerId);
    return (
      failure: null,
      data: data,
      unreadTotal: bus.unreadTotalFor(viewerId),
    );
  }

  @override
  Future<({Failure? failure, DmOpenResult? data})> openWith(int userId) async {
    return (
      failure: null,
      data: DmOpenResult(conversationId: bus.conversation.id),
    );
  }

  @override
  Future<({Failure? failure, List<DmMessage> data})> messages(
    int conversationId, {
    CancelToken? cancelToken,
  }) async {
    return (failure: null, data: bus.messages(conversationId));
  }

  @override
  Future<({Failure? failure, DmMessage? data})> postMessage(
    int conversationId,
    String body, {
    String? clientUuid,
    int? replyToId,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  }) async {
    return bus.postMessage(
      conversationId: conversationId,
      senderId: viewerId,
      body: body,
      clientUuid: clientUuid,
      replyToId: replyToId,
      mediaPath: mediaPath,
      mediaKind: mediaKind,
      durationMs: durationMs,
    );
  }

  @override
  Future<({Failure? failure, void data})> react(
    int conversationId,
    int messageId,
    String emoji,
  ) async {
    return (failure: null, data: null);
  }

  @override
  Future<({Failure? failure, List<int>? data})> media(int messageId) async {
    final bytes = bus.media(messageId);
    if (bytes == null) {
      return (failure: const NotFoundFailure('Mock media missing'), data: null);
    }
    return (failure: null, data: bytes);
  }

  @override
  Future<({Failure? failure, void data})> markRead(int conversationId) async {
    bus.markRead(conversationId, viewerId);
    return (failure: null, data: null);
  }
}
