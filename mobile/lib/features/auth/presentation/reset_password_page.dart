import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class ResetPasswordPage extends ConsumerStatefulWidget {
  const ResetPasswordPage({
    super.key,
    required this.email,
    required this.resetToken,
  });

  final String email;
  final String resetToken;

  @override
  ConsumerState<ResetPasswordPage> createState() => _ResetPasswordPageState();
}

class _ResetPasswordPageState extends ConsumerState<ResetPasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _password = TextEditingController();
  final _passwordConfirm = TextEditingController();
  String? _error;
  bool _submitting = false;

  @override
  void dispose() {
    _password.dispose();
    _passwordConfirm.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (widget.email.isEmpty || widget.resetToken.isEmpty) {
      setState(() => _error = AppLocalizations.of(context).errorGeneric);
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    final error = await ref.read(authControllerProvider.notifier).resetPassword(
          email: widget.email,
          resetToken: widget.resetToken,
          password: _password.text,
          passwordConfirmation: _passwordConfirm.text,
        );
    if (!mounted) return;
    setState(() {
      _submitting = false;
      _error = error;
    });
    if (error == null) {
      final auth = ref.read(authControllerProvider);
      if (auth is Authenticated) {
        context.go(auth.user.routeAfterAuth);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Scaffold(
      body: AmbientBackground(
        child: SafeArea(
          child: Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24),
              child: ConstrainedBox(
                constraints: const BoxConstraints(maxWidth: 420),
                child: DecoratedBox(
                  decoration: BoxDecoration(
                    color: AppColors.surface.withValues(alpha: 0.92),
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(
                      color: AppColors.border.withValues(alpha: 0.7),
                    ),
                    boxShadow: [
                      BoxShadow(
                        color: AppColors.primary.withValues(alpha: 0.06),
                        blurRadius: 24,
                        offset: const Offset(0, 12),
                      ),
                    ],
                  ),
                  child: Padding(
                    padding: const EdgeInsets.all(28),
                    child: Form(
                      key: _formKey,
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          const AuthSiteBranding(),
                          Text(
                            l10n.resetPassword,
                            textAlign: TextAlign.center,
                            style: const TextStyle(
                              fontSize: 24,
                              fontWeight: FontWeight.w800,
                              letterSpacing: -0.5,
                            ),
                          ),
                          const SizedBox(height: 24),
                          TextFormField(
                            controller: _password,
                            obscureText: true,
                            decoration:
                                InputDecoration(labelText: l10n.newPassword),
                            validator: (v) => (v == null || v.isEmpty)
                                ? l10n.validationRequired
                                : null,
                          ),
                          const SizedBox(height: 14),
                          TextFormField(
                            controller: _passwordConfirm,
                            obscureText: true,
                            decoration: InputDecoration(
                              labelText: l10n.passwordConfirmation,
                            ),
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return l10n.validationRequired;
                              }
                              if (v != _password.text) {
                                return l10n.passwordConfirmation;
                              }
                              return null;
                            },
                          ),
                          if (_error != null) ...[
                            const SizedBox(height: 12),
                            Text(
                              _error!,
                              style: const TextStyle(color: AppColors.danger),
                            ),
                          ],
                          const SizedBox(height: 20),
                          GradientButton.indigo(
                            label: l10n.resetPassword,
                            isLoading: _submitting,
                            onPressed: _submit,
                          ),
                          const SizedBox(height: 8),
                          TextButton(
                            onPressed: () => context.go('/login'),
                            child: Text(l10n.back),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}
