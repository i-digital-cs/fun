import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/features/auth/presentation/account_deactivated_page.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/forgot_password_page.dart';
import 'package:fun_mobile/features/auth/presentation/login_page.dart';
import 'package:fun_mobile/features/auth/presentation/register_page.dart';
import 'package:fun_mobile/features/auth/presentation/reset_password_page.dart';
import 'package:fun_mobile/features/auth/presentation/verify_email_page.dart';
import 'package:fun_mobile/features/auth/presentation/verify_reset_code_page.dart';
import 'package:fun_mobile/core/utils/route_ids.dart';
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
import 'package:fun_mobile/features/friends/presentation/dm_chat_page.dart';
import 'package:fun_mobile/features/friends/presentation/friends_page.dart';
import 'package:fun_mobile/features/profile/presentation/notification_settings_page.dart';
import 'package:fun_mobile/features/notifications/presentation/notifications_page.dart';
import 'package:fun_mobile/features/account/presentation/delete_account_page.dart';
import 'package:fun_mobile/features/legal/presentation/legal_web_page.dart';
import 'package:fun_mobile/features/legal/presentation/privacy_policy_page.dart';
import 'package:fun_mobile/features/safety/presentation/blocks_page.dart';
import 'package:fun_mobile/features/safety/presentation/report_page.dart';
import 'package:fun_mobile/features/safety/presentation/safety_guidelines_page.dart';
import 'package:fun_mobile/features/splash/presentation/splash_page.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

final _rootKey = GlobalKey<NavigatorState>();

/// Root navigator for overlays (events, friends, settings). Exposed for FCM.
GlobalKey<NavigatorState> get appRootNavigatorKey => _rootKey;

/// GoRouter is created **once** per ProviderContainer lifetime.
/// Auth changes notify via [refreshListenable] only — do not `ref.watch` auth
/// here (NAV-F8 / P0-00).
final routerProvider = Provider<GoRouter>((ref) {
  final refresh = _AuthRefresh(ref);

  return GoRouter(
    navigatorKey: _rootKey,
    initialLocation: '/splash',
    refreshListenable: refresh,
    redirect: (context, state) {
      final auth = ref.read(authControllerProvider);
      final loc = state.matchedLocation;
      final isAuthRoute = loc == '/login' || loc == '/register';
      final isPasswordResetRoute =
          loc == '/forgot-password' ||
          loc == '/forgot-password/verify' ||
          loc == '/reset-password';
      final isGuestBrowse = isGuestBrowsableLocation(loc) ||
          isGuestBrowsableLocation(state.uri.path);

      if (auth is AuthInitial || auth is AuthLoading) {
        return loc == '/splash' ? null : '/splash';
      }

      // Network blip while restoring session: keep splash UI for retry, but do
      // not block already-opened public browse routes.
      if (auth is AuthSessionRestoreFailed) {
        if (loc == '/splash') return null;
        if (isGuestBrowse || isAuthRoute || isPasswordResetRoute) return null;
        return '/splash';
      }

      if (auth is Unauthenticated) {
        if (loc == '/splash') return guestLandingLocation;
        if (isAuthRoute || isPasswordResetRoute || isGuestBrowse) return null;
        return _loginRedirect(state);
      }

      if (auth is Authenticated) {
        final user = auth.user;
        final returnTo = sanitizeReturnTo(state.uri.queryParameters['returnTo']);

        if (user.isDeactivated) {
          if (loc == '/account/deactivated') return null;
          return '/account/deactivated';
        }

        if (loc == '/account/deactivated') {
          return _postGateLocation(user.routeAfterAuth, returnTo);
        }

        final needsEmail = user.needsEmailVerification || !user.emailVerified;
        if (needsEmail) {
          if (loc == '/email/verify') return null;
          return locationWithReturnTo(
            '/email/verify',
            returnTo: returnTo,
            from: state.uri,
          );
        }

        if (loc == '/email/verify') {
          if (user.needsIdentity) {
            return locationWithReturnTo(
              '/identity',
              returnTo: returnTo,
              from: state.uri,
            );
          }
          return _postGateLocation(user.routeAfterAuth, returnTo);
        }

        if (user.needsIdentity) {
          if (_isIdentityBlockedRoute(loc)) {
            return locationWithReturnTo(
              '/identity',
              returnTo: returnTo,
              from: state.uri,
            );
          }
          if (loc == '/login' ||
              loc == '/register' ||
              loc == '/splash' ||
              isPasswordResetRoute) {
            return locationWithReturnTo(
              user.routeAfterAuth,
              returnTo: returnTo,
              from: state.uri,
            );
          }
          return null;
        }

        if (loc == '/identity') {
          return _postGateLocation('/home', returnTo);
        }

        if (loc == '/login' ||
            loc == '/register' ||
            loc == '/splash' ||
            isPasswordResetRoute) {
          return _postGateLocation(user.routeAfterAuth, returnTo);
        }
      }

      return null;
    },
    routes: [
      GoRoute(
        path: '/splash',
        builder: (context, state) => const SplashPage(),
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
        builder: (context, state) {
          final tab = state.uri.queryParameters['tab'];
          return ChatsPage(openDirectTab: tab == 'direct');
        },
      ),
      GoRoute(
        path: '/friends',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final search = state.uri.queryParameters['search'];
          final tab = state.uri.queryParameters['tab'];
          return FriendsPage(
            focusSearch: search == '1',
            openRequestsTab: tab == 'requests',
          );
        },
        routes: [
          GoRoute(
            path: 'search',
            parentNavigatorKey: _rootKey,
            builder: (context, state) => const FriendsPage(focusSearch: true),
          ),
        ],
      ),
      GoRoute(
        path: '/dms/:conversationId',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final id = parsePositiveRouteId(state.pathParameters['conversationId']);
          if (id == null) return const _InvalidRoutePage();
          return DmChatPage(conversationId: id);
        },
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
        path: '/settings/notifications',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const NotificationSettingsPage(),
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
      GoRoute(
        path: '/privacy-policy',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const PrivacyPolicyPage(),
      ),
      GoRoute(
        path: '/terms',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final l10n = AppLocalizations.of(context);
          return LegalWebPage(
            title: l10n.termsOfService,
            path: '/terms',
            blurb: l10n.termsBlurb,
          );
        },
      ),
      GoRoute(
        path: '/support',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final l10n = AppLocalizations.of(context);
          return LegalWebPage(
            title: l10n.support,
            path: '/support',
            blurb: l10n.supportBlurb,
          );
        },
      ),
      GoRoute(
        path: '/account-deletion',
        parentNavigatorKey: _rootKey,
        builder: (context, state) {
          final l10n = AppLocalizations.of(context);
          return LegalWebPage(
            title: l10n.accountDeletionHelp,
            path: '/account-deletion',
            blurb: l10n.deleteAccountIntro(30),
          );
        },
      ),
      GoRoute(
        path: '/settings/delete-account',
        parentNavigatorKey: _rootKey,
        builder: (context, state) => const DeleteAccountPage(),
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

String _loginRedirect(GoRouterState state) {
  final existing = state.uri.queryParameters['returnTo'];
  if (existing != null) {
    return loginLocation(returnTo: existing);
  }
  final candidate = state.uri.toString();
  return loginLocation(returnTo: candidate);
}

String _postGateLocation(String fallback, String? returnTo) {
  return returnTo ?? fallback;
}

/// Create / chat / manage routes blocked until identity docs are submitted.
/// Join is gated on the event detail UI (AUTHZ-JOIN-001); server remains authoritative.
bool _isIdentityBlockedRoute(String loc) {
  if (loc == '/create' ||
      loc == '/chats' ||
      loc == '/friends' ||
      loc.startsWith('/dms/') ||
      loc == '/report') {
    return true;
  }
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
