import 'dart:typed_data';

import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/qa/fake_qa_users.dart';
import 'package:fun_mobile/features/friends/domain/dm_models.dart';

/// In-memory DM transport shared by fake User A / User B.
///
/// No network. Reset via [clearPrivate] on logout / account switch.
class FakeDmsBus {
  FakeDmsBus();

  static final FakeDmsBus instance = FakeDmsBus()..seed();

  int _nextMessageId = 1;
  final Map<int, List<DmMessage>> _messages = {};
  final Map<int, Map<int, int>> _unreadByUser = {};
  final Map<int, Uint8List> _mediaBytes = {};
  final List<Map<String, dynamic>> postLog = [];

  /// When true, the next [postMessage] fails with [NetworkFailure].
  bool failNextPost = false;

  /// Captured multipart-style fields from the last successful post.
  Map<String, dynamic>? lastPostFields;

  late DmConversation conversation;

  void seed() {
    clearPrivate();
    conversation = DmConversation(
      id: 1,
      otherUser: FakeQaUsers.asFriend(FakeQaUsers.userB),
      lastMessage: null,
      unreadCount: 0,
    );
    _messages[1] = [];
    _unreadByUser[1] = {
      FakeQaUsers.userA.id: 0,
      FakeQaUsers.userB.id: 0,
    };
  }

  void clearPrivate() {
    _messages.clear();
    _unreadByUser.clear();
    _mediaBytes.clear();
    postLog.clear();
    lastPostFields = null;
    failNextPost = false;
    _nextMessageId = 1;
  }

  List<DmConversation> listFor(int viewerId) {
    final other = viewerId == FakeQaUsers.userA.id
        ? FakeQaUsers.asFriend(FakeQaUsers.userB)
        : FakeQaUsers.asFriend(FakeQaUsers.userA);
    final unread = _unreadByUser[conversation.id]?[viewerId] ?? 0;
    final msgs = _messages[conversation.id] ?? const <DmMessage>[];
    final last = msgs.isEmpty ? null : msgs.last;
    return [
      DmConversation(
        id: conversation.id,
        otherUser: other,
        lastMessage: last == null
            ? null
            : DmLastMessage(
                id: last.id,
                body: last.body.isEmpty
                    ? (last.messageType == 'voice'
                          ? 'Voice message'
                          : last.messageType == 'image'
                          ? 'Image'
                          : '')
                    : last.body,
                userId: last.userId,
                userName: last.userName,
                createdAt: last.createdAt,
              ),
        lastMessageAt: last?.createdAt,
        unreadCount: unread,
      ),
    ];
  }

  int unreadTotalFor(int viewerId) {
    return listFor(viewerId).fold<int>(0, (sum, c) => sum + c.unreadCount);
  }

  List<DmMessage> messages(int conversationId) {
    final list = List<DmMessage>.from(_messages[conversationId] ?? const []);
    list.sort((a, b) => a.id.compareTo(b.id));
    return list;
  }

  ({Failure? failure, DmMessage? data}) postMessage({
    required int conversationId,
    required int senderId,
    required String body,
    String? clientUuid,
    int? replyToId,
    String? mediaPath,
    String? mediaKind,
    int? durationMs,
  }) {
    final fields = <String, dynamic>{
      'conversation_id': conversationId,
      'sender_id': senderId,
      'body': body,
      'client_uuid': clientUuid,
      'reply_to_id': replyToId,
      'media_path': mediaPath,
      'media_kind': mediaKind,
      'duration_ms': durationMs,
    };
    postLog.add(fields);

    if (failNextPost) {
      failNextPost = false;
      return (failure: const NetworkFailure('Mock upload failed'), data: null);
    }

    if (mediaKind == 'voice' &&
        durationMs != null &&
        durationMs > 0 &&
        durationMs < 500) {
      return (
        failure: const ValidationFailure('Voice clip too short'),
        data: null,
      );
    }

    // Idempotent reconcile on client_uuid (mirrors backend contract).
    final uuid = clientUuid?.trim();
    if (uuid != null && uuid.isNotEmpty) {
      final existing = (_messages[conversationId] ?? const <DmMessage>[])
          .where((m) => m.clientUuid == uuid)
          .toList();
      if (existing.isNotEmpty) {
        lastPostFields = fields;
        return (failure: null, data: existing.first);
      }
    }

    final sender = senderId == FakeQaUsers.userA.id
        ? FakeQaUsers.userA
        : FakeQaUsers.userB;
    final type = mediaKind == 'voice'
        ? 'voice'
        : mediaKind == 'image'
        ? 'image'
        : 'text';
    final id = _nextMessageId++;
    final msg = DmMessage(
      id: id,
      conversationId: conversationId,
      body: body,
      createdAt: DateTime.now().toUtc(),
      userId: sender.id,
      userName: sender.name,
      userPublicId: sender.publicId,
      userInitials: FakeQaUsers.asFriend(sender).initials,
      messageType: type,
      mediaUrl: mediaPath != null ? 'mock://media/$id' : null,
      mediaMime: mediaKind == 'voice'
          ? 'audio/mp4'
          : mediaKind == 'image'
          ? 'image/jpeg'
          : null,
      mediaDurationMs: durationMs,
      replyToId: replyToId,
      clientUuid: uuid,
    );

    _messages.putIfAbsent(conversationId, () => []).add(msg);
    if (mediaPath != null) {
      _mediaBytes[id] = Uint8List.fromList(
        mediaKind == 'voice'
            ? List<int>.generate(64, (i) => i)
            : List<int>.generate(128, (i) => 255 - i),
      );
    }

    final recipientId = senderId == FakeQaUsers.userA.id
        ? FakeQaUsers.userB.id
        : FakeQaUsers.userA.id;
    final unread = _unreadByUser.putIfAbsent(conversationId, () => {});
    unread[recipientId] = (unread[recipientId] ?? 0) + 1;
    unread[senderId] = 0;

    lastPostFields = fields;
    return (failure: null, data: msg);
  }

  void markRead(int conversationId, int viewerId) {
    final unread = _unreadByUser.putIfAbsent(conversationId, () => {});
    unread[viewerId] = 0;
  }

  List<int>? media(int messageId) {
    final bytes = _mediaBytes[messageId];
    return bytes?.toList();
  }
}
