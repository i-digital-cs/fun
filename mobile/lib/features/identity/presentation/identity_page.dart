import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/identity/domain/identity_status.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

class IdentityPage extends ConsumerStatefulWidget {
  const IdentityPage({super.key});

  @override
  ConsumerState<IdentityPage> createState() => _IdentityPageState();
}

class _IdentityPageState extends ConsumerState<IdentityPage> {
  IdentityStatus? _status;
  String? _error;
  bool _loading = true;
  bool _uploading = false;

  String? _idFrontPath;
  String? _idBackPath;
  String? _selfiePath;

  final Map<int, Uint8List> _thumbBytes = {};
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await ref.read(identityRemoteSourceProvider).fetch();
    if (!mounted) return;
    if (result.failure != null) {
      setState(() {
        _loading = false;
        _error = result.failure!.message;
      });
      return;
    }
    final status = result.data!;
    setState(() {
      _status = status;
      _loading = false;
    });
    await _loadThumbs(status);
  }

  Future<void> _loadThumbs(IdentityStatus status) async {
    final remote = ref.read(identityRemoteSourceProvider);
    for (final doc in status.documents) {
      if (!doc.uploaded || doc.id == null) continue;
      if (_thumbBytes.containsKey(doc.id)) continue;
      final result = await remote.documentBytes(doc.id!);
      if (!mounted) return;
      if (result.data != null && result.data!.isNotEmpty) {
        setState(() => _thumbBytes[doc.id!] = result.data!);
      }
    }
  }

  Future<void> _pick(String slot) async {
    final file = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 2000,
      imageQuality: 85,
    );
    if (file == null || !mounted) return;
    setState(() {
      switch (slot) {
        case 'front':
          _idFrontPath = file.path;
        case 'back':
          _idBackPath = file.path;
        case 'selfie':
          _selfiePath = file.path;
      }
    });
  }

  Future<void> _upload() async {
    final l10n = AppLocalizations.of(context);
    if (_idFrontPath == null || _idBackPath == null || _selfiePath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(l10n.identityMissing)),
      );
      return;
    }
    setState(() => _uploading = true);
    final result = await ref.read(identityRemoteSourceProvider).upload(
          idFrontPath: _idFrontPath!,
          idBackPath: _idBackPath!,
          selfiePath: _selfiePath!,
        );
    if (!mounted) return;
    if (result.failure != null) {
      setState(() => _uploading = false);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await ref.read(authControllerProvider.notifier).refreshMe();
    if (!mounted) return;
    setState(() {
      _uploading = false;
      _idFrontPath = null;
      _idBackPath = null;
      _selfiePath = null;
      _thumbBytes.clear();
      _status = result.data;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(l10n.identityUploaded)),
    );
    if (result.data != null) {
      await _loadThumbs(result.data!);
    }
  }

  String _chipLabel(AppLocalizations l10n, String chip) {
    return switch (chip) {
      'resubmit_required' => l10n.resubmitRequired,
      'pending_upload' => l10n.identityPending,
      _ => l10n.identitySubmitted,
    };
  }

  Color _chipColor(String chip) {
    return switch (chip) {
      'resubmit_required' => AppColors.warning,
      'pending_upload' => AppColors.accent,
      _ => AppColors.success,
    };
  }

  String _docLabel(AppLocalizations l10n, String type) {
    return switch (type) {
      'id_front' => l10n.idFront,
      'id_back' => l10n.idBack,
      'selfie' => l10n.selfie,
      _ => type,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final status = _status;
    final chip = status?.statusChip ?? 'pending_upload';
    final canUpload = status?.canUpload ?? false;
    final hasDocs = status?.documents.any((d) => d.uploaded) ?? false;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.identity)),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(24),
                border: Border.all(
                  color: AppColors.border.withValues(alpha: 0.7),
                ),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    l10n.identitySub,
                    style: const TextStyle(color: AppColors.muted, height: 1.4),
                  ),
                  const SizedBox(height: 16),
                  Align(
                    alignment: AlignmentDirectional.centerStart,
                    child: Chip(
                      label: Text(
                        _chipLabel(l10n, chip),
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w700,
                          fontSize: 12,
                        ),
                      ),
                      backgroundColor: _chipColor(chip),
                      side: BorderSide.none,
                      padding: const EdgeInsets.symmetric(horizontal: 4),
                    ),
                  ),
                  if (status?.resubmitRequired == true) ...[
                    const SizedBox(height: 16),
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: AppColors.warning.withValues(alpha: 0.12),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(
                          color: AppColors.warning.withValues(alpha: 0.45),
                        ),
                      ),
                      child: Row(
                        children: [
                          const Icon(
                            Icons.warning_amber_rounded,
                            color: AppColors.warning,
                          ),
                          const SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              l10n.resubmitRequired,
                              style: const TextStyle(
                                fontWeight: FontWeight.w600,
                                color: AppColors.text,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  if (hasDocs) ...[
                    const SizedBox(height: 20),
                    Wrap(
                      spacing: 12,
                      runSpacing: 12,
                      children: [
                        for (final doc in status!.documents)
                          if (doc.uploaded)
                            _DocumentThumb(
                              label: _docLabel(l10n, doc.type),
                              bytes: doc.id != null
                                  ? _thumbBytes[doc.id!]
                                  : null,
                            ),
                      ],
                    ),
                  ],
                  if (canUpload) ...[
                    const SizedBox(height: 20),
                    IdentityFolderPicker(
                      label: l10n.idFront,
                      path: _idFrontPath,
                      onPick: () => _pick('front'),
                    ),
                    const SizedBox(height: 12),
                    IdentityFolderPicker(
                      label: l10n.idBack,
                      path: _idBackPath,
                      onPick: () => _pick('back'),
                    ),
                    const SizedBox(height: 12),
                    IdentityFolderPicker(
                      label: l10n.selfie,
                      path: _selfiePath,
                      onPick: () => _pick('selfie'),
                    ),
                  ],
                  if (!canUpload && hasDocs) ...[
                    const SizedBox(height: 16),
                    Text(
                      l10n.identitySubmitted,
                      style: const TextStyle(
                        color: AppColors.muted,
                        height: 1.4,
                      ),
                    ),
                  ],
                ],
              ),
            ),
            if (canUpload) ...[
              const SizedBox(height: 24),
              GradientButton(
                label: l10n.uploadDocuments,
                isLoading: _uploading,
                onPressed: _upload,
              ),
            ] else if (hasDocs) ...[
              const SizedBox(height: 24),
              GradientButton.indigo(
                label: l10n.navHome,
                onPressed: () => context.go('/home'),
              ),
            ],
          ],
        ),
      ),
    );
  }
}

class _DocumentThumb extends StatelessWidget {
  const _DocumentThumb({required this.label, this.bytes});

  final String label;
  final Uint8List? bytes;

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(12),
          child: SizedBox(
            width: 96,
            height: 96,
            child: bytes != null
                ? Image.memory(bytes!, fit: BoxFit.cover)
                : Container(
                    color: AppColors.borderSoft,
                    child: const Center(
                      child: SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      ),
                    ),
                  ),
          ),
        ),
        const SizedBox(height: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: AppColors.muted),
        ),
      ],
    );
  }
}

class IdentityFolderPicker extends StatelessWidget {
  const IdentityFolderPicker({
    super.key,
    required this.label,
    required this.path,
    required this.onPick,
  });

  final String label;
  final String? path;
  final VoidCallback onPick;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final filename = path?.split(RegExp(r'[\\/]')).last;

    return InkWell(
      onTap: onPick,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.borderSoft),
        ),
        child: Row(
          children: [
            Icon(
              path != null ? Icons.folder_open : Icons.folder_outlined,
              color: path != null ? AppColors.success : AppColors.primary,
              size: 28,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    label,
                    style: const TextStyle(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    filename ?? l10n.pickImage,
                    style: const TextStyle(
                      color: AppColors.muted,
                      fontSize: 12,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
            if (path != null)
              const Icon(Icons.check_circle, color: AppColors.success, size: 20),
          ],
        ),
      ),
    );
  }
}
