import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/ambient_background.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/data/apple_sign_in_helper.dart';
import 'package:fun_mobile/features/auth/data/google_sign_in_helper.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_access_theater.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_site_branding.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:sign_in_with_apple/sign_in_with_apple.dart';

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
  bool _theater = false;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<String?> _authenticate() {
    return ref
        .read(authControllerProvider.notifier)
        .login(
          email: _email.text.trim(),
          password: _password.text,
          commitSession: false,
        );
  }

  void _goAfterAuth() {
    ref.read(authControllerProvider.notifier).commitPendingSession();
    if (!mounted) return;
    final auth = ref.read(authControllerProvider);
    if (auth is Authenticated) {
      goAfterSuccessfulAuth(context, auth.user);
    }
  }

  void _goAfterSocialAuth() {
    if (!mounted) return;
    final auth = ref.read(authControllerProvider);
    if (auth is Authenticated) {
      goAfterSuccessfulAuth(context, auth.user);
    }
  }

  void _startTheater() {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _theater = true;
      _error = null;
      _submitting = true;
    });
  }

  Future<void> _apple() async {
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      final credential = await AppleSignInHelper.signIn();
      if (credential == null) {
        if (!mounted) return;
        setState(() => _submitting = false);
        return;
      }
      final idToken = credential.identityToken;
      if (idToken == null || idToken.isEmpty) {
        if (!mounted) return;
        setState(() {
          _submitting = false;
          _error = 'Apple Sign-In failed';
        });
        return;
      }
      final given = credential.givenName?.trim();
      final family = credential.familyName?.trim();
      final nameParts = [given, family].whereType<String>().where((s) => s.isNotEmpty);
      final name = nameParts.isEmpty ? null : nameParts.join(' ');
      final locale = ref.read(localeCodeProvider);
      final err = await ref
          .read(authControllerProvider.notifier)
          .loginWithApple(idToken: idToken, name: name, locale: locale);
      if (!mounted) return;
      if (err != null) {
        setState(() {
          _submitting = false;
          _error = err;
        });
        return;
      }
      setState(() => _submitting = false);
      _goAfterSocialAuth();
    } on SignInWithAppleAuthorizationException catch (e) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        if (e.code != AuthorizationErrorCode.canceled) {
          _error = AppLocalizations.of(context).errorGeneric;
        }
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = AppLocalizations.of(context).errorGeneric;
      });
    }
  }

  Future<void> _google() async {
    setState(() {
      _submitting = true;
      _error = null;
    });
    try {
      final googleUser = await GoogleSignInHelper.signIn();
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
      final error = await ref
          .read(authControllerProvider.notifier)
          .loginWithGoogle(
            idToken: hasId ? idToken : null,
            accessToken: hasAccess ? accessToken : null,
            locale: locale,
          );
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = error;
      });
      if (error == null) _goAfterSocialAuth();
    } on PlatformException catch (e) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = GoogleSignInHelper.messageFor(
          e,
          AppLocalizations.of(context).googleSignInFailed,
        );
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _submitting = false;
        _error = kDebugMode
            ? '${AppLocalizations.of(context).googleSignInFailed} ($e)'
            : AppLocalizations.of(context).googleSignInFailed;
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
                                Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(
                                            l10n.login,
                                            style: const TextStyle(
                                              fontSize: 24,
                                              fontWeight: FontWeight.w800,
                                              letterSpacing: -0.5,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            l10n.accessFormSub,
                                            style: TextStyle(
                                              fontSize: 13,
                                              color: AppColors.muted.withValues(
                                                alpha: 0.95,
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ),
                                    TextButton(
                                      onPressed: () async {
                                        final next = locale == 'ar'
                                            ? 'en'
                                            : 'ar';
                                        ref
                                                .read(
                                                  localeCodeProvider.notifier,
                                                )
                                                .state =
                                            next;
                                        await ref
                                            .read(sharedPreferencesProvider)
                                            .setString('locale', next);
                                        ref
                                            .read(apiClientProvider)
                                            .setLocale(next);
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
                                const SizedBox(height: 14),
                                TextFormField(
                                  controller: _password,
                                  obscureText: true,
                                  decoration: InputDecoration(
                                    labelText: l10n.password,
                                  ),
                                  validator: (v) {
                                    if (v == null || v.isEmpty) {
                                      return l10n.validationRequired;
                                    }
                                    return null;
                                  },
                                ),
                                Align(
                                  alignment: AlignmentDirectional.centerEnd,
                                  child: TextButton(
                                    onPressed: () =>
                                        context.go('/forgot-password'),
                                    child: Text(
                                      l10n.forgotPassword,
                                      style: const TextStyle(
                                        fontWeight: FontWeight.w700,
                                        color: AppColors.primary,
                                      ),
                                    ),
                                  ),
                                ),
                                if (_error != null) ...[
                                  const SizedBox(height: 4),
                                  Text(
                                    _error!,
                                    style: const TextStyle(
                                      color: AppColors.danger,
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 12),
                                GradientButton.indigo(
                                  label: l10n.login,
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
                                if (AppleSignInHelper.isSupported) ...[
                                  const SizedBox(height: 10),
                                  SignInWithAppleButton(
                                    onPressed: () {
                                      if (!_submitting) _apple();
                                    },
                                    style: SignInWithAppleButtonStyle.black,
                                    borderRadius: const BorderRadius.all(
                                      Radius.circular(12),
                                    ),
                                  ),
                                ],
                                const SizedBox(height: 12),
                                Wrap(
                                  alignment: WrapAlignment.center,
                                  crossAxisAlignment: WrapCrossAlignment.center,
                                  children: [
                                    Text(
                                      l10n.authLegalConsent,
                                      style: const TextStyle(
                                        fontSize: 12,
                                        color: AppColors.muted,
                                      ),
                                    ),
                                    TextButton(
                                      onPressed: () =>
                                          context.push('/terms'),
                                      child: Text(l10n.termsOfService),
                                    ),
                                    TextButton(
                                      onPressed: () =>
                                          context.push('/privacy-policy'),
                                      child: Text(l10n.privacyPolicy),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 8),
                                TextButton(
                                  onPressed: () => context.go('/discover'),
                                  child: Text(l10n.browseAsGuest),
                                ),
                                TextButton(
                                  onPressed: () => context.go('/register'),
                                  child: Text(
                                    '${l10n.noAccount} ${l10n.register}',
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
