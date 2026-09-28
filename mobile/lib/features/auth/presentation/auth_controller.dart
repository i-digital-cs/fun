import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

sealed class AuthState {
  const AuthState();
}

class AuthInitial extends AuthState {
  const AuthInitial();
}

class AuthLoading extends AuthState {
  const AuthLoading();
}

/// Token exists but `/user` failed for a non-auth reason (e.g. network).
/// Token is retained; splash should offer retry (SEC-SESSION-001).
class AuthSessionRestoreFailed extends AuthState {
  const AuthSessionRestoreFailed([this.message]);
  final String? message;
}

class Authenticated extends AuthState {
  const Authenticated(this.user);
  final User user;
}

class Unauthenticated extends AuthState {
  const Unauthenticated([this.message]);
  final String? message;
}

class AuthController extends StateNotifier<AuthState> {
  AuthController(this._ref) : super(const AuthInitial());

  final Ref _ref;

  /// Session ready after a theater login/register, but not yet applied to
  /// [state] — so GoRouter does not tear down the page mid-sequence.
  User? _pendingUser;

  Future<void> bootstrap() async {
    state = const AuthLoading();
    final repo = _ref.read(authRepositoryProvider);
    final hasToken = await repo.hasToken();
    if (!hasToken) {
      state = const Unauthenticated();
      return;
    }

    final result = await repo.me();
    if (result.data != null) {
      state = Authenticated(result.data!);
      return;
    }

    final failure = result.failure;
    // 401 interceptor already cleared storage; treat as logged out.
    if (failure is UnauthorizedFailure || !await repo.hasToken()) {
      state = const Unauthenticated();
      return;
    }

    // Keep token on network/server blips — do not fake logout.
    state = AuthSessionRestoreFailed(failure?.message);
  }

  /// Apply a deferred session after the access theater finishes.
  void commitPendingSession() {
    final user = _pendingUser;
    if (user == null) return;
    _pendingUser = null;
    state = Authenticated(user);
  }

  void discardPendingSession() {
    _pendingUser = null;
  }

  void _applyUser(User user, {required bool commitSession}) {
    if (commitSession) {
      _pendingUser = null;
      state = Authenticated(user);
    } else {
      _pendingUser = user;
    }
  }

  Future<String?> login({
    required String email,
    required String password,
    bool commitSession = true,
  }) async {
    // Stay Unauthenticated while the request runs — AuthLoading would
    // redirect to /splash and kill the access theater.
    final result = await _ref
        .read(authRepositoryProvider)
        .login(email: email, password: password);
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    _applyUser(result.data!, commitSession: commitSession);
    return null;
  }

  Future<String?> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .loginWithGoogle(
          idToken: idToken,
          accessToken: accessToken,
          locale: locale,
        );
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    state = Authenticated(result.data!);
    return null;
  }

  Future<String?> loginWithApple({
    required String idToken,
    String? name,
    String? locale,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .loginWithApple(idToken: idToken, name: name, locale: locale);
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    state = Authenticated(result.data!);
    return null;
  }

  Future<String?> requestAccountDeletion({
    String? password,
    bool providerReauth = false,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .requestAccountDeletion(
          password: password,
          providerReauth: providerReauth,
        );
    if (result.failure != null) {
      return result.failure?.message;
    }
    await logout();
    return null;
  }

  Future<String?> cancelAccountDeletion() async {
    final result = await _ref
        .read(authRepositoryProvider)
        .cancelAccountDeletion();
    if (result.failure != null) {
      return result.failure?.message;
    }
    await refreshMe();
    return null;
  }

  Future<String?> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String sex,
    required String locale,
    bool commitSession = true,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .register(
          name: name,
          email: email,
          password: password,
          passwordConfirmation: passwordConfirmation,
          sex: sex,
          locale: locale,
        );
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    _applyUser(result.data!, commitSession: commitSession);
    return null;
  }

  Future<String?> verifyEmail(String code, {bool commitSession = true}) async {
    final result = await _ref.read(authRepositoryProvider).verifyEmail(code);
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    _applyUser(result.data!, commitSession: commitSession);
    return null;
  }

  Future<String?> resendEmailVerify() async {
    final result = await _ref.read(authRepositoryProvider).resendEmailVerify();
    return result.failure?.message;
  }

  Future<({String? error, String? email})> forgotPassword(String email) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .forgotPassword(email);
    if (result.failure != null) {
      return (error: result.failure!.message, email: null);
    }
    return (error: null, email: result.data);
  }

  Future<({String? error, String? resetToken})> verifyResetCode({
    required String email,
    required String code,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .verifyResetCode(email: email, code: code);
    if (result.failure != null || result.data == null) {
      return (
        error: result.failure?.message ?? 'Invalid code',
        resetToken: null,
      );
    }
    return (error: null, resetToken: result.data);
  }

  Future<String?> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
    bool commitSession = true,
  }) async {
    final result = await _ref
        .read(authRepositoryProvider)
        .resetPassword(
          email: email,
          resetToken: resetToken,
          password: password,
          passwordConfirmation: passwordConfirmation,
        );
    if (result.failure != null || result.data == null) {
      return result.failure?.message;
    }
    _applyUser(result.data!, commitSession: commitSession);
    return null;
  }

  Future<void> logout() async {
    _pendingUser = null;
    await _ref.read(authRepositoryProvider).logout();
    state = const Unauthenticated();
  }

  void markLoggedOut() {
    _pendingUser = null;
    state = const Unauthenticated();
  }

  Future<void> refreshMe() async {
    final current = state;
    if (current is! Authenticated) return;
    final result = await _ref.read(authRepositoryProvider).me();
    if (result.data != null) {
      state = Authenticated(result.data!);
    }
  }
}

final authControllerProvider = StateNotifierProvider<AuthController, AuthState>(
  (ref) {
    return AuthController(ref);
  },
);
