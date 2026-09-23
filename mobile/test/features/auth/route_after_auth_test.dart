import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

void main() {
  group('User.routeAfterAuth', () {
    test('email verify first', () {
      const user = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'pending_documents',
        locale: 'en',
        emailVerified: false,
        needsEmailVerification: true,
        needsIdentityDocuments: true,
      );
      expect(user.routeAfterAuth, '/email/verify');
    });

    test('identity after email', () {
      const user = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'pending_documents',
        locale: 'en',
        emailVerified: true,
        needsIdentityDocuments: true,
      );
      expect(user.routeAfterAuth, '/identity');
    });

    test('home when active', () {
      const user = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'active',
        locale: 'en',
        emailVerified: true,
        canParticipateFlag: true,
      );
      expect(user.routeAfterAuth, '/home');
    });
  });
}
