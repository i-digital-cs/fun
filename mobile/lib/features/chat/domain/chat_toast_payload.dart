/// In-app toast kinds — mirror web `.chat-toast` (chat + join + message reaction).
enum InAppToastKind { chat, dm, joinAccepted, joinRequested, messageReacted }

/// Payload for foreground sweet toasts (Pusher realtime or FCM-while-open fallback).
class InAppToastPayload {
  const InAppToastPayload({
    required this.kind,
    required this.dedupeKey,
    required this.eventId,
    required this.eventTitle,
    required this.title,
    this.body = '',
    this.senderInitials = '?',
    this.senderPhotoUrl,
    this.openPath,
    this.conversationId = 0,
  });

  final InAppToastKind kind;
  final String dedupeKey;
  final int eventId;
  final int conversationId;
  final String eventTitle;
  final String title;
  final String body;
  final String senderInitials;
  final String? senderPhotoUrl;
  final String? openPath;

  String get resolvedOpenPath {
    if (openPath != null && openPath!.isNotEmpty) return openPath!;
    return switch (kind) {
      InAppToastKind.chat || InAppToastKind.messageReacted =>
        '/events/$eventId/chat',
      InAppToastKind.dm =>
        conversationId > 0 ? '/dms/$conversationId' : '/chats?tab=direct',
      InAppToastKind.joinAccepted => '/events/$eventId',
      InAppToastKind.joinRequested => '/events/$eventId/requests',
    };
  }

  /// User channel `.dm.message` / [UserDirectMessageReceived].
  factory InAppToastPayload.fromDmJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map ? Map<String, dynamic>.from(user) : const {};
    final name =
        userMap['name'] as String? ?? json['sender_name'] as String? ?? '';
    final initials =
        userMap['initials'] as String? ??
        (name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase());
    final photo = userMap['profile_image_path'] as String?;
    final messageId = (json['id'] as num?)?.toInt() ?? 0;
    final conversationId = (json['conversation_id'] as num?)?.toInt() ?? 0;

    return InAppToastPayload(
      kind: InAppToastKind.dm,
      dedupeKey: messageId > 0 ? 'dm:$messageId' : 'dm:$conversationId',
      eventId: 0,
      conversationId: conversationId,
      eventTitle: name,
      title: name,
      senderInitials: initials,
      senderPhotoUrl: (photo != null && photo.isNotEmpty) ? photo : null,
      openPath: conversationId > 0 ? '/dms/$conversationId' : null,
    );
  }

  /// Reverb `.chat.message` / [UserChatMessageReceived].
  factory InAppToastPayload.fromChatJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map ? Map<String, dynamic>.from(user) : const {};
    final name = userMap['name'] as String? ?? '';
    final initials =
        userMap['initials'] as String? ??
        (name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase());
    final photo = userMap['profile_image_path'] as String?;
    final messageId = (json['id'] as num?)?.toInt() ?? 0;
    final eventId = (json['event_id'] as num?)?.toInt() ?? 0;

    return InAppToastPayload(
      kind: InAppToastKind.chat,
      dedupeKey: messageId > 0 ? 'chat:$messageId' : 'chat:$eventId:${name.hashCode}',
      eventId: eventId,
      eventTitle: json['event_title'] as String? ?? '',
      title: name,
      senderInitials: initials,
      senderPhotoUrl: (photo != null && photo.isNotEmpty) ? photo : null,
    );
  }

  /// `.join.accepted` / [UserJoinAccepted].
  factory InAppToastPayload.fromJoinAcceptedJson(Map<String, dynamic> json) {
    final eventId = (json['event_id'] as num?)?.toInt() ?? 0;
    final eventTitle = json['event_title'] as String? ?? '';

    return InAppToastPayload(
      kind: InAppToastKind.joinAccepted,
      dedupeKey: 'join_accepted:$eventId',
      eventId: eventId,
      eventTitle: eventTitle,
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      senderInitials: '✓',
      openPath: '/events/$eventId',
    );
  }

  /// `.join.requested` / [UserJoinRequested].
  factory InAppToastPayload.fromJoinRequestedJson(Map<String, dynamic> json) {
    final eventId = (json['event_id'] as num?)?.toInt() ?? 0;
    final eventTitle = json['event_title'] as String? ?? '';
    final joinRequestId = (json['join_request_id'] as num?)?.toInt() ?? 0;
    final requester = json['requester'];
    final requesterMap =
        requester is Map ? Map<String, dynamic>.from(requester) : const {};
    final name = requesterMap['name'] as String? ?? '';
    final initials = name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase();

    return InAppToastPayload(
      kind: InAppToastKind.joinRequested,
      dedupeKey: joinRequestId > 0
          ? 'join_request:$joinRequestId'
          : 'join_request:$eventId:${name.hashCode}',
      eventId: eventId,
      eventTitle: eventTitle,
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      senderInitials: initials,
      openPath: '/events/$eventId/requests',
    );
  }

  /// `.chat.reaction` / [UserMessageReacted].
  factory InAppToastPayload.fromMessageReactedJson(Map<String, dynamic> json) {
    final eventId = (json['event_id'] as num?)?.toInt() ?? 0;
    final messageId = (json['message_id'] as num?)?.toInt() ?? 0;
    final reaction = json['reaction'] as String? ?? 'like';
    final actor = json['actor'];
    final actorMap = actor is Map ? Map<String, dynamic>.from(actor) : const {};
    final name = actorMap['name'] as String? ?? '';
    final initials =
        actorMap['initials'] as String? ??
        (name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase());
    final photo = actorMap['profile_image_path'] as String?;

    return InAppToastPayload(
      kind: InAppToastKind.messageReacted,
      dedupeKey: messageId > 0
          ? 'react:$messageId:$reaction'
          : 'react:$eventId:$reaction:${name.hashCode}',
      eventId: eventId,
      eventTitle: json['event_title'] as String? ?? '',
      title: json['title'] as String? ?? '',
      body: json['body'] as String? ?? '',
      senderInitials: initials,
      senderPhotoUrl: (photo != null && photo.isNotEmpty) ? photo : null,
      openPath: '/events/$eventId/chat',
    );
  }

  /// FCM data map while the app is foreground (no system tray).
  static InAppToastPayload? tryFromFcmData(Map<String, dynamic> data) {
    final type = data['type'] as String? ?? '';
    final title = data['title'] as String? ?? '';
    final body = data['body'] as String? ?? '';
    final eventTitle = data['event_title'] as String? ?? title;

    if (type == 'dm_message') {
      final conversationId =
          int.tryParse('${data['conversation_id'] ?? ''}') ?? 0;
      final messageId = int.tryParse('${data['message_id'] ?? ''}') ?? 0;
      if (conversationId <= 0) return null;
      return InAppToastPayload(
        kind: InAppToastKind.dm,
        dedupeKey: messageId > 0 ? 'dm:$messageId' : 'dm:fcm:$conversationId',
        eventId: 0,
        conversationId: conversationId,
        eventTitle: title,
        title: title,
        body: body,
        senderInitials: '?',
        openPath: '/dms/$conversationId',
      );
    }

    final eventId = int.tryParse('${data['event_id'] ?? ''}') ?? 0;
    if (eventId <= 0) return null;

    switch (type) {
      case 'chat_message':
        final messageId = int.tryParse('${data['message_id'] ?? ''}') ?? 0;
        return InAppToastPayload(
          kind: InAppToastKind.chat,
          dedupeKey: messageId > 0 ? 'chat:$messageId' : 'chat:fcm:$eventId',
          eventId: eventId,
          eventTitle: eventTitle,
          title: title,
          body: body,
          senderInitials: '?',
        );
      case 'join_accepted':
        return InAppToastPayload(
          kind: InAppToastKind.joinAccepted,
          dedupeKey: 'join_accepted:$eventId',
          eventId: eventId,
          eventTitle: eventTitle,
          title: title,
          body: body,
          senderInitials: '✓',
          openPath: '/events/$eventId',
        );
      case 'join_request':
        final joinRequestId =
            int.tryParse('${data['join_request_id'] ?? ''}') ?? 0;
        return InAppToastPayload(
          kind: InAppToastKind.joinRequested,
          dedupeKey: joinRequestId > 0
              ? 'join_request:$joinRequestId'
              : 'join_request:fcm:$eventId',
          eventId: eventId,
          eventTitle: eventTitle,
          title: title,
          body: body,
          senderInitials: '?',
          openPath: '/events/$eventId/requests',
        );
      case 'message_reacted':
        final messageId = int.tryParse('${data['message_id'] ?? ''}') ?? 0;
        final reaction = data['reaction'] as String? ?? 'like';
        return InAppToastPayload(
          kind: InAppToastKind.messageReacted,
          dedupeKey: messageId > 0
              ? 'react:$messageId:$reaction'
              : 'react:fcm:$eventId:$reaction',
          eventId: eventId,
          eventTitle: eventTitle,
          title: title,
          body: body,
          senderInitials: reaction == 'dislike' ? '👎' : '👍',
          openPath: '/events/$eventId/chat',
        );
      default:
        return null;
    }
  }
}

/// Backward-compatible alias used by chat poll / older call sites.
typedef ChatToastPayload = InAppToastPayload;
