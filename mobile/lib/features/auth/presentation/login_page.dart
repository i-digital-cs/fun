import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:google_sign_in/google_sign_in.dart';

class LoginPage extends ConsumerStatefulWidget {
  const LoginPage({super.key});

  @override
  ConsumerState<LoginPage> createState() => _LoginPageState();
}

class _LoginPageState extends ConsumerState<LoginPage> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  String? _error;
  bool _submitting = false;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  void _goAfterAuth() {
    final auth = ref.read(authControllerProvider);
    if (auth is Authenticated && auth.user.isDeactivated) {
      context.go('/account/deactivated');
    } else {
      context.go('/home');
    }
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _submitting = true;
      _error = null;
    });
    final error = await ref.read(authControllerProvider.notifier).login(
          email: _email.text.trim(),
          password: _password.text,
        );
    if (!mounted) return;
    setState(() {
      _submitting = false;
      _error = error;
    });
    if (error == null) _goAfterAuth();
  }

  Future<void> _google() async {
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      final googleUser = await GoogleSignIn(
        scopes: const ['email', 'profile'],
      ).signIn();
      if (googleUser == null) {
        if (!mounted) return;
        setState(() => _submitting = false);
        return;
      }
      final googleAuth = await googleUser.authentication;
      final accessToken = googleAuth.accessToken;
      if (accessToken == null || accessToken.isEmpty) {
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
                accessToken: accessToken,
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
    final locale = ref.watch(localeCodeProvider);

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
                          Row(
                            children: [
                              Text(
                                l10n.login,
                                style: const TextStyle(
                                  fontSize: 24,
                                  fontWeight: FontWeight.w800,
                                  letterSpacing: -0.5,
                                ),
                              ),
                              const Spacer(),
                              TextButton(
                                onPressed: () async {
                                  final next = locale == 'ar' ? 'en' : 'ar';
                                  ref.read(localeCodeProvider.notifier).state =
                                      next;
                                  await ref
                                      .read(sharedPreferencesProvider)
                                      .setString('locale', next);
                                  ref.read(apiClientProvider).setLocale(next);
                                },
                                child: Text(locale == 'ar' ? 'EN' : 'ع'),
                              ),
                            ],
                          ),
                          const SizedBox(height: 24),
                          TextFormField(
                            controller: _email,
                            keyboardType: TextInputType.emailAddress,
                            autocorrect: false,
                            decoration: InputDecoration(labelText: l10n.email),
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
                            decoration:
                                InputDecoration(labelText: l10n.password),
                            validator: (v) {
                              if (v == null || v.isEmpty) {
                                return l10n.validationRequired;
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
                            label: l10n.login,
                            isLoading: _submitting,
                            onPressed: _submit,
                          ),
                          const SizedBox(height: 10),
                          OutlinedButton.icon(
                            onPressed: _submitting ? null : _google,
                            icon: const Icon(Icons.g_mobiledata, size: 28),
                            style: OutlinedButton.styleFrom(
                              padding: const EdgeInsets.symmetric(vertical: 12),
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
                            onPressed: () => context.go('/register'),
                            child: Text('${l10n.noAccount} ${l10n.register}'),
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
