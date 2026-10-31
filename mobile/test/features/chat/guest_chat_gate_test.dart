import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';

void main() {
  test('private messaging routes are not guest-browsable', () {
    expect(isGuestBrowsableLocation('/chats'), isFalse);
    expect(isGuestBrowsableLocation('/chats?tab=direct'), isFalse);
    expect(isGuestBrowsableLocation('/dms/12'), isFalse);
    expect(isGuestBrowsableLocation('/events/9/chat'), isFalse);
  });

  test('guest messaging actions preserve returnTo via loginLocation', () {
    expect(
      loginLocation(returnTo: '/chats'),
      '/login?returnTo=${Uri.encodeComponent('/chats')}',
    );
    expect(
      loginLocation(returnTo: '/dms/12'),
      '/login?returnTo=${Uri.encodeComponent('/dms/12')}',
    );
    expect(
      loginLocation(returnTo: '/events/9/chat'),
      '/login?returnTo=${Uri.encodeComponent('/events/9/chat')}',
    );
  });
}
