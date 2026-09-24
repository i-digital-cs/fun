import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_access_theater.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class ForgotPasswordPage extends ConsumerStatefulWidget {
  const ForgotPasswordPage({super.key});

  @override
  ConsumerState<ForgotPasswordPage> createState() => _ForgotPasswordPageState();
}

class _ForgotPasswordPageState extends ConsumerState<ForgotPasswordPage> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  String? _error;
  bool _submitting = false;
  bool _theater = false;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  void _startTheater() {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _theater = true;
      _error = null;
      _submitting = true;
    });
  }

  Future<String?> _authenticate() async {
    final result = await ref
        .read(authControllerProvider.notifier)
        .forgotPassword(_email.text.trim());
    return result.error;
  }

  void _onSuccess() {
    final email = _email.text.trim();
    context.go(
      Uri(
        path: '/forgot-password/verify',
        queryParameters: {'email': email},
      ).toString(),
    );
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
                    child: _theater
                        ? AuthAccessTheater(
                            digits: AuthAccessTheater.digitsFrom(
                              source: AuthDigitSource.email,
                              email: _email.text.trim(),
                            ),
                            title: l10n.accessEnterTitle,
                            subtitle: l10n.accessEnterSub,
                            authenticate: _authenticate,
                            onSuccess: _onSuccess,
                            onCancel: (error) {
                              if (!mounted) return;
                              setState(() {
                                _theater = false;
                                _submitting = false;
                                _error = error;
                              });
                            },
                          )
                        : Form(
                            key: _formKey,
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.stretch,
                              children: [
                                const AuthSiteBranding(),
                                Text(
                                  l10n.forgotPassword,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: -0.5,
                                  ),
                                ),
                                const SizedBox(height: 8),
                                Text(
                                  l10n.forgotPasswordSub,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: AppColors.muted,
                                    fontSize: 14,
                                    height: 1.4,
                                  ),
                                ),
                                const SizedBox(height: 24),
                                TextFormField(
                                  controller: _email,
                                  keyboardType: TextInputType.emailAddress,
                                  autocorrect: false,
                                  decoration: InputDecoration(
                                    labelText: l10n.email,
                                  ),
                                  validator: (v) {
                                    if (v == null || v.trim().isEmpty) {
                                      return l10n.validationRequired;
                                    }
                                    if (!v.contains('@')) {
                                      return l10n.validationEmail;
                                    }
                                    return null;
                                  },
                                ),
                                if (_error != null) ...[
                                  const SizedBox(height: 12),
                                  Text(
                                    _error!,
                                    style: const TextStyle(
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 20),
                                GradientButton.indigo(
                                  label: l10n.sendResetCode,
                                  isLoading: _submitting,
                                  onPressed: _startTheater,
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
