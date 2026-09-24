import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/auth/data/auth_remote_source.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

class AuthRepositoryImpl implements AuthRepository {
  AuthRepositoryImpl({required this._remote, required this._tokenStorage});

  final AuthRemoteSource _remote;
  final TokenStorage _tokenStorage;

  @override
  Future<Result<User>> login({
    required String email,
    required String password,
  }) async {
    final result = await _remote.login(email: email, password: password);
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    await _tokenStorage.write(result.data!.token);
    return (failure: null, data: result.data!.user);
  }

  @override
  Future<Result<User>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async {
    final result = await _remote.loginWithGoogle(
      idToken: idToken,
      accessToken: accessToken,
      locale: locale,
    );
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    await _tokenStorage.write(result.data!.token);
    final me = await _remote.me();
    if (me.data != null) {
      return (failure: null, data: me.data);
    }
    return (failure: null, data: result.data!.user);
  }

  @override
  Future<Result<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String sex,
    required String locale,
  }) async {
    final result = await _remote.register(
      name: name,
      email: email,
      password: password,
      passwordConfirmation: passwordConfirmation,
      sex: sex,
      locale: locale,
    );
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    await _tokenStorage.write(result.data!.token);
    return (failure: null, data: result.data!.user);
  }

  @override
  Future<Result<User>> me() => _remote.me();

  @override
  Future<Result<void>> logout() async {
    final result = await _remote.logout();
    await _tokenStorage.clear();
    if (result.failure != null && result.failure is! UnauthorizedFailure) {
      return (failure: result.failure, data: null);
    }
    return (failure: null, data: null);
  }

  @override
  Future<bool> hasToken() async {
    final token = await _tokenStorage.read();
    return token != null && token.isNotEmpty;
  }

  @override
  Future<Result<User>> verifyEmail(String code) async {
    final result = await _remote.verifyEmail(code);
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    return (failure: null, data: result.data);
  }

  @override
  Future<Result<void>> resendEmailVerify() => _remote.resendEmailVerify();

  @override
  Future<Result<String>> forgotPassword(String email) async {
    final result = await _remote.forgotPassword(email);
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    return (failure: null, data: result.email);
  }

  @override
  Future<Result<String>> verifyResetCode({
    required String email,
    required String code,
  }) async {
    final result = await _remote.verifyResetCode(email: email, code: code);
    if (result.failure != null || result.resetToken == null) {
      return (
        failure: result.failure ?? const ValidationFailure('Invalid code'),
        data: null,
      );
    }
    return (failure: null, data: result.resetToken);
  }

  @override
  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async {
    final result = await _remote.resetPassword(
      email: email,
      resetToken: resetToken,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
    if (result.failure != null) {
      return (failure: result.failure, data: null);
    }
    await _tokenStorage.write(result.data!.token);
    return (failure: null, data: result.data!.user);
  }
}
