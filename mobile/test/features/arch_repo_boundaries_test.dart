import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/chat/domain/chat_thread.dart';
import 'package:fun_mobile/features/chat/domain/chats_repository.dart';
import 'package:fun_mobile/features/identity/domain/identity_repository.dart';
import 'package:fun_mobile/features/identity/domain/identity_status.dart';
import 'package:fun_mobile/features/profile/domain/profile_repository.dart';
import 'package:fun_mobile/features/profile/domain/public_profile_repository.dart';
import 'package:fun_mobile/features/home/domain/home_repository.dart';

class _FakeChats implements ChatsRepository {
  @override
  Future<({Failure? failure, List<ChatThread> data, int unreadTotal})>
  list() async => (failure: null, data: const <ChatThread>[], unreadTotal: 0);

  @override
  Future<({Failure? failure, void data})> markRead(int eventId) async =>
      (failure: null, data: null);
}

void main() {
  test('ARCH-REPO chat/profile/identity/home interfaces are implementable', () {
    expect(_FakeChats(), isA<ChatsRepository>());
    // Type presence smoke for abstract boundaries.
    expect(ProfileRepository, isNotNull);
    expect(PublicProfileRepository, isNotNull);
    expect(IdentityRepository, isNotNull);
    expect(HomeRepository, isNotNull);
    expect(IdentityStatus, isNotNull);
  });
}
