import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/discover/presentation/discover_page.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

void main() {
  testWidgets('DiscoverFilterPanel shows category/city filters without duplicate search', (
    tester,
  ) async {
    final search = TextEditingController();
    addTearDown(search.dispose);

    await tester.pumpWidget(
      MaterialApp(
        localizationsDelegates: AppLocalizations.localizationsDelegates,
        supportedLocales: AppLocalizations.supportedLocales,
        locale: const Locale('en'),
        home: Scaffold(
          body: SingleChildScrollView(
            child: DiscoverFilterPanel(
              searchController: search,
              catalogLoaded: true,
              cities: const [CityItem(id: 1, name: 'Manama')],
              categories: const [CategoryItem(id: 2, name: 'Outdoors')],
              distances: const [10, 25, 50],
              allCities: true,
              cityId: null,
              categoryId: null,
              distanceKm: 25,
              when: null,
              onAllCitiesChanged: (_) {},
              onCityChanged: (_) {},
              onCategoryChanged: (_) {},
              onDistanceChanged: (_) {},
              onWhenChanged: (_) {},
              onApply: () {},
              onClear: () {},
            ),
          ),
        ),
      ),
    );

    await tester.pumpAndSettle();
    expect(find.byType(DiscoverFilterPanel), findsOneWidget);
    // Search lives in Discover chrome only — panel must not duplicate it.
    expect(find.byIcon(Icons.search), findsNothing);
    expect(find.text('Category'), findsOneWidget);
    expect(find.text('City'), findsOneWidget);
  });
}
