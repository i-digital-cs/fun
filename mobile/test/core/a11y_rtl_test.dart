import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/home/presentation/widgets/location_shortcut_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

void main() {
  testWidgets('A11Y-RTL-001 Arabic locale uses RTL text direction', (
    tester,
  ) async {
    late TextDirection direction;
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('ar'),
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        home: Builder(
          builder: (context) {
            direction = Directionality.of(context);
            return const SizedBox.shrink();
          },
        ),
      ),
    );
    expect(direction, TextDirection.rtl);
  });

  testWidgets('A11Y-RTL-001 English locale uses LTR text direction', (
    tester,
  ) async {
    late TextDirection direction;
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('en'),
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        home: Builder(
          builder: (context) {
            direction = Directionality.of(context);
            return const SizedBox.shrink();
          },
        ),
      ),
    );
    expect(direction, TextDirection.ltr);
  });

  testWidgets('A11Y-RTL-001 location shortcut exposes semantics button', (
    tester,
  ) async {
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('en'),
        supportedLocales: AppLocalizations.supportedLocales,
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        home: Scaffold(
          body: LocationShortcutCard(
            cityName: null,
            distanceKm: 25,
            onTap: () {},
          ),
        ),
      ),
    );

    expect(
      tester.getSemantics(find.byType(LocationShortcutCard)),
      matchesSemantics(
        isButton: true,
        label: 'Set your location',
        hasTapAction: true,
      ),
    );
  });
}
