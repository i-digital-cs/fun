import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class ProfilePage extends ConsumerWidget {
  const ProfilePage({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final locale = ref.watch(localeCodeProvider);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.navProfile)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          if (user != null) ...[
            CircleAvatar(
              radius: 36,
              backgroundColor: AppColors.primary,
              child: Text(
                user.initials,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                  fontSize: 20,
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              user.name,
              style: const TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.w800,
              ),
            ),
            Text(
              user.email,
              style: const TextStyle(color: AppColors.muted),
            ),
            if (user.publicId != null) ...[
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                padding: const EdgeInsets.all(14),
                decoration: BoxDecoration(
                  color: AppColors.primary.withValues(alpha: 0.08),
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.primary.withValues(alpha: 0.2)),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.yourPublicId,
                      style: const TextStyle(
                        color: AppColors.primary,
                        fontWeight: FontWeight.w700,
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      user.publicId!,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 18,
                        fontFamily: 'monospace',
                      ),
                    ),
                    const SizedBox(height: 10),
                    OutlinedButton(
                      onPressed: () => context.push('/u/${user.publicId}'),
                      child: Text(l10n.openPublicProfile),
                    ),
                  ],
                ),
              ),
            ],
            if (user.cityName != null) ...[
              const SizedBox(height: 4),
              Text(
                '${user.cityName} · ${l10n.withinKm(user.distanceKm ?? 10)}',
                style: const TextStyle(color: AppColors.muted, fontSize: 13),
              ),
            ],
            const SizedBox(height: 24),
          ],
          ProfileLinkTile(
            icon: Icons.location_on_outlined,
            title: l10n.location,
            onTap: () => context.push('/settings/location'),
          ),
          if (user?.needsIdentity == true)
            ProfileLinkTile(
              icon: Icons.badge_outlined,
              title: l10n.identity,
              subtitle: l10n.verifyIdentity,
              onTap: () => context.push('/identity'),
            ),
          ProfileLinkTile(
            icon: Icons.block_outlined,
            title: l10n.blocks,
            onTap: () => context.push('/blocks'),
          ),
          ProfileLinkTile(
            icon: Icons.verified_user_outlined,
            title: l10n.safetyGuidelines,
            onTap: () => context.push('/safety/guidelines'),
          ),
          ProfileLinkTile(
            icon: Icons.flag_outlined,
            title: l10n.report,
            onTap: () => context.push('/report'),
          ),
          const SizedBox(height: 24),
          ListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(l10n.language),
            trailing: SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'en', label: Text('EN')),
                ButtonSegment(value: 'ar', label: Text('ع')),
              ],
              selected: {locale},
              onSelectionChanged: (values) async {
                final next = values.first;
                ref.read(localeCodeProvider.notifier).state = next;
                await ref
                    .read(sharedPreferencesProvider)
                    .setString('locale', next);
                ref.read(apiClientProvider).setLocale(next);
              },
            ),
          ),
          const SizedBox(height: 24),
          GradientButton.indigo(
            label: l10n.logout,
            onPressed: () async {
              await ref.read(authControllerProvider.notifier).logout();
              if (context.mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }
}

class ProfileLinkTile extends StatelessWidget {
  const ProfileLinkTile({
    super.key,
    required this.icon,
    required this.title,
    required this.onTap,
    this.subtitle,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.borderSoft),
      ),
      child: ListTile(
        leading: Icon(icon, color: AppColors.primary),
        title: Text(title, style: const TextStyle(fontWeight: FontWeight.w600)),
        subtitle: subtitle != null ? Text(subtitle!) : null,
        trailing: const Icon(Icons.chevron_right, color: AppColors.muted),
        onTap: onTap,
      ),
    );
  }
}
