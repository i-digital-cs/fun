import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_access_theater.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:google_sign_in/google_sign_in.dart';

class RegisterPage extends ConsumerStatefulWidget {
  const RegisterPage({super.key});

  @override
  ConsumerState<RegisterPage> createState() => _RegisterPageState();
}

class _RegisterPageState extends ConsumerState<RegisterPage> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _passwordConfirm = TextEditingController();
  String? _error;
  bool _submitting = false;
  bool _theater = false;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _password.dispose();
    _passwordConfirm.dispose();
    super.dispose();
  }

  void _goAfterAuth() {
    ref.read(authControllerProvider.notifier).commitPendingSession();
  }

  void _startTheater() {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _theater = true;
      _error = null;
      _submitting = true;
    });
  }

  Future<String?> _authenticate() {
    final locale = ref.read(localeCodeProvider);
    return ref.read(authControllerProvider.notifier).register(
          name: _name.text.trim(),
          email: _email.text.trim(),
          password: _password.text,
          passwordConfirmation: _passwordConfirm.text,
          locale: locale,
          commitSession: false,
        );
  }

  Future<void> _google() async {
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      final serverClientId = Env.googleServerClientId;
      final googleUser = await GoogleSignIn(
        scopes: const ['email', 'profile'],
        serverClientId: serverClientId.isEmpty ? null : serverClientId,
      ).signIn();
      if (googleUser == null) {
        if (!mounted) return;
        setState(() => _submitting = false);
        return;
      }
      final googleAuth = await googleUser.authentication;
      final idToken = googleAuth.idToken;
      final accessToken = googleAuth.accessToken;
      final hasId = idToken != null && idToken.isNotEmpty;
      final hasAccess = accessToken != null && accessToken.isNotEmpty;
      if (!hasId && !hasAccess) {
        if (!mounted) return;
        setState(() {
          _submitting = false;
          _error = AppLocalizations.of(context).googleSignInFailed;
        });
        return;
      }
      final locale = ref.read(localeCodeProvider);
      final error =
          await ref.read(authControllerProvider.notifier).loginWithGoogle(
                idToken: hasId ? idToken : null,
                accessToken: hasAccess ? accessToken : null,
                locale: locale,
              );
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = error;
      });
      if (error == null) _goAfterAuth();
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = AppLocalizations.of(context).googleSignInFailed;
      });
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
                    child: _theater
                        ? AuthAccessTheater(
                            digits: AuthAccessTheater.digitsFrom(
                              source: AuthDigitSource.password,
                              password: _password.text,
                            ),
                            title: l10n.accessEnterTitle,
                            subtitle: l10n.accessLoginEnterSub,
                            authenticate: _authenticate,
                            onSuccess: _goAfterAuth,
                            onCancel: (error) {
                              if (!mounted) return;
                              ref
                                  .read(authControllerProvider.notifier)
                                  .discardPendingSession();
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
                                  l10n.register,
                                  style: const TextStyle(
                                    fontSize: 24,
                                    fontWeight: FontWeight.w800,
                                    letterSpacing: -0.5,
                                  ),
                                ),
                                const SizedBox(height: 24),
                                TextFormField(
                                  controller: _name,
                                  textCapitalization: TextCapitalization.words,
                                  decoration:
                                      InputDecoration(labelText: l10n.name),
                                  validator: (v) =>
                                      (v == null || v.trim().isEmpty)
                                          ? l10n.validationRequired
                                          : null,
                                ),
                                const SizedBox(height: 14),
                                TextFormField(
                                  controller: _email,
                                  keyboardType: TextInputType.emailAddress,
                                  autocorrect: false,
                                  decoration:
                                      InputDecoration(labelText: l10n.email),
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
                                const SizedBox(height: 14),
                                TextFormField(
                                  controller: _password,
                                  obscureText: true,
                                  decoration: InputDecoration(
                                    labelText: l10n.password,
                                  ),
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
                                    style: const TextStyle(
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 20),
                                GradientButton(
                                  label: l10n.register,
                                  isLoading: _submitting,
                                  onPressed: _startTheater,
                                ),
                                const SizedBox(height: 10),
                                OutlinedButton.icon(
                                  onPressed: _submitting ? null : _google,
                                  icon: const Icon(
                                    Icons.g_mobiledata,
                                    size: 28,
                                  ),
                                  style: OutlinedButton.styleFrom(
                                    padding: const EdgeInsets.symmetric(
                                      vertical: 12,
                                    ),
                                    shape: RoundedRectangleBorder(
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                  ),
                                  label: Text(l10n.continueWithGoogle),
                                ),
                                const SizedBox(height: 8),
                                TextButton(
                                  onPressed: () => context.go('/discover'),
                                  child: Text(l10n.browseAsGuest),
                                ),
                                TextButton(
                                  onPressed: () => context.go('/login'),
                                  child: Text(
                                    '${l10n.haveAccount} ${l10n.login}',
                                  ),
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
