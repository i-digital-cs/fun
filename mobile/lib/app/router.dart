import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/features/auth/presentation/account_deactivated_page.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/forgot_password_page.dart';
import 'package:fun_mobile/features/auth/presentation/login_page.dart';
import 'package:fun_mobile/features/auth/presentation/register_page.dart';
import 'package:fun_mobile/features/auth/presentation/reset_password_page.dart';
import 'package:fun_mobile/features/auth/presentation/verify_email_page.dart';
import 'package:fun_mobile/features/auth/presentation/verify_reset_code_page.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/route_ids.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/site_brand_mark.dart';
import 'package:fun_mobile/features/discover/presentation/discover_page.dart';
import 'package:fun_mobile/features/events/presentation/create_event_page.dart';
import 'package:fun_mobile/features/events/presentation/event_chat_page.dart';
import 'package:fun_mobile/features/events/presentation/event_detail_page.dart';
import 'package:fun_mobile/features/events/presentation/event_members_page.dart';
import 'package:fun_mobile/features/events/presentation/event_ticket_page.dart';
import 'package:fun_mobile/features/events/presentation/favorites_page.dart';
import 'package:fun_mobile/features/events/presentation/join_requests_page.dart';
import 'package:fun_mobile/features/events/presentation/my_events_page.dart';
import 'package:fun_mobile/features/home/presentation/home_page.dart';
import 'package:fun_mobile/features/home/presentation/main_shell.dart';
import 'package:fun_mobile/features/identity/presentation/identity_page.dart';
import 'package:fun_mobile/features/profile/presentation/location_page.dart';
import 'package:fun_mobile/features/profile/presentation/profile_edit_page.dart';
import 'package:fun_mobile/features/profile/presentation/profile_page.dart';
import 'package:fun_mobile/features/profile/presentation/public_profile_page.dart';
import 'package:fun_mobile/features/chat/presentation/chats_page.dart';
import 'package:fun_mobile/features/notifications/presentation/notifications_page.dart';
import 'package:fun_mobile/features/safety/presentation/blocks_page.dart';
import 'package:fun_mobile/features/safety/presentation/report_page.dart';
import 'package:fun_mobile/features/safety/presentation/safety_guidelines_page.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/features/site/data/app_update_service.dart';
import 'package:fun_mobile/features/site/domain/app_update.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

final _rootKey = GlobalKey<NavigatorState>();

final routerProvider = Provider<GoRouter>((ref) {
  final auth = ref.watch(authControllerProvider);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/splash',
    refreshListenable: _AuthRefresh(ref),
    redirect: (context, state) {
      final loc = state.matchedLocation;
      final isAuthRoute = loc == '/login' || loc == '/register';
      final isPasswordResetRoute =
          loc == '/forgot-password' ||
          loc == '/forgot-password/verify' ||
          loc == '/reset-password';
      final isGuestBrowse =
          loc == '/discover' ||
          loc == '/safety/guidelines' ||
          loc.startsWith('/u/') ||
          _isGuestEventShow(loc);

      if (auth is AuthInitial ||
          auth is AuthLoading ||
          auth is AuthSessionRestoreFailed) {
        return loc == '/splash' ? null : '/splash';
      }

      if (auth is Unauthenticated) {
        if (loc == '/splash') return '/discover';
        if (isAuthRoute || isPasswordResetRoute || isGuestBrowse) return null;
        return '/login';
      }

      if (auth is Authenticated) {
        final user = auth.user;

        if (user.isDeactivated) {
          if (loc == '/account/deactivated') return null;
          return '/account/deactivated';
        }

        if (loc == '/account/deactivated') {
          return user.routeAfterAuth;
        }

        final needsEmail = user.needsEmailVerification || !user.emailVerified;
        if (needsEmail) {
          if (loc == '/email/verify') return null;
          return '/email/verify';
        }

        if (loc == '/email/verify') {
          return user.routeAfterAuth;
        }

        if (user.needsIdentity) {
          if (_isIdentityBlockedRoute(loc)) {
            return '/identity';
          }
          if (loc == '/login' ||
              loc == '/register' ||
              loc == '/splash' ||
              isPasswordResetRoute) {
            return user.routeAfterAuth;
          }
          return null;
        }

        if (loc == '/login' ||
            loc == '/register' ||
            loc == '/splash' ||
            isPasswordResetRoute) {
          return user.routeAfterAuth;
        }
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const _SplashPage(),
      ),
      GoRoute(path: '/login', builder: (context, state) => const LoginPage()),
      GoRoute(
        path: '/register',
        builder: (context, state) => const RegisterPage(),
      ),
      GoRoute(
        path: '/email/verify',
        builder: (context, state) => const VerifyEmailPage(),
      ),
      GoRoute(
        path: '/forgot-password',
        builder: (context, state) => const ForgotPasswordPage(),
        routes: [
          GoRoute(
            path: 'verify',
            builder: (context, state) {
              final email = state.uri.queryParameters['email'] ?? '';
              return VerifyResetCodePage(email: email);
            },
          ),
        ],
      ),
      GoRoute(
        path: '/reset-password',
        builder: (context, state) {
          final extra = state.extra;
          if (extra is ResetPasswordArgs &&
              extra.email.isNotEmpty &&
              extra.resetToken.isNotEmpty) {
            return ResetPasswordPage(
              email: extra.email,
              resetToken: extra.resetToken,
            );
          }
          // SEC-RESET-001: never accept resetToken from query/path.
          return const ForgotPasswordPage();
        },
      ),
      GoRoute(
        path: '/account/deactivated',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const AccountDeactivatedPage(),
      ),
      GoRoute(
        path: '/chats',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const ChatsPage(),
      ),
      GoRoute(
        path: '/notifications',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const NotificationsPage(),
      ),
      GoRoute(
        path: '/events/:id',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final id = parsePositiveRouteId(state.pathParameters['id']);
          if (id == null) return const _InvalidRoutePage();
          return EventDetailPage(eventId: id);
        },
        routes: [
          GoRoute(
            path: 'chat',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = parsePositiveRouteId(state.pathParameters['id']);
              if (id == null) return const _InvalidRoutePage();
              return EventChatPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'requests',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = parsePositiveRouteId(state.pathParameters['id']);
              if (id == null) return const _InvalidRoutePage();
              return JoinRequestsPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'members',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = parsePositiveRouteId(state.pathParameters['id']);
              if (id == null) return const _InvalidRoutePage();
              return EventMembersPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'edit',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = parsePositiveRouteId(state.pathParameters['id']);
              if (id == null) return const _InvalidRoutePage();
              return CreateEventPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'ticket',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = parsePositiveRouteId(state.pathParameters['id']);
              if (id == null) return const _InvalidRoutePage();
              return EventTicketPage(eventId: id);
            },
          ),
        ],
      ),
      GoRoute(
        path: '/favorites',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const FavoritesPage(),
      ),
      GoRoute(
        path: '/u/:publicId',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final publicId = state.pathParameters['publicId'] ?? '';
          return PublicProfilePage(publicId: publicId);
        },
      ),
      GoRoute(
        path: '/settings/location',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const LocationPage(),
      ),
      GoRoute(
        path: '/settings/profile',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const ProfileEditPage(),
      ),
      GoRoute(
        path: '/identity',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const IdentityPage(),
      ),
      GoRoute(
        path: '/blocks',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const BlocksPage(),
      ),
      GoRoute(
        path: '/report',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const ReportPage(),
      ),
      GoRoute(
        path: '/safety/guidelines',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const SafetyGuidelinesPage(),
      ),
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) {
          return NotificationUnreadListener(
            child: ChatToastListener(
              child: MainShell(navigationShell: navigationShell),
            ),
          );
        },
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/home',
                builder: (context, state) => const HomePage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/discover',
                builder: (context, state) => const DiscoverPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/create',
                builder: (context, state) => const CreateEventPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/my-events',
                builder: (context, state) => const MyEventsPage(),
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/profile',
                builder: (context, state) => const ProfilePage(),
              ),
            ],
          ),
        ],
      ),
    ],
  );
});

bool _isGuestEventShow(String loc) {
  return RegExp(r'^/events/\d+$').hasMatch(loc);
}

/// Create / chat / manage routes blocked until identity docs are submitted.
/// Join is gated on the event detail UI (AUTHZ-JOIN-001); server remains authoritative.
bool _isIdentityBlockedRoute(String loc) {
  if (loc == '/create' || loc == '/chats' || loc == '/report') return true;
  if (RegExp(r'^/events/\d+/(chat|requests|members|edit)$').hasMatch(loc)) {
    return true;
  }
  return false;
}

class _AuthRefresh extends ChangeNotifier {
  _AuthRefresh(this._ref) {
    _ref.listen<AuthState>(authControllerProvider, (_, _) {
      notifyListeners();
    });
  }

  final Ref _ref;
}

class _InvalidRoutePage extends StatelessWidget {
  const _InvalidRoutePage();

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                l10n.invalidRoute,
                textAlign: TextAlign.center,
                style: const TextStyle(fontWeight: FontWeight.w700),
              ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: () => context.go('/discover'),
                child: Text(l10n.discoverTitle),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _SplashPage extends ConsumerStatefulWidget {
  const _SplashPage();

  @override
  ConsumerState<_SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<_SplashPage> {
  String? _restoreError;

  @override
  void initState() {
    super.initState();
    Future.microtask(_boot);
  }

  Future<void> _boot() async {
    setState(() => _restoreError = null);
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

    await ref.read(authControllerProvider.notifier).bootstrap();
    if (!mounted) return;
    final auth = ref.read(authControllerProvider);
    if (auth is AuthSessionRestoreFailed) {
      setState(() => _restoreError = auth.message ?? l10nFallback());
    }
  }

  String l10nFallback() => AppLocalizations.of(context).errorOffline;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    if (_restoreError != null) {
      return Scaffold(
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Text(
                  _restoreError!,
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: AppColors.muted),
                ),
                const SizedBox(height: 16),
                TextButton(onPressed: _boot, child: Text(l10n.retry)),
              ],
            ),
          ),
        ),
      );
    }
    final brand = ref.watch(siteBrandingProvider).maybeWhen(
      data: (s) => s,
      orElse: () => SiteBranding.fallback,
    );

    return Scaffold(
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            SiteBrandMark(
              logoUrl: brand.logoUrl,
              size: 72,
              borderRadius: 18,
            ),
            const SizedBox(height: 16),
            Text(
              brand.displayName,
              style: const TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                letterSpacing: -0.3,
                color: AppColors.text,
              ),
            ),
            const SizedBox(height: 28),
            const FunLoader(size: FunLoaderSize.lg),
          ],
        ),
      ),
    );
  }
}
