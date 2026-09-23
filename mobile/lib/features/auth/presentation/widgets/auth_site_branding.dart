import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';

/// Site logo + display name for auth glass cards.
class AuthSiteBranding extends ConsumerWidget {
  const AuthSiteBranding({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final branding = ref.watch(siteBrandingProvider);

    return branding.when(
      data: (site) => _BrandRow(
        displayName: site.displayName,
        logoUrl: site.logoUrl,
      ),
      loading: () => const _BrandRow(displayName: 'Fun'),
      error: (_, _) => const _BrandRow(displayName: 'Fun'),
    );
  }
}

class _BrandRow extends StatelessWidget {
  const _BrandRow({required this.displayName, this.logoUrl});

  final String displayName;
  final String? logoUrl;

  @override
  Widget build(BuildContext context) {
    final hasLogo = logoUrl != null && logoUrl!.isNotEmpty;

    return Column(
      children: [
        if (hasLogo) ...[
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: SizedBox(
              width: 56,
              height: 56,
              child: AppNetworkImage(
                url: logoUrl!,
                logicalWidth: 56,
                logicalHeight: 56,
                fit: BoxFit.contain,
                error: const ColoredBox(
                  color: AppColors.primarySoft,
                  child: Icon(Icons.celebration_outlined, color: AppColors.primary),
                ),
              ),
            ),
          ),
          const SizedBox(height: 12),
        ],
        Text(
          displayName,
          textAlign: TextAlign.center,
          style: const TextStyle(
            fontSize: 20,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.3,
            color: AppColors.text,
          ),
        ),
        const SizedBox(height: 20),
      ],
    );
  }
}
