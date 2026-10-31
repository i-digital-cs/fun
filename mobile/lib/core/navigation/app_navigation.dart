import 'package:flutter/widgets.dart';
import 'package:fun_mobile/core/identity/user_public_id.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:go_router/go_router.dart';

/// Shared navigation helpers (Funnity V2.1 contract).
///
/// Verbs:
/// - **push** — drill-in (detail, chat, settings, overlays)
/// - **replace** — same-level swap (login↔register; chat→event without duplicate)
/// - **go** — shell tab roots, logout, post-auth landing, invalid fallback only
/// - **pop** — dialogs/sheets; AppBar back when [GoRouter.canPop]
///
/// See `docs/audits/FUNNITY_V2_1_NAVIGATION_CONTRACT.md`.

/// Resolve where to land after a successful login/register commit.
///
/// Honors allowlisted [returnTo] (deep links). Never forces `/home` when a
/// safe return path exists. Email/identity gates keep [returnTo] chained.
String resolvePostAuthLocation(User user, {String? returnTo}) {
  final safe = sanitizeReturnTo(returnTo);
  if (user.isDeactivated) return '/account/deactivated';
  if (user.needsEmailVerification || !user.emailVerified) {
    return locationWithReturnTo('/email/verify', returnTo: safe);
  }
  if (user.needsIdentity) {
    return locationWithReturnTo('/identity', returnTo: safe);
  }
  return safe ?? user.routeAfterAuth;
}

/// Explicit post-auth navigation (complements GoRouter [redirect]).
///
/// Call after [AuthController.commitPendingSession] or social login success
/// so the user leaves `/login` even if a refresh race delays redirect.
///
/// Prefer [goAfterSuccessfulAuthWithRouter] when commit may dispose the page
/// (Android redirect race): capture [GoRouter] + [returnTo] **before** commit.
void goAfterSuccessfulAuth(BuildContext context, User user) {
  final returnTo = GoRouterState.of(context).uri.queryParameters['returnTo'];
  goAfterSuccessfulAuthWithRouter(
    GoRouter.of(context),
    user,
    returnTo: returnTo,
  );
}

/// Same as [goAfterSuccessfulAuth] but does not require a mounted [BuildContext].
void goAfterSuccessfulAuthWithRouter(
  GoRouter router,
  User user, {
  String? returnTo,
}) {
  router.go(resolvePostAuthLocation(user, returnTo: returnTo));
}

/// Paths guests may browse without auth (keep in sync with [router.dart]).
bool isGuestBrowsableLocation(String location) {
  final path = Uri.tryParse(location)?.path ?? location;
  if (path == '/discover' ||
      path == '/safety/guidelines' ||
      path == '/privacy-policy' ||
      path == '/terms' ||
      path == '/support' ||
      path == '/account-deletion') {
    return true;
  }
  if (path.startsWith('/u/')) return true;
  return RegExp(r'^/events/\d+$').hasMatch(path);
}

/// Allowlisted in-app return paths after login / gates.
///
/// Rejects schemes, hosts, `//`, and unknown roots.
String? sanitizeReturnTo(String? raw) {
  if (raw == null) return null;
  var value = raw.trim();
  if (value.isEmpty) return null;

  // Decode once if double-encoded query param.
  try {
    if (value.contains('%')) {
      value = Uri.decodeComponent(value);
    }
  } catch (_) {
    return null;
  }

  if (value.startsWith('//') ||
      value.contains('://') ||
      value.contains('\\') ||
      value.contains('\n') ||
      value.contains('\r') ||
      // Reject traversal before Uri normalizes `/u/../events/1` → `/events/1`.
      value.contains('..')) {
    return null;
  }

  if (!value.startsWith('/')) return null;

  final uri = Uri.tryParse(value);
  if (uri == null || uri.hasScheme || uri.host.isNotEmpty) return null;

  final path = uri.path;
  if (path.isEmpty || path == '/' || path.contains('..')) return null;

  const blocked = {
    '/login',
    '/register',
    '/splash',
    '/forgot-password',
    '/reset-password',
    '/email/verify',
  };
  if (blocked.contains(path) || path.startsWith('/forgot-password')) {
    return null;
  }

  const allowedExact = {
    '/home',
    '/discover',
    '/create',
    '/my-events',
    '/profile',
    '/chats',
    '/friends',
    '/notifications',
    '/favorites',
    '/identity',
    '/blocks',
    '/report',
    '/settings/location',
    '/settings/profile',
    '/settings/notifications',
    '/settings/delete-account',
    '/safety/guidelines',
    '/privacy-policy',
    '/terms',
    '/support',
    '/account-deletion',
    '/account/deactivated',
  };

  if (allowedExact.contains(path)) {
    return uri.hasQuery ? '$path?${uri.query}' : path;
  }

  if (path.startsWith('/u/')) {
    final id = UserPublicId.normalize(Uri.decodeComponent(path.substring(3)));
    if (id != null) {
      final safePath = '/u/$id';
      return uri.hasQuery ? '$safePath?${uri.query}' : safePath;
    }
  }

  if (RegExp(r'^/dms/\d+$').hasMatch(path)) {
    return path;
  }

  if (RegExp(r'^/events/\d+$').hasMatch(path) ||
      RegExp(r'^/events/\d+/(chat|requests|members|edit|ticket)$')
          .hasMatch(path)) {
    return path;
  }

  if (path == '/friends/search') {
    return path;
  }

  return null;
}

/// Build `/login` with optional allowlisted [returnTo].
String loginLocation({String? returnTo}) {
  final safe = sanitizeReturnTo(returnTo);
  if (safe == null) return '/login';
  return '/login?returnTo=${Uri.encodeComponent(safe)}';
}

/// Append or preserve `returnTo` on a gate path (`/email/verify`, `/identity`).
String locationWithReturnTo(String path, {String? returnTo, Uri? from}) {
  final fromQuery = from?.queryParameters['returnTo'];
  final candidate = sanitizeReturnTo(returnTo) ??
      sanitizeReturnTo(fromQuery) ??
      (from != null && !path.startsWith(from.path)
          ? sanitizeReturnTo(from.toString())
          : null);
  if (candidate == null) return path;
  final sep = path.contains('?') ? '&' : '?';
  return '$path${sep}returnTo=${Uri.encodeComponent(candidate)}';
}

/// Shell tab path for [StatefulNavigationShell] branch index.
String shellPathForBranch(int index) {
  return switch (index) {
    0 => '/home',
    1 => '/discover',
    2 => '/create',
    3 => '/my-events',
    4 => '/profile',
    _ => '/home',
  };
}

/// Open an allowlisted in-app path (FCM / deep link).
///
/// Prefer [push] when the shell is already showing; otherwise [go] then [push]
/// is unnecessary — [go] to the path is enough for root overlays.
void openAppPath(BuildContext context, String path) {
  final safe = sanitizeReturnTo(path) ?? sanitizeReturnTo(Uri.tryParse(path)?.path);
  if (safe == null) {
    context.go('/home');
    return;
  }
  final router = GoRouter.of(context);
  final loc = router.routerDelegate.currentConfiguration.uri.toString();
  if (loc == safe || loc.startsWith('$safe?')) return;
  // Root overlays and shell destinations: go is correct for cold start;
  // push when already under shell so Back returns.
  final isShell = const {
    '/home',
    '/discover',
    '/create',
    '/my-events',
    '/profile',
  }.contains(Uri.parse(safe).path);
  if (isShell) {
    context.go(safe);
    return;
  }
  if (router.canPop() ||
      loc.startsWith('/home') ||
      loc.startsWith('/discover') ||
      loc.startsWith('/create') ||
      loc.startsWith('/my-events') ||
      loc.startsWith('/profile')) {
    context.push(safe);
    return;
  }
  context.go(safe);
}

/// Opens `/u/{publicId}` only when [publicId] matches Laravel `Funity-F######`.
/// Returns `false` when the id is missing/invalid (no navigation).
bool pushPublicProfile(BuildContext context, String? publicId) {
  final id = UserPublicId.normalize(publicId);
  if (id == null) return false;
  context.push('/u/$id');
  return true;
}
