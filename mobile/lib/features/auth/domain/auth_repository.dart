import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

typedef Result<T> = ({Failure? failure, T? data});

abstract class AuthRepository {
  Future<Result<User>> login({
    required String email,
    required String password,
  });

  Future<Result<User>> loginWithGoogle({
    required String accessToken,
    String? locale,
  });

  Future<Result<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String locale,
  });

  Future<Result<User>> me();

  Future<Result<void>> logout();

  Future<bool> hasToken();
}
