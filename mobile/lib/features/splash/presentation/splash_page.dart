import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/site_brand_mark.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/site/data/app_update_service.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/features/site/domain/app_update.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Animated launch screen — site logo + name from `GET /api/site`.
/// Auth redirect is handled by GoRouter once [AuthController.bootstrap] finishes.
class SplashPage extends ConsumerStatefulWidget {
  const SplashPage({super.key});

  @override
  ConsumerState<SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<SplashPage>
    with TickerProviderStateMixin {
  String? _restoreError;

  late final AnimationController _logoController;
  late final AnimationController _taglineController;
  late final AnimationController _progressController;

  late final Animation<double> _logoScale;
  late final Animation<double> _logoFade;
  late final Animation<Offset> _taglineSlide;
  late final Animation<double> _taglineFade;

  @override
  void initState() {
    super.initState();

    SystemChrome.setSystemUIOverlayStyle(
      const SystemUiOverlayStyle(
        statusBarColor: Colors.transparent,
        statusBarIconBrightness: Brightness.light,
        statusBarBrightness: Brightness.dark,
      ),
    );

    _logoController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 900),
    );
    _logoScale = Tween<double>(begin: 0.5, end: 1.0).animate(
      CurvedAnimation(parent: _logoController, curve: Curves.elasticOut),
    );
    _logoFade = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(
        parent: _logoController,
        curve: const Interval(0, 0.6, curve: Curves.easeOut),
      ),
    );

    _taglineController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 600),
    );
    _taglineSlide =
        Tween<Offset>(begin: const Offset(0, 0.5), end: Offset.zero).animate(
          CurvedAnimation(
            parent: _taglineController,
            curve: Curves.easeOutCubic,
          ),
        );
    _taglineFade = Tween<double>(begin: 0, end: 1).animate(
      CurvedAnimation(parent: _taglineController, curve: Curves.easeOut),
    );

    _progressController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 2200),
    );

    Future.microtask(_start);
  }

  Future<void> _start() async {
    await Future<void>.delayed(const Duration(milliseconds: 200));
    if (!mounted) return;
    _logoController.forward();

    await Future<void>.delayed(const Duration(milliseconds: 500));
    if (!mounted) return;
    _taglineController.forward();
    _progressController.forward();

    await _boot();
  }

  Future<void> _boot() async {
    setState(() => _restoreError = null);

    // Keep splash visible briefly while startup work runs.
    await Future.wait([
      Future<void>.delayed(const Duration(milliseconds: 1800)),
      _runStartup(),
    ]);
  }

  Future<void> _runStartup() async {
    final locale = ref.read(localeCodeProvider);
    final update = await checkForUpdate(
      ref.read(siteRemoteSourceProvider),
      locale: locale,
    );
    if (!mounted) return;

    if (update.kind == UpdateKind.force || update.kind == UpdateKind.soft) {
      final l10n = AppLocalizations.of(context);
      final force = update.kind == UpdateKind.force;
      await showDialog<void>(
        context: context,
        barrierDismissible: !force,
        builder: (ctx) => PopScope(
          canPop: !force,
          child: AlertDialog(
            title: Text(l10n.updateAvailable),
            content: Text(
              update.message?.isNotEmpty == true
                  ? update.message!
                  : l10n.updateAvailableBody,
            ),
            actions: [
              if (!force)
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: Text(l10n.updateLater),
                ),
              FilledButton(
                onPressed: () async {
                  await openStoreUrl(update.storeUrl);
                  if (!force && ctx.mounted) Navigator.pop(ctx);
                },
                child: Text(l10n.updateNow),
              ),
            ],
          ),
        ),
      );
      if (force) return;
    }

    // Warm branding in parallel with session restore.
    unawaited(ref.read(siteBrandingProvider.future));

    await ref.read(authControllerProvider.notifier).bootstrap();
    if (!mounted) return;
    final auth = ref.read(authControllerProvider);
    if (auth is AuthSessionRestoreFailed) {
      setState(() {
        _restoreError = auth.message ?? AppLocalizations.of(context).errorOffline;
      });
    }
  }

  @override
  void dispose() {
    _logoController.dispose();
    _taglineController.dispose();
    _progressController.dispose();
    SystemChrome.setSystemUIOverlayStyle(SystemUiOverlayStyle.dark);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final brand = ref.watch(siteBrandingProvider).maybeWhen(
      data: (s) => s,
      orElse: () => SiteBranding.fallback,
    );

    if (_restoreError != null) {
      return Scaffold(
        backgroundColor: AppColors.canvas,
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SiteBrandMark(
                  logoUrl: brand.logoUrl,
                  size: 64,
                  borderRadius: 18,
                ),
                const SizedBox(height: 20),
                Text(
                  _restoreError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: 16),
                TextButton(
                  onPressed: () {
                    setState(() => _restoreError = null);
                    _progressController
                      ..reset()
                      ..forward();
                    unawaited(_boot());
                  },
                  child: Text(l10n.retry),
                ),
                const SizedBox(height: 8),
                FilledButton(
                  onPressed: () async {
                    // Clear stuck/expired session so public browse is unblocked.
                    await ref.read(authControllerProvider.notifier).logout();
                    if (!context.mounted) return;
                    context.go('/home');
                  },
                  child: Text(l10n.continueBrowsing),
                ),
              ],
            ),
          ),
        ),
      );
    }

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              AppColors.primaryDeep,
              AppColors.primary,
              AppColors.primaryViolet,
            ],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: Column(
            children: [
              Expanded(
                child: Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      ScaleTransition(
                        scale: _logoScale,
                        child: FadeTransition(
                          opacity: _logoFade,
                          child: Container(
                            width: 112,
                            height: 112,
                            padding: const EdgeInsets.all(18),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.14),
                              borderRadius: BorderRadius.circular(28),
                              border: Border.all(
                                color: Colors.white.withValues(alpha: 0.22),
                              ),
                            ),
                            child: SiteBrandMark(
                              logoUrl: brand.logoUrl,
                              size: 76,
                              borderRadius: 16,
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 28),
                      FadeTransition(
                        opacity: _logoFade,
                        child: Text(
                          brand.displayName,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 34,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.2,
                          ),
                        ),
                      ),
                      const SizedBox(height: 12),
                      SlideTransition(
                        position: _taglineSlide,
                        child: FadeTransition(
                          opacity: _taglineFade,
                          child: Text(
                            l10n.splashTagline,
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              color: Colors.white.withValues(alpha: 0.65),
                              fontSize: 16,
                              letterSpacing: 0.4,
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(40, 0, 40, 48),
                child: Column(
                  children: [
                    AnimatedBuilder(
                      animation: _progressController,
                      builder: (_, _) {
                        return ClipRRect(
                          borderRadius: BorderRadius.circular(4),
                          child: LinearProgressIndicator(
                            value: _progressController.value,
                            backgroundColor: Colors.white.withValues(
                              alpha: 0.15,
                            ),
                            valueColor: const AlwaysStoppedAnimation<Color>(
                              Colors.white,
                            ),
                            minHeight: 3,
                          ),
                        );
                      },
                    ),
                    const SizedBox(height: 20),
                    Text(
                      l10n.loading,
                      style: TextStyle(
                        color: Colors.white.withValues(alpha: 0.45),
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
