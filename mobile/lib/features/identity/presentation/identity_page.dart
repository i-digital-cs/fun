import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

class IdentityPage extends ConsumerStatefulWidget {
  const IdentityPage({super.key});

  @override
  ConsumerState<IdentityPage> createState() => _IdentityPageState();
}

class _IdentityPageState extends ConsumerState<IdentityPage> {
  String? _idFrontPath;
  String? _idBackPath;
  String? _selfiePath;
  bool _uploading = false;
  final _picker = ImagePicker();

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
    if (_idFrontPath == null || _idBackPath == null || _selfiePath == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).identityMissing)),
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
    setState(() => _uploading = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).identityUploaded)),
    );
    context.go('/home');
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.identity)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(24),
              border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(
                  l10n.identitySub,
                  style: const TextStyle(color: AppColors.muted, height: 1.4),
                ),
                const SizedBox(height: 20),
                IdentityImagePickerTile(
                  label: l10n.idFront,
                  path: _idFrontPath,
                  onPick: () => _pick('front'),
                ),
                const SizedBox(height: 12),
                IdentityImagePickerTile(
                  label: l10n.idBack,
                  path: _idBackPath,
                  onPick: () => _pick('back'),
                ),
                const SizedBox(height: 12),
                IdentityImagePickerTile(
                  label: l10n.selfie,
                  path: _selfiePath,
                  onPick: () => _pick('selfie'),
                ),
              ],
            ),
          ),
          const SizedBox(height: 24),
          GradientButton(
            label: l10n.uploadDocuments,
            isLoading: _uploading,
            onPressed: _upload,
          ),
        ],
      ),
    );
  }
}

class IdentityImagePickerTile extends StatelessWidget {
  const IdentityImagePickerTile({
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
              path != null ? Icons.check_circle : Icons.add_a_photo_outlined,
              color: path != null ? AppColors.success : AppColors.primary,
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                label,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            Text(
              path != null ? l10n.selected : l10n.pickImage,
              style: const TextStyle(
                color: AppColors.muted,
                fontSize: 12,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
