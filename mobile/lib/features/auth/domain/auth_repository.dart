import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

typedef Result<T> = ({Failure? failure, T? data});

abstract class AuthRepository {
  Future<Result<User>> login({
    required String email,
    required String password,
  });

  Future<Result<User>> loginWithGoogle({
    String? idToken,
    String? accessToken,
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

  Future<Result<User>> verifyEmail(String code);

  Future<Result<void>> resendEmailVerify();

  Future<Result<String>> forgotPassword(String email);

  Future<Result<String>> verifyResetCode({
    required String email,
    required String code,
  });

  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  });
}
