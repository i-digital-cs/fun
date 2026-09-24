import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/attachment_folder_picker.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';

class ProfileEditPage extends ConsumerStatefulWidget {
  const ProfileEditPage({super.key});

  @override
  ConsumerState<ProfileEditPage> createState() => _ProfileEditPageState();
}

class _ProfileEditPageState extends ConsumerState<ProfileEditPage> {
  final _formKey = GlobalKey<FormState>();
  late final TextEditingController _name;
  late final TextEditingController _email;
  late final TextEditingController _currentPassword;
  late final TextEditingController _password;
  late final TextEditingController _passwordConfirm;
  String _locale = 'en';
  String? _photoPath;
  bool _removePhoto = false;
  bool _saving = false;
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    final auth = ref.read(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    _name = TextEditingController(text: user?.name ?? '');
    _email = TextEditingController(text: user?.email ?? '');
    _currentPassword = TextEditingController();
    _password = TextEditingController();
    _passwordConfirm = TextEditingController();
    _locale = user?.locale ?? ref.read(localeCodeProvider);
  }

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _currentPassword.dispose();
    _password.dispose();
    _passwordConfirm.dispose();
    super.dispose();
  }

  Future<void> _pickPhoto() async {
    final file = await _picker.pickImage(
      source: ImageSource.gallery,
      maxWidth: 1600,
      imageQuality: 85,
    );
    if (file == null || !mounted) return;
    setState(() {
      _photoPath = file.path;
      _removePhoto = false;
    });
  }

  Future<void> _save() async {
    if (!_formKey.currentState!.validate()) return;
    final changingPassword = _password.text.isNotEmpty;
    if (changingPassword && _currentPassword.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(AppLocalizations.of(context).currentPassword)),
      );
      return;
    }

    setState(() => _saving = true);
    final result = await ref
        .read(profileRepositoryProvider)
        .updateProfile(
          name: _name.text.trim(),
          email: _email.text.trim(),
          locale: _locale,
          currentPassword: changingPassword ? _currentPassword.text : null,
          password: changingPassword ? _password.text : null,
          passwordConfirmation: changingPassword ? _passwordConfirm.text : null,
          photoPath: _photoPath,
          removePhoto: _removePhoto,
        );
    if (!mounted) return;
    if (result.failure != null) {
      setState(() => _saving = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      return;
    }

    ref.read(localeCodeProvider.notifier).state = _locale;
    await ref.read(sharedPreferencesProvider).setString('locale', _locale);
    ref.read(apiClientProvider).setLocale(_locale);

    await ref.read(authControllerProvider.notifier).refreshMe();
    if (!mounted) return;
    setState(() => _saving = false);

    final needsVerify =
        result.emailChanged || (result.data?.needsEmailVerification ?? false);
    if (needsVerify) {
      context.go('/email/verify');
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).updateProfile)),
    );
    context.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final hasPassword = auth is Authenticated ? auth.user.hasPassword : true;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.editProfile)),
      body: Form(
        key: _formKey,
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
                  TextFormField(
                    controller: _name,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(labelText: l10n.name),
                    validator: (v) => (v == null || v.trim().isEmpty)
                        ? l10n.validationRequired
                        : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _email,
                    keyboardType: TextInputType.emailAddress,
                    textInputAction: TextInputAction.next,
                    decoration: InputDecoration(labelText: l10n.email),
                    validator: (v) {
                      if (v == null || v.trim().isEmpty) {
                        return l10n.validationRequired;
                      }
                      if (!v.contains('@')) return l10n.validationEmail;
                      return null;
                    },
                  ),
                  const SizedBox(height: 16),
                  DropdownButtonFormField<String>(
                    initialValue: _locale,
                    decoration: InputDecoration(labelText: l10n.language),
                    items: const [
                      DropdownMenuItem(value: 'en', child: Text('English')),
                      DropdownMenuItem(value: 'ar', child: Text('العربية')),
                    ],
                    onChanged: (v) {
                      if (v != null) setState(() => _locale = v);
                    },
                  ),
                  const SizedBox(height: 20),
                  Text(
                    l10n.password,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      color: AppColors.text,
                    ),
                  ),
                  const SizedBox(height: 8),
                  if (hasPassword) ...[
                    TextFormField(
                      controller: _currentPassword,
                      obscureText: true,
                      decoration: InputDecoration(
                        labelText: l10n.currentPassword,
                      ),
                    ),
                    const SizedBox(height: 12),
                  ],
                  TextFormField(
                    controller: _password,
                    obscureText: true,
                    decoration: InputDecoration(labelText: l10n.password),
                  ),
                  const SizedBox(height: 12),
                  TextFormField(
                    controller: _passwordConfirm,
                    obscureText: true,
                    decoration: InputDecoration(
                      labelText: l10n.passwordConfirmation,
                    ),
                    validator: (v) {
                      if (_password.text.isEmpty) return null;
                      if (v != _password.text) {
                        return l10n.passwordConfirmation;
                      }
                      return null;
                    },
                  ),
                  const SizedBox(height: 20),
                  AttachmentFolderPicker(
                    label: l10n.pickImage,
                    path: _photoPath,
                    onPick: _pickPhoto,
                    onClear: _photoPath == null
                        ? null
                        : () => setState(() => _photoPath = null),
                  ),
                  const SizedBox(height: 8),
                  CheckboxListTile(
                    contentPadding: EdgeInsets.zero,
                    value: _removePhoto,
                    onChanged: (v) => setState(() {
                      _removePhoto = v ?? false;
                      if (_removePhoto) _photoPath = null;
                    }),
                    title: Text(l10n.removePhoto),
                    controlAffinity: ListTileControlAffinity.leading,
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            GradientButton.indigo(
              label: l10n.updateProfile,
              isLoading: _saving,
              onPressed: _saving ? null : _save,
            ),
          ],
        ),
      ),
    );
  }
}
