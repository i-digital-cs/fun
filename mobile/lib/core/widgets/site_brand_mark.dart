import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:http/http.dart' as http;

/// Bundled official mark — same geometry as `public/branding/funity-mark.svg`
/// and Brand Identity System (icon-only). Used only when `logo_url` is missing
/// or fails to load. In-app brand chrome always prefers `GET /api/site` logo.
const kFunityMarkAsset = 'assets/branding/funity-mark.svg';

/// Renders `GET /api/site` `logo_url` (SVG or raster) exclusively when present.
/// Falls back to the official Funity mark asset — never a generic icon.
class SiteBrandMark extends StatefulWidget {
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
  State<SiteBrandMark> createState() => _SiteBrandMarkState();
}

class _SiteBrandMarkState extends State<SiteBrandMark> {
  static final _svgCache = <String, String>{};

  String? _svgBody;
  bool _failed = false;
  String? _loadingUrl;

  @override
  void initState() {
    super.initState();
    _load();
  }

  @override
  void didUpdateWidget(covariant SiteBrandMark oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.logoUrl != widget.logoUrl) {
      _load();
    }
  }

  Future<void> _load() async {
    final url = widget.logoUrl?.trim();
    if (url == null || url.isEmpty || !SiteBrandMark.isSvgUrl(url)) {
      if (mounted) {
        setState(() {
          _svgBody = null;
          _failed = false;
          _loadingUrl = url;
        });
      }
      return;
    }

    final cached = _svgCache[url];
    if (cached != null) {
      if (mounted) {
        setState(() {
          _svgBody = cached;
          _failed = false;
          _loadingUrl = url;
        });
      }
      return;
    }

    _loadingUrl = url;
    if (mounted) {
      setState(() {
        _svgBody = null;
        _failed = false;
      });
    }

    try {
      final response = await http.get(
        Uri.parse(url),
        headers: const {
          'Accept': 'image/svg+xml,image/*,*/*',
          'User-Agent': 'FunityMobile/1.0',
        },
      );
      if (!mounted || _loadingUrl != url) return;
      if (response.statusCode >= 200 &&
          response.statusCode < 300 &&
          response.body.trim().startsWith('<')) {
        final body = response.body;
        _svgCache[url] = body;
        setState(() {
          _svgBody = body;
          _failed = false;
        });
      } else {
        setState(() => _failed = true);
      }
    } catch (_) {
      if (mounted && _loadingUrl == url) {
        setState(() => _failed = true);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final url = widget.logoUrl?.trim();
    return _BrandShell(
      size: widget.size,
      borderRadius: widget.borderRadius,
      child: _buildChild(url),
    );
  }

  Widget _buildChild(String? url) {
    if (url == null || url.isEmpty) {
      return _OfficialMark(size: widget.size, fit: widget.fit);
    }

    if (SiteBrandMark.isSvgUrl(url)) {
      if (_svgBody != null) {
        return SvgPicture.string(
          _svgBody!,
          width: widget.size,
          height: widget.size,
          fit: widget.fit,
          allowDrawingOutsideViewBox: true,
          placeholderBuilder: (_) =>
              _OfficialMark(size: widget.size, fit: widget.fit),
        );
      }
      if (_failed) {
        return _OfficialMark(size: widget.size, fit: widget.fit);
      }
      return _OfficialMark(size: widget.size, fit: widget.fit);
    }

    return AppNetworkImage(
      url: url,
      logicalWidth: widget.size,
      logicalHeight: widget.size,
      fit: widget.fit,
      error: _OfficialMark(size: widget.size, fit: widget.fit),
    );
  }
}

class _BrandShell extends StatelessWidget {
  const _BrandShell({
    required this.size,
    required this.borderRadius,
    required this.child,
  });

  final double size;
  final double borderRadius;
  final Widget child;

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
            color: AppColors.primary.withValues(alpha: 0.18),
            blurRadius: 12,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      padding: EdgeInsets.all(size * 0.08),
      child: child,
    );
  }
}

class _OfficialMark extends StatelessWidget {
  const _OfficialMark({required this.size, required this.fit});

  final double size;
  final BoxFit fit;

  @override
  Widget build(BuildContext context) {
    return SvgPicture.asset(
      kFunityMarkAsset,
      width: size,
      height: size,
      fit: fit,
      allowDrawingOutsideViewBox: true,
    );
  }
}
