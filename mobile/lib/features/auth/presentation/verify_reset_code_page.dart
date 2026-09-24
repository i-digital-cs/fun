import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/reset_password_page.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_access_theater.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/otp_code_fields.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class VerifyResetCodePage extends ConsumerStatefulWidget {
  const VerifyResetCodePage({super.key, required this.email});

  final String email;

  @override
  ConsumerState<VerifyResetCodePage> createState() =>
      _VerifyResetCodePageState();
}

class _VerifyResetCodePageState extends ConsumerState<VerifyResetCodePage> {
  String _code = '';
  String? _error;
  String? _info;
  bool _submitting = false;
  bool _resending = false;
  bool _theater = false;
  String? _resetToken;

  void _startTheater() {
    if (_code.length != 4 || widget.email.isEmpty) return;
    setState(() {
      _theater = true;
      _error = null;
      _info = null;
      _submitting = true;
      _resetToken = null;
    });
  }

  Future<String?> _authenticate() async {
    final result = await ref
        .read(authControllerProvider.notifier)
        .verifyResetCode(email: widget.email, code: _code);
    _resetToken = result.resetToken;
    return result.error;
  }

  void _onSuccess() {
    if (_resetToken == null) return;
    context.go(
      '/reset-password',
      extra: ResetPasswordArgs(
        email: widget.email,
        resetToken: _resetToken!,
      ),
    );
  }

  Future<void> _resend() async {
    if (widget.email.isEmpty) return;
    setState(() {
      _resending = true;
      _error = null;
      _info = null;
    });
    final result = await ref
        .read(authControllerProvider.notifier)
        .forgotPassword(widget.email);
    if (!mounted) return;
    setState(() {
      _resending = false;
      if (result.error != null) {
        _error = result.error;
      } else {
        _info = AppLocalizations.of(context).codeSent;
      }
    });
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
                              source: AuthDigitSource.code,
                              code: _code,
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
                        : Column(
                            crossAxisAlignment: CrossAxisAlignment.stretch,
                            children: [
                              const AuthSiteBranding(),
                              Text(
                                l10n.verifyResetCode,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: -0.5,
                                ),
                              ),
                              const SizedBox(height: 8),
                              Text(
                                l10n.verifyResetCodeSub,
                                textAlign: TextAlign.center,
                                style: const TextStyle(
                                  color: AppColors.muted,
                                  fontSize: 14,
                                ),
                              ),
                              if (widget.email.isNotEmpty) ...[
                                const SizedBox(height: 6),
                                Text(
                                  widget.email,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    fontWeight: FontWeight.w600,
                                    fontSize: 13,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 24),
                              OtpCodeFields(
                                enabled: !_submitting,
                                onChanged: (code) =>
                                    setState(() => _code = code),
                              ),
                              if (_error != null) ...[
                                const SizedBox(height: 12),
                                Text(
                                  _error!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: AppColors.danger,
                                  ),
                                ),
                              ],
                              if (_info != null) ...[
                                const SizedBox(height: 12),
                                Text(
                                  _info!,
                                  textAlign: TextAlign.center,
                                  style: const TextStyle(
                                    color: AppColors.primary,
                                  ),
                                ),
                              ],
                              const SizedBox(height: 20),
                              GradientButton.indigo(
                                label: l10n.verifyResetCode,
                                isLoading: _submitting,
                                onPressed:
                                    _code.length == 4 ? _startTheater : null,
                              ),
                              const SizedBox(height: 8),
                              TextButton(
                                onPressed: (_submitting || _resending)
                                    ? null
                                    : _resend,
                                child: _resending
                                    ? Text(l10n.loading)
                                    : Text(l10n.resendCode),
                              ),
                              TextButton(
                                onPressed: () =>
                                    context.go('/forgot-password'),
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
    );
  }
}
