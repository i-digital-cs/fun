import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Contract tests for guest browse-first routing (no full ProviderScope shell).
///
/// Full GoRouter + MainShell needs many remote providers; emulator QA covers
/// end-to-end Home/Discover. These tests lock the allowlist + landing policy.
void main() {
  test('guest landing after splash is Home', () {
    expect(guestLandingLocation, '/home');
    expect(isGuestBrowsableLocation(guestLandingLocation), isTrue);
  });

  test('protected actions are not guest-browsable', () {
    for (final path in const [
      '/create',
      '/my-events',
      '/profile',
      '/chats',
      '/friends',
      '/notifications',
      '/favorites',
      '/settings/profile',
      '/settings/location',
      '/events/1/ticket',
      '/events/1/chat',
      '/events/1/edit',
    ]) {
      expect(
        isGuestBrowsableLocation(path),
        isFalse,
        reason: '$path must require auth',
      );
    }
  });

  test('loginLocation preserves create/event/profile returnTo', () {
    expect(
      loginLocation(returnTo: '/create'),
      '/login?returnTo=${Uri.encodeComponent('/create')}',
    );
    expect(
      loginLocation(returnTo: '/events/604'),
      '/login?returnTo=${Uri.encodeComponent('/events/604')}',
    );
    expect(
      loginLocation(returnTo: '/u/Funity-F001115'),
      '/login?returnTo=${Uri.encodeComponent('/u/Funity-F001115')}',
    );
  });

  testWidgets('Arabic guest copy resolves for Home CTAs', (tester) async {
    late AppLocalizations l10n;
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('ar'),
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        supportedLocales: AppLocalizations.supportedLocales,
        home: Builder(
          builder: (context) {
            l10n = AppLocalizations.of(context);
            return const SizedBox.shrink();
          },
        ),
      ),
    );
    expect(l10n.guestGreeting, isNotEmpty);
    expect(l10n.guestGreetingSub, isNotEmpty);
    expect(l10n.guestSignInCta, isNotEmpty);
    expect(l10n.continueBrowsing, isNotEmpty);
    expect(l10n.guestGreeting, isNot(contains('{name}')));
  });
}
