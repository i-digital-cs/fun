import 'package:flutter_riverpod/flutter_riverpod.dart';
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

  Future<void> bootstrap() async {
    state = const AuthLoading();
    final repo = _ref.read(authRepositoryProvider);
    final hasToken = await repo.hasToken();
    if (!hasToken) {
      state = const Unauthenticated();
      return;
    }

    final result = await repo.me();
    if (result.failure != null || result.data == null) {
      state = const Unauthenticated();
      return;
    }
    state = Authenticated(result.data!);
  }

  Future<String?> login({
    required String email,
    required String password,
  }) async {
    state = const AuthLoading();
    final result = await _ref.read(authRepositoryProvider).login(
          email: email,
          password: password,
        );
    if (result.failure != null || result.data == null) {
      state = Unauthenticated(result.failure?.message);
      return result.failure?.message;
    }
    state = Authenticated(result.data!);
    return null;
  }

  Future<String?> loginWithGoogle({
    required String accessToken,
    String? locale,
  }) async {
    state = const AuthLoading();
    final result = await _ref.read(authRepositoryProvider).loginWithGoogle(
          accessToken: accessToken,
          locale: locale,
        );
    if (result.failure != null || result.data == null) {
      state = Unauthenticated(result.failure?.message);
      return result.failure?.message;
    }
    state = Authenticated(result.data!);
    return null;
  }

  Future<String?> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String locale,
  }) async {
    state = const AuthLoading();
    final result = await _ref.read(authRepositoryProvider).register(
          name: name,
          email: email,
          password: password,
          passwordConfirmation: passwordConfirmation,
          locale: locale,
        );
    if (result.failure != null || result.data == null) {
      state = Unauthenticated(result.failure?.message);
      return result.failure?.message;
    }
    state = Authenticated(result.data!);
    return null;
  }

  Future<void> logout() async {
    await _ref.read(authRepositoryProvider).logout();
    state = const Unauthenticated();
  }

  void markLoggedOut() {
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

final authControllerProvider =
    StateNotifierProvider<AuthController, AuthState>((ref) {
  return AuthController(ref);
});
