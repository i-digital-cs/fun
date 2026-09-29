import 'package:equatable/equatable.dart';

class AppNotification extends Equatable {
  const AppNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.body,
    this.url,
    this.apiPath,
    this.eventId,
    this.joinRequestId,
    this.requesterId,
    this.accepterId,
    this.readAt,
    this.createdAt,
  });

  final String id;
  final String type;
  final String title;
  final String body;
  final String? url;
  final String? apiPath;
  final int? eventId;
  final int? joinRequestId;
  final int? requesterId;
  final int? accepterId;
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    final nested = json['data'];
    final data = nested is Map
        ? Map<String, dynamic>.from(nested)
        : const <String, dynamic>{};

    int? asInt(dynamic v) => v is num ? v.toInt() : int.tryParse('$v');

    return AppNotification(
      id: json['id']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      body: json['body']?.toString() ?? '',
      url: json['url']?.toString(),
      apiPath: json['api_path']?.toString(),
      eventId: asInt(json['event_id'] ?? data['event_id']),
      joinRequestId: asInt(json['join_request_id'] ?? data['join_request_id']),
      requesterId: asInt(json['requester_id'] ?? data['requester_id']),
      accepterId: asInt(json['accepter_id'] ?? data['accepter_id']),
      readAt: json['read_at'] != null
          ? DateTime.tryParse(json['read_at'].toString())
          : null,
      createdAt: json['created_at'] != null
          ? DateTime.tryParse(json['created_at'].toString())
          : null,
    );
  }

  @override
  List<Object?> get props => [id, type, title, body, readAt, createdAt];
}
