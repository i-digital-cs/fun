import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';

void main() {
  testWidgets('Arabic chat bubble preview uses bidi RTL for Arabic body', (
    tester,
  ) async {
    const arabic = 'مرحبا كيف حالك';
    await tester.pumpWidget(
      MaterialApp(
        locale: const Locale('ar'),
        home: Directionality(
          textDirection: TextDirection.rtl,
          child: Scaffold(
            body: Align(
              alignment: AlignmentDirectional.centerEnd,
              child: Container(
                key: const Key('chat-bubble'),
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.primary,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  arabic,
                  textDirection: bidiTextDirection(arabic),
                  style: const TextStyle(color: Colors.white),
                ),
              ),
            ),
          ),
        ),
      ),
    );

    expect(find.text(arabic), findsOneWidget);
    expect(bidiTextDirection(arabic), TextDirection.rtl);
    expect(find.byKey(const Key('chat-bubble')), findsOneWidget);
    final text = tester.widget<Text>(find.text(arabic));
    expect(text.textDirection, TextDirection.rtl);
  });
}
