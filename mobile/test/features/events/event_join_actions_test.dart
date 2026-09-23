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
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Join'), findsOneWidget);
    await tester.tap(find.text('Join'));
    expect(joined, isTrue);
  });

  testWidgets('request_pending shows pending banner', (tester) async {
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'request_pending',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Request pending'), findsOneWidget);
  });

  testWidgets('joined shows chat and leave', (tester) async {
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'joined',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Chat'), findsOneWidget);
    expect(find.text('Leave'), findsOneWidget);
  });

  testWidgets('event_full shows full banner', (tester) async {
    await tester.pumpWidget(
      _wrap(
        EventJoinActions(
          joinAction: 'event_full',
          acting: false,
          onJoin: () {},
          onLeave: () {},
          onChat: () {},
        ),
      ),
    );

    expect(find.text('Event full'), findsOneWidget);
  });
}
