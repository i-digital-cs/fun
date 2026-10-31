import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';

/// Deterministic fixture identities for SAFE_MOCK_MESSAGING only.
abstract final class FakeQaUsers {
  static const userA = User(
    id: 900001,
    name: 'Test User A',
    email: 'user-a@mock.local',
    status: 'active',
    locale: 'en',
    emailVerified: true,
    canParticipateFlag: true,
    publicId: 'Funity-F900001',
  );

  static const userB = User(
    id: 900002,
    name: 'Test User B',
    email: 'user-b@mock.local',
    status: 'active',
    locale: 'en',
    emailVerified: true,
    canParticipateFlag: true,
    publicId: 'Funity-F900002',
  );

  static FriendUser asFriend(User user) {
    final parts = user.name.trim().split(RegExp(r'\s+'));
    final initials = parts.isEmpty
        ? '?'
        : parts.take(2).map((p) => p[0].toUpperCase()).join();
    return FriendUser(
      id: user.id,
      publicId: user.publicId ?? '',
      name: user.name,
      initials: initials,
      friendship: const FriendshipState(status: 'accepted'),
      canMessage: true,
    );
  }

  static User? byEmail(String email) {
    final e = email.trim().toLowerCase();
    if (e == userA.email) return userA;
    if (e == userB.email) return userB;
    return null;
  }
}
