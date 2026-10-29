import 'package:equatable/equatable.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';

class DmLastMessage extends Equatable {
  const DmLastMessage({
    required this.id,
    required this.body,
    this.userId,
    this.userName,
    this.createdAt,
  });

  final int id;
  final String body;
  final int? userId;
  final String? userName;
  final DateTime? createdAt;

  factory DmLastMessage.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const DmLastMessage(id: 0, body: '');
    }
    return DmLastMessage(
      id: (json['id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      userId: (json['user_id'] as num?)?.toInt(),
      userName: json['user_name'] as String?,
      createdAt: json['created_at'] is String
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
    );
  }

  @override
  List<Object?> get props => [id, body, userId, userName, createdAt];
}

class DmConversation extends Equatable {
  const DmConversation({
    required this.id,
    required this.otherUser,
    this.lastMessage,
    this.lastMessageAt,
    this.unreadCount = 0,
  });

  final int id;
  final FriendUser otherUser;
  final DmLastMessage? lastMessage;
  final DateTime? lastMessageAt;
  final int unreadCount;

  factory DmConversation.fromJson(Map<String, dynamic> json) {
    final other = json['other_user'];
    final last = json['last_message'];
    return DmConversation(
      id: (json['id'] as num?)?.toInt() ?? 0,
      otherUser: other is Map
          ? FriendUser.fromJson(Map<String, dynamic>.from(other))
          : const FriendUser(id: 0, publicId: '', name: '', initials: '?'),
      lastMessage: last is Map
          ? DmLastMessage.fromJson(Map<String, dynamic>.from(last))
          : null,
      lastMessageAt: json['last_message_at'] is String
          ? DateTime.tryParse(json['last_message_at'] as String)
          : null,
      unreadCount: (json['unread_count'] as num?)?.toInt() ?? 0,
    );
  }

  @override
  List<Object?> get props => [
    id,
    otherUser,
    lastMessage,
    lastMessageAt,
    unreadCount,
  ];
}

class DmMessage extends Equatable {
  const DmMessage({
    required this.id,
    required this.conversationId,
    required this.body,
    this.createdAt,
    this.userId,
    this.userName,
    this.userPublicId,
    this.userInitials,
    this.userPhotoUrl,
    this.messageType = 'text',
    this.mediaUrl,
    this.mediaMime,
    this.mediaDurationMs,
    this.replyToId,
    this.replyTo,
    this.clientUuid,
  });

  final int id;
  final int conversationId;
  final String body;
  final DateTime? createdAt;
  final int? userId;
  final String? userName;
  final String? userPublicId;
  final String? userInitials;
  final String? userPhotoUrl;
  final String messageType;
  final String? mediaUrl;
  final String? mediaMime;
  final int? mediaDurationMs;
  final int? replyToId;
  final DmMessageReply? replyTo;
  final String? clientUuid;

  factory DmMessage.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map ? Map<String, dynamic>.from(user) : const {};
    return DmMessage(
      id: (json['id'] as num?)?.toInt() ?? 0,
      conversationId: (json['conversation_id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      createdAt: json['created_at'] is String
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      userId: (userMap['id'] as num?)?.toInt(),
      userName: userMap['name'] as String?,
      userPublicId: userMap['public_id'] as String?,
      userInitials: userMap['initials'] as String?,
      userPhotoUrl: userMap['profile_image_path'] as String?,
      messageType: json['message_type'] as String? ?? 'text',
      mediaUrl: json['media_url'] as String?,
      mediaMime: json['media_mime'] as String?,
      mediaDurationMs: (json['media_duration_ms'] as num?)?.toInt(),
      replyToId: (json['reply_to_id'] as num?)?.toInt(),
      replyTo: json['reply_to'] is Map
          ? DmMessageReply.fromJson(
              Map<String, dynamic>.from(json['reply_to'] as Map),
            )
          : null,
      clientUuid: json['client_uuid'] as String?,
    );
  }

  @override
  List<Object?> get props => [
    id,
    conversationId,
    body,
    createdAt,
    userId,
    userName,
    userPublicId,
    userInitials,
    userPhotoUrl,
    messageType,
    mediaUrl,
    mediaMime,
    mediaDurationMs,
    replyToId,
    replyTo,
    clientUuid,
  ];
}

class DmMessageReply extends Equatable {
  const DmMessageReply({required this.id, required this.body, this.userName});

  final int id;
  final String body;
  final String? userName;

  factory DmMessageReply.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map
        ? Map<String, dynamic>.from(user)
        : const <String, dynamic>{};
    return DmMessageReply(
      id: (json['id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      userName: userMap['name'] as String?,
    );
  }

  @override
  List<Object?> get props => [id, body, userName];
}

class DmOpenResult extends Equatable {
  const DmOpenResult({required this.conversationId});

  final int conversationId;

  factory DmOpenResult.fromJson(Map<String, dynamic> json) {
    return DmOpenResult(conversationId: (json['id'] as num?)?.toInt() ?? 0);
  }

  @override
  List<Object?> get props => [conversationId];
}
