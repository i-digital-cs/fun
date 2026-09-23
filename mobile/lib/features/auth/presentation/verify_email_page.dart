import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/otp_code_fields.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class VerifyEmailPage extends ConsumerStatefulWidget {
  const VerifyEmailPage({super.key});

  @override
  ConsumerState<VerifyEmailPage> createState() => _VerifyEmailPageState();
}

class _VerifyEmailPageState extends ConsumerState<VerifyEmailPage> {
  String _code = '';
  String? _error;
  String? _info;
  bool _submitting = false;
  bool _resending = false;

  Future<void> _verify() async {
    if (_code.length != 4) return;
    setState(() {
      _submitting = true;
      _error = null;
      _info = null;
    });
    final error =
        await ref.read(authControllerProvider.notifier).verifyEmail(_code);
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

  Future<void> _resend() async {
    setState(() {
      _resending = true;
      _error = null;
      _info = null;
    });
    final error =
        await ref.read(authControllerProvider.notifier).resendEmailVerify();
    if (!mounted) return;
    setState(() {
      _resending = false;
      if (error != null) {
        _error = error;
      } else {
        _info = AppLocalizations.of(context).codeSent;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final email = auth is Authenticated ? auth.user.email : null;

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
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        const AuthSiteBranding(),
                        Text(
                          l10n.verifyEmail,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 24,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.5,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          l10n.verifyEmailHint,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: AppColors.muted,
                            fontSize: 14,
                            height: 1.4,
                          ),
                        ),
                        if (email != null) ...[
                          const SizedBox(height: 6),
                          Text(
                            email,
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
                          onChanged: (code) => setState(() => _code = code),
                        ),
                        if (_error != null) ...[
                          const SizedBox(height: 12),
                          Text(
                            _error!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppColors.danger),
                          ),
                        ],
                        if (_info != null) ...[
                          const SizedBox(height: 12),
                          Text(
                            _info!,
                            textAlign: TextAlign.center,
                            style: const TextStyle(color: AppColors.primary),
                          ),
                        ],
                        const SizedBox(height: 20),
                        GradientButton.indigo(
                          label: l10n.verifyEmail,
                          isLoading: _submitting,
                          onPressed: _code.length == 4 ? _verify : null,
                        ),
                        const SizedBox(height: 8),
                        TextButton(
                          onPressed: (_submitting || _resending) ? null : _resend,
                          child: _resending
                              ? Text(l10n.loading)
                              : Text(l10n.resendCode),
                        ),
                        TextButton(
                          onPressed: _submitting
                              ? null
                              : () => ref
                                  .read(authControllerProvider.notifier)
                                  .logout(),
                          child: Text(l10n.logout),
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
