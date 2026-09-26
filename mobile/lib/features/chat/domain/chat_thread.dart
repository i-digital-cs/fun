import 'package:flutter/foundation.dart';

@immutable
class ChatThread {
  const ChatThread({
    required this.eventId,
    required this.title,
    this.city,
    this.imageUrl,
    this.startsAt,
    this.chatUrl,
    this.unreadCount = 0,
    this.lastMessage,
  });

  final int eventId;
  final String title;
  final String? city;
  final String? imageUrl;
  final DateTime? startsAt;
  final String? chatUrl;
  final int unreadCount;
  final ChatLastMessage? lastMessage;

  factory ChatThread.fromJson(Map<String, dynamic> json) {
    final last = json['last_message'];
    final startsRaw = json['starts_at'];
    return ChatThread(
      eventId: (json['event_id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      city: json['city'] as String?,
      imageUrl: json['image_url'] as String?,
      startsAt: startsRaw is String ? DateTime.tryParse(startsRaw) : null,
      chatUrl: json['chat_url'] as String?,
      unreadCount: (json['unread_count'] as num?)?.toInt() ?? 0,
      lastMessage: last is Map
          ? ChatLastMessage.fromJson(Map<String, dynamic>.from(last))
          : null,
    );
  }
}

@immutable
class ChatLastMessage {
  const ChatLastMessage({
    required this.id,
    required this.body,
    this.createdAt,
    this.isMine = false,
    this.userName,
  });

  final int id;
  final String body;
  final String? createdAt;
  final bool isMine;
  final String? userName;

  DateTime? get createdAtDate =>
      createdAt != null ? DateTime.tryParse(createdAt!) : null;

  factory ChatLastMessage.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    return ChatLastMessage(
      id: (json['id'] as num?)?.toInt() ?? 0,
      body: json['body'] as String? ?? '',
      createdAt: json['created_at'] as String?,
      isMine: json['is_mine'] == true,
      userName: user is Map ? user['name'] as String? : null,
    );
  }
}
