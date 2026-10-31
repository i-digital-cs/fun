import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/app/theme/app_theme.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('AppTheme typography', () {
    test('bundled Arabic family id is Tajawal', () {
      expect(AppTheme.arabicFontFamily, 'Tajawal');
    });

    test('Arabic locale uses bundled Tajawal centrally', () {
      final theme = AppTheme.light(locale: const Locale('ar'));

      expect(theme.textTheme.bodyMedium?.fontFamily, AppTheme.arabicFontFamily);
      expect(theme.textTheme.titleLarge?.fontFamily, AppTheme.arabicFontFamily);
      expect(theme.textTheme.labelLarge?.fontFamily, AppTheme.arabicFontFamily);
      expect(theme.primaryTextTheme.bodyLarge?.fontFamily, AppTheme.arabicFontFamily);
      expect(
        theme.appBarTheme.titleTextStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.dialogTheme.titleTextStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.elevatedButtonTheme.style?.textStyle?.resolve({})?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.filledButtonTheme.style?.textStyle?.resolve({})?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.textButtonTheme.style?.textStyle?.resolve({})?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.inputDecorationTheme.labelStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.navigationBarTheme.labelTextStyle?.resolve({})?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.snackBarTheme.contentTextStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.tabBarTheme.labelStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
      expect(
        theme.listTileTheme.titleTextStyle?.fontFamily,
        AppTheme.arabicFontFamily,
      );
    });

    testWidgets('Arabic theme paints sample UI without overflow at large text scale', (
      tester,
    ) async {
      tester.view.physicalSize = const Size(1080, 2400);
      tester.view.devicePixelRatio = 3;
      addTearDown(tester.view.resetPhysicalSize);
      addTearDown(tester.view.resetDevicePixelRatio);

      final theme = AppTheme.light(locale: const Locale('ar'));

      await tester.pumpWidget(
        MediaQuery(
          data: const MediaQueryData(textScaler: TextScaler.linear(1.6)),
          child: MaterialApp(
            theme: theme,
            locale: const Locale('ar'),
            builder: (context, child) => Directionality(
              textDirection: TextDirection.rtl,
              child: child!,
            ),
            home: Scaffold(
              appBar: AppBar(title: const Text('اكتشف الفعاليات')),
              body: ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  Text(
                    'مرحباً بك في فنيتي — منصة الأنشطة والمجتمعات في مصر',
                    style: theme.textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 12),
                  Text(
                    'وصف طويل جداً للتحقق من التفاف النص العربي وارتفاع السطر دون قص الحروف أو تجاوز الحدود في البطاقات والحوارات.',
                    style: theme.textTheme.bodyMedium,
                  ),
                  const SizedBox(height: 16),
                  FilledButton(
                    onPressed: () {},
                    child: const Text('إنشاء فعالية'),
                  ),
                  TextButton(
                    onPressed: () {},
                    child: const Text('الإعدادات'),
                  ),
                ],
              ),
            ),
          ),
        ),
      );
      await tester.pump();

      expect(tester.takeException(), isNull);
      expect(find.textContaining('فنيتي'), findsOneWidget);

      final body = tester.widget<Text>(find.textContaining('وصف طويل'));
      expect(
        body.style?.fontFamily ?? theme.textTheme.bodyMedium?.fontFamily,
        AppTheme.arabicFontFamily,
      );
    });
  });
}
