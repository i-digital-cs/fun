import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/core/qa/fake_dms_bus.dart';
import 'package:fun_mobile/core/qa/fake_dms_repository.dart';
import 'package:fun_mobile/core/qa/fake_qa_auth_repository.dart';
import 'package:fun_mobile/core/qa/fake_qa_users.dart';
void main() {
  late FakeDmsBus bus;
  late FakeQaAuthRepository auth;

  setUp(() {
    bus = FakeDmsBus()..seed();
    auth = FakeQaAuthRepository(bus: bus);
  });

  test('fake login lands on allowlisted returnTo destinations', () async {
    final session = await auth.login(
      email: FakeQaUsers.userA.email,
      password: 'mock',
    );
    expect(session.failure, isNull);
    expect(session.data?.user.id, FakeQaUsers.userA.id);

    expect(
      resolvePostAuthLocation(session.data!.user, returnTo: '/chats'),
      '/chats',
    );
    expect(
      resolvePostAuthLocation(session.data!.user, returnTo: '/events/9'),
      '/events/9',
    );
    expect(
      resolvePostAuthLocation(
        session.data!.user,
        returnTo: '/u/Funity-F001115',
      ),
      '/u/Funity-F001115',
    );
  });

  test('logout clears token and private DM bus', () async {
    final repo = FakeDmsRepository(
      bus: bus,
      viewerId: () => FakeQaUsers.userA.id,
    );
    await repo.postMessage(1, 'secret', clientUuid: 's1');
    expect((await repo.messages(1)).data, isNotEmpty);

    await auth.logout();
    expect(await auth.hasToken(), isFalse);
    expect((await auth.me()).failure, isNotNull);
    expect(bus.messages(1), isEmpty);
  });

  test('account switch clears prior private messages', () async {
    final asA = FakeDmsRepository(
      bus: bus,
      viewerId: () => FakeQaUsers.userA.id,
    );
    await asA.postMessage(1, 'A only', clientUuid: 'a-only');

    await auth.login(email: FakeQaUsers.userB.email, password: 'mock');
    expect(auth.currentUser?.id, FakeQaUsers.userB.id);
    expect(bus.messages(1), isEmpty);
  });

  test('expired session behaves as guest (no me user)', () async {
    auth.expireSession();
    expect(await auth.hasToken(), isFalse);
    expect((await auth.me()).data, isNull);
  });

  test('returnTo rejects external redirects', () {
    expect(sanitizeReturnTo('https://evil.example'), isNull);
    expect(loginLocation(returnTo: 'https://evil.example'), '/login');
  });

  test('guest messaging routes stay gated', () {
    expect(isGuestBrowsableLocation('/chats'), isFalse);
    expect(isGuestBrowsableLocation('/dms/1'), isFalse);
    expect(isGuestBrowsableLocation('/home'), isTrue);
  });

  test('OTP / forgot-password paths are disabled in mock auth', () async {
    final otp = await auth.resendEmailVerify();
    expect(otp.failure, isNotNull);
    final forgot = await auth.forgotPassword('anyone@example.com');
    expect(forgot.failure, isNotNull);
  });

  test('post-auth join destination is event detail not auto-join', () {
    const user = FakeQaUsers.userA;
    expect(
      resolvePostAuthLocation(user, returnTo: '/events/42'),
      '/events/42',
    );
    // Join remains a manual action after login (no /join auto path).
    expect(sanitizeReturnTo('/events/42/join'), isNull);
  });
}
