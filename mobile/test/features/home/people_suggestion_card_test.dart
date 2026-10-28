import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';
import 'package:fun_mobile/features/home/presentation/widgets/people_suggestions_section.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

FriendUser _user({
  String name = 'Sara Ahmed',
  String? city = 'Cairo',
  FriendshipState? friendship,
}) {
  return FriendUser(
    id: 1,
    publicId: 'FUN-0001',
    name: name,
    initials: 'SA',
    cityName: city,
    friendship: friendship ??
        const FriendshipState(
          status: 'none',
          canRequest: true,
          canMessage: false,
        ),
  );
}

Widget _wrap(
  Widget child, {
  Locale locale = const Locale('en'),
  double width = 390,
  double textScale = 1,
}) {
  return MediaQuery(
    data: MediaQueryData(
      size: Size(width, 800),
      textScaler: TextScaler.linear(textScale),
    ),
    child: MaterialApp(
      locale: locale,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(
        body: Directionality(
          textDirection:
              locale.languageCode == 'ar' ? TextDirection.rtl : TextDirection.ltr,
          child: Center(child: child),
        ),
      ),
    ),
  );
}

void main() {
  testWidgets('people card lays out without overflow at phone widths', (
    tester,
  ) async {
    for (final width in [320.0, 360.0, 390.0, 412.0, 430.0]) {
      await tester.pumpWidget(
        _wrap(
          PeopleSuggestionCard(
            user: _user(
              name: 'Very Long Display Name That Should Ellipsize Nicely',
            ),
            onProfile: () {},
            onAdd: () {},
          ),
          width: width,
        ),
      );
      await tester.pump();
      expect(tester.takeException(), isNull);
      expect(find.byType(PeopleSuggestionCard), findsOneWidget);
      final size = tester.getSize(find.byType(PeopleSuggestionCard));
      expect(size.width, PeopleSuggestionCard.cardWidth);
      expect(size.height, PeopleSuggestionCard.cardHeight);
      final overflow = find.byWidgetPredicate(
        (w) =>
            w is Text &&
            (w.data?.contains('OVERFLOWED') ?? false),
      );
      expect(overflow, findsNothing);
    }
  });

  testWidgets('people cards share uniform dimensions in a carousel row', (
    tester,
  ) async {
    await tester.pumpWidget(
      _wrap(
        SizedBox(
          height: PeopleSuggestionCard.cardHeight,
          child: ListView(
            scrollDirection: Axis.horizontal,
            children: [
              PeopleSuggestionCard(
                user: _user(name: 'Short', city: null),
                onProfile: () {},
                onAdd: () {},
              ),
              const SizedBox(width: 10),
              PeopleSuggestionCard(
                user: _user(
                  name: 'A Much Longer Name That Wraps Two Lines',
                  city: '6th of October',
                ),
                onProfile: () {},
                onAdd: () {},
              ),
            ],
          ),
        ),
        width: 320,
      ),
    );
    await tester.pump();
    expect(tester.takeException(), isNull);
    final cards = find.byType(PeopleSuggestionCard);
    expect(cards, findsNWidgets(2));
    final a = tester.getSize(cards.at(0));
    final b = tester.getSize(cards.at(1));
    expect(a, b);
    expect(a.height, PeopleSuggestionCard.cardHeight);
  });

  testWidgets('people card handles large Arabic text scale', (tester) async {
    await tester.pumpWidget(
      _wrap(
        PeopleSuggestionCard(
          user: _user(name: 'محمد أحمد الطويل جداً للاختبار'),
          onProfile: () {},
          onAdd: () {},
        ),
        locale: const Locale('ar'),
        width: 360,
        textScale: 1.3,
      ),
    );
    await tester.pump();
    expect(tester.takeException(), isNull);
  });

  testWidgets('pending state shows request sent instead of add', (tester) async {
    await tester.pumpWidget(
      _wrap(
        PeopleSuggestionCard(
          user: _user(
            friendship: const FriendshipState(
              status: 'pending',
              direction: 'outgoing',
              canRequest: false,
            ),
          ),
          onProfile: () {},
        ),
      ),
    );
    await tester.pump();
    expect(find.text('Request sent'), findsOneWidget);
    expect(find.text('Profile'), findsOneWidget);
  });

  testWidgets('missing photo falls back to initials', (tester) async {
    await tester.pumpWidget(
      _wrap(
        PeopleSuggestionCard(
          user: _user(),
          onProfile: () {},
          onAdd: () {},
        ),
      ),
    );
    await tester.pump();
    expect(find.text('SA'), findsOneWidget);
  });
}
