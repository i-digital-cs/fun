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
  final DateTime? readAt;
  final DateTime? createdAt;

  bool get isUnread => readAt == null;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id']?.toString() ?? '',
      type: json['type']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      body: json['body']?.toString() ?? '',
      url: json['url']?.toString(),
      apiPath: json['api_path']?.toString(),
      eventId: (json['event_id'] as num?)?.toInt(),
      joinRequestId: (json['join_request_id'] as num?)?.toInt(),
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
