import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

EventItem _event({String title = 'Local padel meetup'}) {
  return EventItem(
    id: 42,
    title: title,
    venue: 'Desert Road Courts',
    maxParticipants: 12,
    membersCount: 4,
    costPerPerson: 0,
    startsAt: DateTime(2026, 10, 29, 19, 15),
    category: const EventCategory(id: 1, name: 'Padel'),
    city: const EventCity(id: 1, name: 'Obour'),
    distanceKm: 3.2,
    sex: 'mix',
    locationLabel: 'Desert Road Courts · Obour',
  );
}

Widget _wrap(
  Widget child, {
  double width = 390,
  Locale locale = const Locale('en'),
}) {
  return MediaQuery(
    data: MediaQueryData(size: Size(width, 844)),
    child: MaterialApp(
      locale: locale,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(
        body: SingleChildScrollView(
          child: Padding(padding: const EdgeInsets.all(16), child: child),
        ),
      ),
    ),
  );
}

void main() {
  testWidgets('compact event card fits under ~320px height on phone', (
    tester,
  ) async {
    await tester.pumpWidget(
      _wrap(
        EventCard(
          event: _event(
            title: 'تمرين Padel (بادل) – زوجي with a long bilingual title',
          ),
          onView: () {},
          onFavorite: () {},
        ),
        width: 360,
      ),
    );
    await tester.pump();
    await tester.pump(const Duration(milliseconds: 300));

    final size = tester.getSize(find.byType(EventCard));
    expect(
      size.height,
      lessThan(320),
      reason: 'card should be compact for mobile (was fixed ~360–440)',
    );
    expect(size.height, greaterThan(180));
    expect(tester.takeException(), isNull);
  });

  testWidgets('event card shows title and price chip', (tester) async {
    await tester.pumpWidget(_wrap(EventCard(event: _event(), onView: () {})));
    await tester.pump();
    expect(find.text('Local padel meetup'), findsOneWidget);
    expect(find.byKey(const ValueKey('event-card-price-chip')), findsOneWidget);
  });
}
