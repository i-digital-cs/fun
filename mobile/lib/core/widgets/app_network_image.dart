import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';

/// Bounded [CachedNetworkImage] so lists do not decode full-resolution bitmaps.
class AppNetworkImage extends StatelessWidget {
  const AppNetworkImage({
    super.key,
    required this.url,
    this.fit = BoxFit.cover,
    this.logicalWidth,
    this.logicalHeight,
    this.maxCacheWidth = 900,
    this.maxCacheHeight = 900,
    this.placeholder,
    this.error,
  });

  final String url;
  final BoxFit fit;

  /// Layout size used to size the memory cache (device pixels).
  final double? logicalWidth;
  final double? logicalHeight;
  final int maxCacheWidth;
  final int maxCacheHeight;
  final Widget? placeholder;
  final Widget? error;

  @override
  Widget build(BuildContext context) {
    final dpr = MediaQuery.devicePixelRatioOf(context);
    final cacheW = logicalWidth == null
        ? null
        : (logicalWidth! * dpr).round().clamp(1, maxCacheWidth);
    final cacheH = logicalHeight == null
        ? null
        : (logicalHeight! * dpr).round().clamp(1, maxCacheHeight);

    return CachedNetworkImage(
      imageUrl: url,
      fit: fit,
      memCacheWidth: cacheW,
      memCacheHeight: cacheH,
      fadeInDuration: const Duration(milliseconds: 150),
      placeholder: (_, _) =>
          placeholder ?? const ColoredBox(color: AppColors.primarySoft),
      errorWidget: (_, _, _) =>
          error ??
          const ColoredBox(
            color: AppColors.primarySoft,
            child: Icon(Icons.image_outlined, color: AppColors.primary),
          ),
    );
  }
}
