import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/presentation/event_detail_page.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

Widget _wrap(Widget child) {
  return MaterialApp(
    localizationsDelegates: AppLocalizations.localizationsDelegates,
    supportedLocales: AppLocalizations.supportedLocales,
    locale: const Locale('en'),
    home: Scaffold(body: child),
  );
}

void main() {
  testWidgets('request_to_join shows Join CTA', (tester) async {
    var joined = false;
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'request_to_join',
          acting: false,
          onJoin: () => joined = true,
          onLeave: () {},
          onCancelJoin: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Request to join'), findsOneWidget);
    await tester.tap(find.text('Request to join'));
    expect(joined, isTrue);
  });

  testWidgets('request_pending shows pending banner and cancel join', (
    tester,
  ) async {
    var cancelled = false;
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'request_pending',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onCancelJoin: () => cancelled = true,
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Request pending'), findsOneWidget);
    expect(find.text('Cancel join'), findsOneWidget);
    await tester.tap(find.text('Cancel join'));
    expect(cancelled, isTrue);
  });

  testWidgets('joined shows chat and leave', (tester) async {
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'joined',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onCancelJoin: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Chat'), findsOneWidget);
    expect(find.text('Leave event'), findsOneWidget);
  });

  testWidgets('event_full shows full banner', (tester) async {
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'event_full',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onCancelJoin: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Event full'), findsOneWidget);
  });
}
