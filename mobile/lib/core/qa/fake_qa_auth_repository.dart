import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/qa/fake_dms_bus.dart';
import 'package:fun_mobile/core/qa/fake_qa_users.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/domain/otp_issue_meta.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

/// Fake Sanctum session for SAFE_MOCK_MESSAGING. Never hits Production.
class FakeQaAuthRepository implements AuthRepository {
  FakeQaAuthRepository({FakeDmsBus? bus}) : _bus = bus ?? FakeDmsBus.instance;

  final FakeDmsBus _bus;
  User? _user = FakeQaUsers.userA;
  bool _hasToken = true;

  User? get currentUser => _user;

  void _clearPrivateCaches() {
    _bus.clearPrivate();
    _bus.seed();
  }

  @override
  Future<bool> hasToken() async => _hasToken;

  @override
  Future<Result<User>> me() async {
    if (!_hasToken || _user == null) {
      return (failure: const UnauthorizedFailure('Mock session expired'), data: null);
    }
    return (failure: null, data: _user);
  }

  @override
  Future<Result<void>> logout() async {
    _hasToken = false;
    _user = null;
    _clearPrivateCaches();
    return (failure: null, data: null);
  }

  @override
  Future<Result<AuthSession>> login({
    required String email,
    required String password,
  }) async {
    final matched = FakeQaUsers.byEmail(email) ?? FakeQaUsers.userA;
    // Account switch: wipe prior private bus so User B never sees User A drafts.
    _clearPrivateCaches();
    _hasToken = true;
    _user = matched;
    return (failure: null, data: AuthSession(user: matched));
  }

  /// Seed authenticated User A without Production login (SAFE_MOCK boot).
  void ensureSeededSession() {
    _hasToken = true;
    _user ??= FakeQaUsers.userA;
  }

  @override
  Future<Result<AuthSession>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async => login(email: FakeQaUsers.userA.email, password: 'mock');

  @override
  Future<Result<AuthSession>> loginWithApple({
    required String idToken,
    String? name,
    String? locale,
  }) async => login(email: FakeQaUsers.userA.email, password: 'mock');

  @override
  Future<Result<AuthSession>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String sex,
    required String locale,
  }) async => (
    failure: const ValidationFailure('Mock register disabled'),
    data: null,
  );

  @override
  Future<Result<void>> requestAccountDeletion({
    String? password,
    bool providerReauth = false,
  }) async => (failure: const ValidationFailure('Mock only'), data: null);

  @override
  Future<Result<void>> cancelAccountDeletion() async =>
      (failure: null, data: null);

  @override
  Future<Result<User>> verifyEmail(String code) async => me();

  @override
  Future<Result<OtpIssueMeta>> resendEmailVerify() async => (
    failure: const ValidationFailure('Mock OTP disabled — no Production OTP'),
    data: null,
  );

  @override
  Future<Result<ForgotPasswordResult>> forgotPassword(String email) async => (
    failure: const ValidationFailure('Mock forgot-password disabled'),
    data: null,
  );

  @override
  Future<Result<String>> verifyResetCode({
    required String email,
    required String code,
  }) async => (failure: const ValidationFailure('Mock only'), data: null);

  @override
  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async => (failure: const ValidationFailure('Mock only'), data: null);

  /// Simulate expired Sanctum session without Production calls.
  void expireSession() {
    _hasToken = false;
    _user = null;
    _clearPrivateCaches();
  }
}
