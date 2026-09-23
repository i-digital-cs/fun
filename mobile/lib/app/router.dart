import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/features/auth/presentation/account_deactivated_page.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/auth/presentation/login_page.dart';
import 'package:fun_mobile/features/auth/presentation/register_page.dart';
import 'package:fun_mobile/features/discover/presentation/discover_page.dart';
import 'package:fun_mobile/features/events/presentation/create_event_page.dart';
import 'package:fun_mobile/features/events/presentation/event_chat_page.dart';
import 'package:fun_mobile/features/events/presentation/event_detail_page.dart';
import 'package:fun_mobile/features/events/presentation/event_members_page.dart';
import 'package:fun_mobile/features/events/presentation/join_requests_page.dart';
import 'package:fun_mobile/features/events/presentation/my_events_page.dart';
import 'package:fun_mobile/features/home/presentation/home_page.dart';
import 'package:fun_mobile/features/home/presentation/main_shell.dart';
import 'package:fun_mobile/features/identity/presentation/identity_page.dart';
import 'package:fun_mobile/features/profile/presentation/location_page.dart';
import 'package:fun_mobile/features/profile/presentation/profile_page.dart';
import 'package:fun_mobile/features/profile/presentation/public_profile_page.dart';
import 'package:fun_mobile/features/chat/presentation/chats_page.dart';
import 'package:fun_mobile/features/safety/presentation/blocks_page.dart';
import 'package:fun_mobile/features/safety/presentation/report_page.dart';
import 'package:fun_mobile/features/safety/presentation/safety_guidelines_page.dart';
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
      final isGuestBrowse = loc == '/discover' ||
          loc == '/safety/guidelines' ||
          loc.startsWith('/u/') ||
          _isGuestEventShow(loc);

      if (auth is AuthInitial || auth is AuthLoading) {
        return loc == '/splash' ? null : '/splash';
      }

      if (auth is Unauthenticated) {
        if (loc == '/splash') return '/discover';
        if (isAuthRoute || isGuestBrowse) return null;
        return '/login';
      }

      if (auth is Authenticated) {
        final deactivated = auth.user.isDeactivated;
        if (deactivated) {
          if (loc == '/account/deactivated') return null;
          return '/account/deactivated';
        }

        if (loc == '/account/deactivated') {
          return '/home';
        }

        if (loc == '/login' || loc == '/register' || loc == '/splash') {
          return '/home';
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
        ],
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
  final match = RegExp(r'^/events/\d+$').hasMatch(loc);
  return match;
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
    Future.microtask(
      () => ref.read(authControllerProvider.notifier).bootstrap(),
    );
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      body: Center(
        child: CircularProgressIndicator(),
      ),
    );
  }
}
