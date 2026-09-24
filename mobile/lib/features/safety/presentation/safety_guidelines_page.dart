import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/site/presentation/widgets/site_brand_lockup.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Mirrors Blade `safety/guidelines` — identity / report / block + CTAs.
class SafetyGuidelinesPage extends ConsumerWidget {
  const SafetyGuidelinesPage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final canParticipate = auth is Authenticated && auth.user.canParticipate;
    final brandName = ref.watch(siteDisplayNameProvider);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.safetyGuidelines),
        backgroundColor: AppColors.canvas,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
        children: [
          Text(
            brandName.toUpperCase(),
            style: const TextStyle(
              fontSize: 12,
              fontWeight: FontWeight.w700,
              letterSpacing: 1.2,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            l10n.safetyGuidelines,
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.4,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            l10n.guidelinesIntro,
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 14,
              height: 1.45,
            ),
          ),
          const SizedBox(height: 20),
          _GuidelineCard(
            title: l10n.guidelineIdentityTitle,
            body: l10n.guidelineIdentityBody,
            action: canParticipate
                ? null
                : (auth is Authenticated && auth.user.needsIdentity
                      ? TextButton(
                          onPressed: () => context.push('/identity'),
                          child: Text(l10n.verifyIdentity),
                        )
                      : null),
          ),
          const SizedBox(height: 12),
          _GuidelineCard(
            title: l10n.guidelineReportTitle,
            body: l10n.guidelineReportBody,
            action: canParticipate
                ? Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: GradientButton(
                      label: l10n.report,
                      onPressed: () => context.push('/report'),
                    ),
                  )
                : null,
          ),
          const SizedBox(height: 12),
          _GuidelineCard(
            title: l10n.guidelineBlockTitle,
            body: l10n.guidelineBlockBody,
            action: canParticipate
                ? Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: OutlinedButton(
                      onPressed: () => context.push('/blocks'),
                      style: OutlinedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                      ),
                      child: Text(l10n.blockedUsers),
                    ),
                  )
                : null,
          ),
          const SizedBox(height: 20),
          Text(
            l10n.guidelinesDisclaimer,
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 13,
              height: 1.45,
            ),
          ),
        ],
      ),
    );
  }
}

class _GuidelineCard extends StatelessWidget {
  const _GuidelineCard({required this.title, required this.body, this.action});

  final String title;
  final String body;
  final Widget? action;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.borderSoft),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 8),
          Text(
            body,
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 13,
              height: 1.5,
            ),
          ),
          ?action,
        ],
      ),
    );
  }
}
