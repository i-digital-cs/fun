import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

void main() {
  group('sanitizeReturnTo', () {
    test('allows event and shell paths', () {
      expect(sanitizeReturnTo('/events/42'), '/events/42');
      expect(sanitizeReturnTo('/events/42/chat'), '/events/42/chat');
      expect(sanitizeReturnTo('/home'), '/home');
      expect(sanitizeReturnTo('/friends?tab=requests'), '/friends?tab=requests');
      expect(sanitizeReturnTo('/u/abc123'), '/u/abc123');
      expect(sanitizeReturnTo('/dms/9'), '/dms/9');
    });

    test('rejects external and auth traps', () {
      expect(sanitizeReturnTo('https://evil.example/x'), isNull);
      expect(sanitizeReturnTo('//evil.example'), isNull);
      expect(sanitizeReturnTo('/login'), isNull);
      expect(sanitizeReturnTo('/register'), isNull);
      expect(sanitizeReturnTo('/splash'), isNull);
      expect(sanitizeReturnTo('../etc/passwd'), isNull);
      expect(sanitizeReturnTo(''), isNull);
      expect(sanitizeReturnTo(null), isNull);
    });

    test('loginLocation encodes returnTo', () {
      expect(
        loginLocation(returnTo: '/events/7'),
        '/login?returnTo=${Uri.encodeComponent('/events/7')}',
      );
      expect(loginLocation(returnTo: 'https://evil'), '/login');
    });
  });

  group('resolvePostAuthLocation', () {
    const active = User(
      id: 1,
      name: 'A',
      email: 'a@x.com',
      status: 'active',
      locale: 'en',
      emailVerified: true,
      canParticipateFlag: true,
    );

    test('honors deep-link returnTo for active users', () {
      expect(
        resolvePostAuthLocation(active, returnTo: '/events/42'),
        '/events/42',
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/friends'),
        '/friends',
      );
    });

    test('falls back to home without returnTo', () {
      expect(resolvePostAuthLocation(active), '/home');
      expect(resolvePostAuthLocation(active, returnTo: 'https://evil'), '/home');
    });

    test('chains returnTo through email verify gate', () {
      const pendingEmail = User(
        id: 2,
        name: 'B',
        email: 'b@x.com',
        status: 'pending_documents',
        locale: 'en',
        emailVerified: false,
        needsEmailVerification: true,
        needsIdentityDocuments: true,
      );
      expect(
        resolvePostAuthLocation(pendingEmail, returnTo: '/events/9'),
        '/email/verify?returnTo=${Uri.encodeComponent('/events/9')}',
      );
    });

    test('chains returnTo through identity gate', () {
      const pendingId = User(
        id: 3,
        name: 'C',
        email: 'c@x.com',
        status: 'pending_documents',
        locale: 'en',
        emailVerified: true,
        needsIdentityDocuments: true,
      );
      expect(
        resolvePostAuthLocation(pendingId, returnTo: '/create'),
        '/identity?returnTo=${Uri.encodeComponent('/create')}',
      );
    });
  });
}
