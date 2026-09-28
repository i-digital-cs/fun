import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/safe_url.dart';
import 'package:fun_mobile/features/site/presentation/widgets/site_brand_lockup.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Opens the canonical web legal document (single source of truth) in browser.
class LegalWebPage extends ConsumerWidget {
  const LegalWebPage({
    super.key,
    required this.title,
    required this.path,
    required this.blurb,
  });

  final String title;
  final String path;
  final String blurb;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final brand = ref.watch(siteDisplayNameProvider);
    final url = 'https://funnity.com$path';

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(title), backgroundColor: AppColors.canvas),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
        children: [
          Text(
            brand.toUpperCase(),
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              letterSpacing: 1.2,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            title,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.4,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            blurb,
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 14,
              height: 1.5,
            ),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: () => launchSafeExternalUrl(url),
            child: Text(AppLocalizations.of(context).openFullDocument),
          ),
        ],
      ),
    );
  }
}
