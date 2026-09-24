/// Payload from Reverb `.chat.message` / [UserChatMessageReceived].
class ChatToastPayload {
  const ChatToastPayload({
    required this.messageId,
    required this.eventId,
    required this.eventTitle,
    required this.senderName,
    required this.senderInitials,
    this.senderPhotoUrl,
  });

  final int messageId;
  final int eventId;
  final String eventTitle;
  final String senderName;
  final String senderInitials;
  final String? senderPhotoUrl;

  factory ChatToastPayload.fromJson(Map<String, dynamic> json) {
    final user = json['user'];
    final userMap = user is Map ? Map<String, dynamic>.from(user) : const {};
    final name = userMap['name'] as String? ?? '';
    final initials =
        userMap['initials'] as String? ??
        (name.trim().isEmpty ? '?' : name.trim()[0].toUpperCase());
    final photo = userMap['profile_image_path'] as String?;

    return ChatToastPayload(
      messageId: (json['id'] as num?)?.toInt() ?? 0,
      eventId: (json['event_id'] as num?)?.toInt() ?? 0,
      eventTitle: json['event_title'] as String? ?? '',
      senderName: name,
      senderInitials: initials,
      senderPhotoUrl: (photo != null && photo.isNotEmpty) ? photo : null,
    );
  }
}
