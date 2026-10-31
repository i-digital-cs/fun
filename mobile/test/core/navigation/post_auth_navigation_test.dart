import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

void main() {
  const active = User(
    id: 1,
    name: 'A',
    email: 'a@x.com',
    status: 'active',
    locale: 'en',
    emailVerified: true,
    canParticipateFlag: true,
  );

  group('post-auth destination contract', () {
    test('login without returnTo lands on home', () {
      expect(resolvePostAuthLocation(active), '/home');
      expect(resolvePostAuthLocation(active, returnTo: null), '/home');
    });

    test('valid event returnTo preserved', () {
      expect(
        resolvePostAuthLocation(active, returnTo: '/events/42'),
        '/events/42',
      );
    });

    test('valid profile returnTo normalized', () {
      expect(
        resolvePostAuthLocation(active, returnTo: '/u/Funity-F001115'),
        '/u/Funity-F001115',
      );
    });

    test('invalid returnTo falls back to home', () {
      expect(
        resolvePostAuthLocation(active, returnTo: 'https://evil.example'),
        '/home',
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/u/../events/1'),
        '/home',
      );
    });

    test('auth cross-link preserves sanitized returnTo', () {
      expect(
        locationWithReturnTo('/register', returnTo: '/events/9'),
        '/register?returnTo=${Uri.encodeComponent('/events/9')}',
      );
      expect(
        locationWithReturnTo('/login', returnTo: 'https://evil'),
        '/login',
      );
    });
  });
}
