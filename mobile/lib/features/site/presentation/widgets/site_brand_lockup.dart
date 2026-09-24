import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/site_brand_mark.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';

/// Logo + display name from `GET /api/site` (Blade `site_logo_url` + `site_display_name`).
class SiteBrandLockup extends ConsumerWidget {
  const SiteBrandLockup({
    super.key,
    this.markSize = 36,
    this.maxNameWidth = 140,
    this.showName = true,
    this.onTap,
    this.dense = false,
    this.onDark = false,
  });

  final double markSize;
  final double maxNameWidth;
  final bool showName;
  final VoidCallback? onTap;
  final bool dense;
  /// White name for use on dark / hero surfaces.
  final bool onDark;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final brand = ref.watch(siteBrandingProvider).maybeWhen(
      data: (s) => s,
      orElse: () => SiteBranding.fallback,
    );

    final row = Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        SiteBrandMark(
          logoUrl: brand.logoUrl,
          size: markSize,
          borderRadius: markSize * 0.32,
        ),
        if (showName) ...[
          SizedBox(width: dense ? 8 : 10),
          ConstrainedBox(
            constraints: BoxConstraints(maxWidth: maxNameWidth),
            child: Text(
              brand.displayName,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: dense ? 14 : 16,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.3,
                color: onDark ? Colors.white : AppColors.text,
              ),
            ),
          ),
        ],
      ],
    );

    if (onTap == null) return row;
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 2, vertical: 2),
        child: row,
      ),
    );
  }
}

/// Resolved site display name for copy that used to hardcode “Fun”.
final siteDisplayNameProvider = Provider<String>((ref) {
  return ref.watch(siteBrandingProvider).maybeWhen(
    data: (s) => s.displayName,
    orElse: () => SiteBranding.fallback.displayName,
  );
});
