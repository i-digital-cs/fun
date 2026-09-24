import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';

/// Renders `GET /api/site` `logo_url` (SVG or raster) with a soft fallback mark.
class SiteBrandMark extends StatelessWidget {
  const SiteBrandMark({
    super.key,
    this.logoUrl,
    this.size = 40,
    this.borderRadius = 13,
    this.fit = BoxFit.contain,
  });

  final String? logoUrl;
  final double size;
  final double borderRadius;
  final BoxFit fit;

  static bool isSvgUrl(String? url) {
    if (url == null || url.isEmpty) return false;
    final path = Uri.tryParse(url)?.path.toLowerCase() ?? url.toLowerCase();
    return path.endsWith('.svg');
  }

  @override
  Widget build(BuildContext context) {
    final url = logoUrl?.trim();
    final fallback = _FallbackMark(size: size, borderRadius: borderRadius);

    if (url == null || url.isEmpty) {
      return fallback;
    }

    return ClipRRect(
      borderRadius: BorderRadius.circular(borderRadius),
      child: SizedBox(
        width: size,
        height: size,
        child: isSvgUrl(url)
            ? SvgPicture.network(
                url,
                width: size,
                height: size,
                fit: fit,
                placeholderBuilder: (_) => fallback,
                errorBuilder: (_, _, _) => fallback,
              )
            : AppNetworkImage(
                url: url,
                logicalWidth: size,
                logicalHeight: size,
                fit: fit,
                error: fallback,
              ),
      ),
    );
  }
}

class _FallbackMark extends StatelessWidget {
  const _FallbackMark({required this.size, required this.borderRadius});

  final double size;
  final double borderRadius;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(borderRadius),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.2),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Icon(
        Icons.celebration_outlined,
        color: AppColors.primary,
        size: size * 0.45,
      ),
    );
  }
}
