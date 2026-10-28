import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';

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
}
