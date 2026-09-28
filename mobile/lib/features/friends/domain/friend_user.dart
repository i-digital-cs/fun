import 'package:equatable/equatable.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';

/// Public profile card from `/friends`, search, or request rows.
class FriendUser extends Equatable {
  const FriendUser({
    required this.id,
    required this.publicId,
    required this.name,
    required this.initials,
    this.profileImagePath,
    this.roleLabel,
    this.friendship = const FriendshipState(),
    this.canMessage = false,
  });

  final int id;
  final String publicId;
  final String name;
  final String initials;
  final String? profileImagePath;
  final String? roleLabel;
  final FriendshipState friendship;
  final bool canMessage;

  factory FriendUser.fromJson(Map<String, dynamic> json) {
    final friendshipJson = json['friendship'];
    return FriendUser(
      id: (json['id'] as num?)?.toInt() ?? 0,
      publicId: json['public_id'] as String? ?? '',
      name: json['name'] as String? ?? '',
      initials: json['initials'] as String? ?? '?',
      profileImagePath: json['profile_image_path'] as String?,
      roleLabel: json['role_label'] as String?,
      friendship: FriendshipState.fromJson(
        friendshipJson is Map
            ? Map<String, dynamic>.from(friendshipJson)
            : null,
      ),
      canMessage: json['can_message'] as bool? ?? false,
    );
  }

  @override
  List<Object?> get props => [
    id,
    publicId,
    name,
    initials,
    profileImagePath,
    roleLabel,
    friendship,
    canMessage,
  ];
}

class FriendRequestRow extends Equatable {
  const FriendRequestRow({
    required this.id,
    required this.status,
    required this.user,
    this.createdAt,
  });

  final int id;
  final String status;
  final FriendUser user;
  final DateTime? createdAt;

  factory FriendRequestRow.fromJson(Map<String, dynamic> json) {
    final userJson = json['user'];
    return FriendRequestRow(
      id: (json['id'] as num?)?.toInt() ?? 0,
      status: json['status'] as String? ?? '',
      createdAt: json['created_at'] is String
          ? DateTime.tryParse(json['created_at'] as String)
          : null,
      user: userJson is Map
          ? FriendUser.fromJson(Map<String, dynamic>.from(userJson))
          : const FriendUser(id: 0, publicId: '', name: '', initials: '?'),
    );
  }

  @override
  List<Object?> get props => [id, status, user, createdAt];
}

class FriendRequestsBundle extends Equatable {
  const FriendRequestsBundle({
    this.incoming = const [],
    this.outgoing = const [],
  });

  final List<FriendRequestRow> incoming;
  final List<FriendRequestRow> outgoing;

  @override
  List<Object?> get props => [incoming, outgoing];
}
