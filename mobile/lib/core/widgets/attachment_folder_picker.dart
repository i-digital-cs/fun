import 'dart:io';

import 'package:flutter/material.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Blade `x-ui.attachment-folder` — open folder + status + thumb + count.
class AttachmentFolderPicker extends StatelessWidget {
  const AttachmentFolderPicker({
    super.key,
    required this.label,
    required this.onPick,
    this.path,
    this.networkUrl,
    this.hint,
    this.onClear,
  });

  final String label;
  final VoidCallback onPick;
  final String? path;
  final String? networkUrl;
  final String? hint;
  final VoidCallback? onClear;

  bool get _hasFile =>
      (path != null && path!.isNotEmpty) ||
      (networkUrl != null && networkUrl!.isNotEmpty);

  String? get _filename {
    if (path != null && path!.isNotEmpty) {
      return path!.split(RegExp(r'[\\/]')).last;
    }
    if (networkUrl != null && networkUrl!.isNotEmpty) {
      return networkUrl!.split(RegExp(r'[\\/?#]')).lastWhere(
            (s) => s.isNotEmpty,
            orElse: () => 'image',
          );
    }
    return null;
  }

  String? get _ext {
    final name = _filename;
    if (name == null || !name.contains('.')) return null;
    return name.split('.').last.toUpperCase();
  }

  String? get _sizeLabel {
    if (path == null || path!.isEmpty) return null;
    try {
      final bytes = File(path!).lengthSync();
      if (bytes < 1024) return '$bytes B';
      if (bytes < 1024 * 1024) {
        return '${(bytes / 1024).toStringAsFixed(0)} KB';
      }
      return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
    } catch (_) {
      return null;
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final open = _hasFile;
    final tag = [
      ?_ext,
      ?_sizeLabel,
    ].join(' · ');

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          label,
          style: const TextStyle(
            fontWeight: FontWeight.w700,
            fontSize: 13,
            color: AppColors.text,
          ),
        ),
        const SizedBox(height: 8),
        InkWell(
          onTap: onPick,
          borderRadius: BorderRadius.circular(18),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 280),
            curve: Curves.easeOutCubic,
            padding: const EdgeInsets.fromLTRB(14, 14, 14, 12),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(18),
              border: Border.all(
                color: open
                    ? AppColors.primary.withValues(alpha: 0.35)
                    : AppColors.borderSoft,
              ),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    children: [
                      SizedBox(
                        height: open ? 88 : 72,
                        child: Stack(
                          alignment: Alignment.bottomCenter,
                          clipBehavior: Clip.none,
                          children: [
                            // Folder back
                            Positioned(
                              left: 8,
                              right: 8,
                              bottom: open ? 28 : 18,
                              child: CustomPaint(
                                size: const Size(double.infinity, 48),
                                painter: _FolderBackPainter(
                                  color: open
                                      ? const Color(0xFFFBBF24)
                                      : const Color(0xFFF59E0B),
                                ),
                              ),
                            ),
                            // File card peeking out when open
                            if (open)
                              Positioned(
                                left: 28,
                                right: 28,
                                bottom: 36,
                                child: Transform.rotate(
                                  angle: -0.08,
                                  child: Container(
                                    height: 44,
                                    decoration: BoxDecoration(
                                      color: Colors.white,
                                      borderRadius: BorderRadius.circular(8),
                                      border: Border.all(
                                        color: AppColors.borderSoft,
                                      ),
                                      boxShadow: [
                                        BoxShadow(
                                          color: Colors.black
                                              .withValues(alpha: 0.08),
                                          blurRadius: 8,
                                          offset: const Offset(0, 3),
                                        ),
                                      ],
                                    ),
                                    padding: const EdgeInsets.symmetric(
                                      horizontal: 8,
                                    ),
                                    child: Row(
                                      children: [
                                        Icon(
                                          Icons.image_outlined,
                                          size: 16,
                                          color: AppColors.primary
                                              .withValues(alpha: 0.8),
                                        ),
                                        const SizedBox(width: 6),
                                        Expanded(
                                          child: Text(
                                            _filename ?? l10n.selected,
                                            maxLines: 1,
                                            overflow: TextOverflow.ellipsis,
                                            style: const TextStyle(
                                              fontSize: 11,
                                              fontWeight: FontWeight.w700,
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ),
                              )
                            else
                              Positioned(
                                left: 36,
                                right: 36,
                                bottom: 32,
                                child: Container(
                                  height: 36,
                                  alignment: Alignment.center,
                                  decoration: BoxDecoration(
                                    color: Colors.white.withValues(alpha: 0.9),
                                    borderRadius: BorderRadius.circular(8),
                                    border: Border.all(
                                      color: AppColors.borderSoft,
                                    ),
                                  ),
                                  child: Text(
                                    l10n.attachmentAdd,
                                    style: const TextStyle(
                                      fontSize: 11,
                                      fontWeight: FontWeight.w700,
                                      color: AppColors.muted,
                                    ),
                                  ),
                                ),
                              ),
                            // Folder front
                            Positioned(
                              left: 4,
                              right: 4,
                              bottom: 0,
                              child: Container(
                                height: open ? 34 : 38,
                                decoration: BoxDecoration(
                                  color: open
                                      ? const Color(0xFFF59E0B)
                                      : const Color(0xFFD97706),
                                  borderRadius: const BorderRadius.only(
                                    bottomLeft: Radius.circular(10),
                                    bottomRight: Radius.circular(10),
                                    topLeft: Radius.circular(4),
                                    topRight: Radius.circular(4),
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: const Color(0xFFF59E0B)
                                          .withValues(alpha: 0.35),
                                      blurRadius: 10,
                                      offset: const Offset(0, 4),
                                    ),
                                  ],
                                ),
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 10,
                                ),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 7,
                                      height: 7,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        color: open
                                            ? const Color(0xFF34D399)
                                            : Colors.white70,
                                      ),
                                    ),
                                    const SizedBox(width: 6),
                                    Expanded(
                                      child: Text(
                                        l10n.attachmentFiles,
                                        style: const TextStyle(
                                          color: Colors.white,
                                          fontSize: 10,
                                          fontWeight: FontWeight.w800,
                                        ),
                                      ),
                                    ),
                                    Text(
                                      open ? '01' : '00',
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 12,
                                        fontWeight: FontWeight.w900,
                                        fontFeatures: [
                                          FontFeature.tabularFigures(),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        hint ?? l10n.attachmentHint,
                        textAlign: TextAlign.center,
                        style: TextStyle(
                          fontSize: 11,
                          color: AppColors.muted.withValues(alpha: 0.9),
                        ),
                      ),
                    ],
                  ),
                ),
                if (open) ...[
                  const SizedBox(width: 12),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(12),
                    child: SizedBox(
                      width: 72,
                      height: 72,
                      child: path != null && path!.isNotEmpty
                          ? Image.file(File(path!), fit: BoxFit.cover)
                          : networkUrl != null
                              ? Image.network(networkUrl!, fit: BoxFit.cover)
                              : Container(
                                  color: AppColors.primarySoft,
                                  child: const Icon(
                                    Icons.image_outlined,
                                    color: AppColors.primary,
                                  ),
                                ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
        if (open) ...[
          const SizedBox(height: 8),
          Row(
            children: [
              Expanded(
                child: Text(
                  tag.isEmpty
                      ? (_filename ?? '')
                      : '${_filename ?? ''} ($tag)',
                  style: const TextStyle(
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                    color: AppColors.text,
                  ),
                ),
              ),
              if (onClear != null)
                TextButton(
                  onPressed: onClear,
                  child: Text(l10n.attachmentClear),
                ),
            ],
          ),
        ],
      ],
    );
  }
}

class _FolderBackPainter extends CustomPainter {
  _FolderBackPainter({required this.color});

  final Color color;

  @override
  void paint(Canvas canvas, Size size) {
    final path = Path()
      ..moveTo(0, 10)
      ..lineTo(0, 4)
      ..quadraticBezierTo(0, 0, 4, 0)
      ..lineTo(size.width * 0.32, 0)
      ..lineTo(size.width * 0.4, 8)
      ..lineTo(size.width - 4, 8)
      ..quadraticBezierTo(size.width, 8, size.width, 12)
      ..lineTo(size.width, size.height)
      ..lineTo(0, size.height)
      ..close();
    canvas.drawPath(path, Paint()..color = color);
  }

  @override
  bool shouldRepaint(covariant _FolderBackPainter oldDelegate) =>
      oldDelegate.color != color;
}
