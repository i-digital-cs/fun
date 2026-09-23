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
import 'package:fun_mobile/core/widgets/fun_loader.dart';
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
import 'package:fun_mobile/features/safety/presentation/blocks_page.dart';
import 'package:fun_mobile/features/safety/presentation/report_page.dart';
import 'package:fun_mobile/features/safety/presentation/safety_guidelines_page.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
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
      final isPasswordResetRoute = loc == '/forgot-password' ||
          loc == '/forgot-password/verify' ||
          loc == '/reset-password';
      final isGuestBrowse = loc == '/discover' ||
          loc == '/safety/guidelines' ||
          loc.startsWith('/u/') ||
          _isGuestEventShow(loc);

      if (auth is AuthInitial || auth is AuthLoading) {
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

        final needsEmail =
            user.needsEmailVerification || !user.emailVerified;
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
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginPage(),
      ),
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
          final q = state.uri.queryParameters;
          return ResetPasswordPage(
            email: q['email'] ?? '',
            resetToken: q['resetToken'] ?? '',
          );
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
        path: '/events/:id',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
          return EventDetailPage(eventId: id);
        },
        routes: [
          GoRoute(
            path: 'chat',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return EventChatPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'requests',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return JoinRequestsPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'members',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return EventMembersPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'edit',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
              return CreateEventPage(eventId: id);
            },
          ),
          GoRoute(
            path: 'ticket',
            parentNavigatorKey: _rootKey,
            builder: (context, state) {
              final id = int.tryParse(state.pathParameters['id'] ?? '') ?? 0;
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
          return ChatToastListener(
            child: MainShell(navigationShell: navigationShell),
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

/// Create / join / chat routes blocked until identity docs are submitted.
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

class _SplashPage extends ConsumerStatefulWidget {
  const _SplashPage();

  @override
  ConsumerState<_SplashPage> createState() => _SplashPageState();
}

class _SplashPageState extends ConsumerState<_SplashPage> {
  @override
  void initState() {
    super.initState();
    Future.microtask(_boot);
  }

  Future<void> _boot() async {
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
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: FunLoader(size: FunLoaderSize.lg),
      ),
    );
  }
}
