import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/auth/presentation/otp_resend_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/otp_resend_section.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

Widget _wrap(ProviderContainer container, Locale locale, Widget child) {
  return UncontrolledProviderScope(
    container: container,
    child: MaterialApp(
      locale: locale,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        ...GlobalMaterialLocalizations.delegates,
      ],
      supportedLocales: AppLocalizations.supportedLocales,
      home: Scaffold(body: child),
    ),
  );
}

void main() {
  testWidgets('resend section stays visible during cooldown and after', (
    tester,
  ) async {
    final container = ProviderContainer();

    container.read(emailVerifyResendProvider.notifier).applyRetryAfter(45);

    await tester.pumpWidget(
      _wrap(
        container,
        const Locale('en'),
        OtpResendSection(tickerKey: 'email', onResend: () async {}),
      ),
    );
    await tester.pump(); // post-frame syncFromStore

    expect(find.textContaining('Resend code in'), findsOneWidget);
    expect(find.text("Didn't receive it?"), findsOneWidget);

    final button = tester.widget<TextButton>(find.byType(TextButton));
    expect(button.onPressed, isNull);

    container.read(emailVerifyResendProvider.notifier).clear();
    await tester.pump();
    expect(find.text('Resend code'), findsOneWidget);

    await tester.pumpWidget(const SizedBox.shrink());
    container.dispose();
  });

  testWidgets('Arabic RTL shows localized countdown copy', (tester) async {
    final container = ProviderContainer();
    container.read(emailVerifyResendProvider.notifier).applyRetryAfter(30);

    await tester.pumpWidget(
      _wrap(
        container,
        const Locale('ar'),
        const Directionality(
          textDirection: TextDirection.rtl,
          child: OtpResendSection(tickerKey: 'email', onResend: _noop),
        ),
      ),
    );
    await tester.pump();

    expect(find.textContaining('إعادة الإرسال خلال'), findsOneWidget);

    await tester.pumpWidget(const SizedBox.shrink());
    container.dispose();
  });

  test('countdown formatter pads minutes and seconds', () {
    expect(formatOtpCountdown(45), '00:45');
    expect(formatOtpCountdown(75), '01:15');
    expect(formatOtpCountdown(0), '00:00');
  });

  test('OtpResendController restores remaining after navigation', () {
    final controller = OtpResendController();
    controller.applyRetryAfter(60);
    final first = controller.state.remainingSeconds();
    expect(first, inInclusiveRange(59, 60));

    // Simulate leaving and returning: same store still has availableAt.
    final second = controller.state.remainingSeconds();
    expect(second, lessThanOrEqualTo(first));
    expect(second, greaterThan(0));
  });
}

Future<void> _noop() async {}
