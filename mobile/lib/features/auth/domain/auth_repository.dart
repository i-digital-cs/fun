import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/features/auth/domain/otp_issue_meta.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

typedef Result<T> = ({Failure? failure, T? data});

class AuthSession {
  const AuthSession({required this.user, this.otp});

  final User user;
  final OtpIssueMeta? otp;
}

class ForgotPasswordResult {
  const ForgotPasswordResult({required this.email, this.otp});

  final String email;
  final OtpIssueMeta? otp;
}

abstract class AuthRepository {
  Future<Result<AuthSession>> login({
    required String email,
    required String password,
  });

  Future<Result<AuthSession>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  });

  Future<Result<AuthSession>> loginWithApple({
    required String idToken,
    String? name,
    String? locale,
  });

  Future<Result<void>> requestAccountDeletion({
    String? password,
    bool providerReauth = false,
  });

  Future<Result<void>> cancelAccountDeletion();

  Future<Result<AuthSession>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String sex,
    required String locale,
  });

  Future<Result<User>> me();

  Future<Result<void>> logout();

  Future<bool> hasToken();

  Future<Result<User>> verifyEmail(String code);

  Future<Result<OtpIssueMeta>> resendEmailVerify();

  Future<Result<ForgotPasswordResult>> forgotPassword(String email);

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
