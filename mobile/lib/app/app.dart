import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/router.dart';
import 'package:fun_mobile/app/theme/app_theme.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

class FunApp extends ConsumerWidget {
  const FunApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final localeCode = ref.watch(localeCodeProvider);
    final locale = Locale(localeCode);

    return MaterialApp.router(
      title: 'Fun',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.light(locale: locale),
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: AppLocalizations.localizationsDelegates,
      routerConfig: router,
    );
  }
}
