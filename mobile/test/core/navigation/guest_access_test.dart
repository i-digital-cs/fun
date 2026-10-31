import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:go_router/go_router.dart';

void main() {
  group('isGuestBrowsableLocation', () {
    test('allows Home, Discover, public event, profile, legal', () {
      expect(isGuestBrowsableLocation('/home'), isTrue);
      expect(isGuestBrowsableLocation('/discover'), isTrue);
      expect(isGuestBrowsableLocation('/events/604'), isTrue);
      expect(isGuestBrowsableLocation('/u/Funity-F001115'), isTrue);
      expect(isGuestBrowsableLocation('/privacy-policy'), isTrue);
      expect(isGuestBrowsableLocation('/terms'), isTrue);
    });

    test('blocks protected shells and private event subroutes', () {
      expect(isGuestBrowsableLocation('/create'), isFalse);
      expect(isGuestBrowsableLocation('/my-events'), isFalse);
      expect(isGuestBrowsableLocation('/profile'), isFalse);
      expect(isGuestBrowsableLocation('/chats'), isFalse);
      expect(isGuestBrowsableLocation('/friends'), isFalse);
      expect(isGuestBrowsableLocation('/notifications'), isFalse);
      expect(isGuestBrowsableLocation('/favorites'), isFalse);
      expect(isGuestBrowsableLocation('/events/604/chat'), isFalse);
      expect(isGuestBrowsableLocation('/events/604/ticket'), isFalse);
      expect(isGuestBrowsableLocation('/events/604/edit'), isFalse);
      expect(isGuestBrowsableLocation('/events/604/members'), isFalse);
      expect(isGuestBrowsableLocation('/settings/profile'), isFalse);
      expect(isGuestBrowsableLocation('/dms/1'), isFalse);
    });
  });

  group('requireAuth + returnTo', () {
    testWidgets('guest is sent to login with allowlisted returnTo', (tester) async {
      late final GoRouter router;
      router = GoRouter(
        initialLocation: '/events/604',
        routes: [
          GoRoute(
            path: '/events/:id',
            builder: (_, _) => Scaffold(
              body: Builder(
                builder: (context) => TextButton(
                  onPressed: () => requireAuth(
                    context,
                    const Unauthenticated(),
                    returnTo: '/events/604',
                  ),
                  child: const Text('Join'),
                ),
              ),
            ),
          ),
          GoRoute(
            path: '/login',
            builder: (_, state) => Text('LOGIN:${state.uri}'),
          ),
        ],
      );

      await tester.pumpWidget(MaterialApp.router(routerConfig: router));
      await tester.tap(find.text('Join'));
      await tester.pumpAndSettle();

      expect(find.textContaining('LOGIN:'), findsOneWidget);
      expect(
        router.state.uri.toString(),
        loginLocation(returnTo: '/events/604'),
      );
    });

    testWidgets('authenticated requireAuth returns true without navigating', (
      tester,
    ) async {
      const user = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'active',
        locale: 'en',
        emailVerified: true,
        canParticipateFlag: true,
      );
      var navigated = false;
      late final GoRouter router;
      router = GoRouter(
        initialLocation: '/events/1',
        routes: [
          GoRoute(
            path: '/events/:id',
            builder: (_, _) => Scaffold(
              body: Builder(
                builder: (context) => TextButton(
                  onPressed: () {
                    final ok = requireAuth(
                      context,
                      const Authenticated(user),
                      returnTo: '/events/1',
                    );
                    expect(ok, isTrue);
                  },
                  child: const Text('Join'),
                ),
              ),
            ),
          ),
          GoRoute(
            path: '/login',
            builder: (_, _) {
              navigated = true;
              return const Text('LOGIN');
            },
          ),
        ],
      );

      await tester.pumpWidget(MaterialApp.router(routerConfig: router));
      await tester.tap(find.text('Join'));
      await tester.pumpAndSettle();

      expect(navigated, isFalse);
      expect(router.state.uri.path, '/events/1');
    });
  });

  group('post-auth returnTo after protected action', () {
    test('join and create returnTo survive resolvePostAuthLocation', () {
      const active = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'active',
        locale: 'en',
        emailVerified: true,
        canParticipateFlag: true,
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/events/604'),
        '/events/604',
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/create'),
        '/create',
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/u/Funity-F001115'),
        '/u/Funity-F001115',
      );
      expect(
        resolvePostAuthLocation(active, returnTo: '/home'),
        '/home',
      );
    });
  });
}
