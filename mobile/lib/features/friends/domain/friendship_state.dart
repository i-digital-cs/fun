import 'package:equatable/equatable.dart';

/// Mirrors API `friendship` object from `/users/{publicId}` and search.
class FriendshipState extends Equatable {
  const FriendshipState({
    this.status,
    this.direction,
    this.canMessage = false,
    this.canRequest = false,
  });

  /// `null`, `pending`, `accepted`, `declined`, `blocked`, or `self`.
  final String? status;

  /// `incoming` or `outgoing` when pending/declined.
  final String? direction;
  final bool canMessage;
  final bool canRequest;

  factory FriendshipState.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return const FriendshipState();
    }
    return FriendshipState(
      status: json['status'] as String?,
      direction: json['direction'] as String?,
      canMessage: json['can_message'] as bool? ?? false,
      canRequest: json['can_request'] as bool? ?? false,
    );
  }

  @override
  List<Object?> get props => [status, direction, canMessage, canRequest];
}
