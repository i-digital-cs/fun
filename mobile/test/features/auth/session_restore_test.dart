import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';

class _FakeAuthRepository implements AuthRepository {
  _FakeAuthRepository({
    this.hasTokenValue = false,
    this.meUser,
    this.meFailure,
    this.tokenCleared = false,
  });

  bool hasTokenValue;
  User? meUser;
  Failure? meFailure;
  bool tokenCleared;

  @override
  Future<bool> hasToken() async => hasTokenValue && !tokenCleared;

  @override
  Future<Result<User>> me() async => (failure: meFailure, data: meUser);

  @override
  Future<Result<void>> logout() async {
    tokenCleared = true;
    hasTokenValue = false;
    return (failure: null, data: null);
  }

  @override
  Future<Result<User>> login({
    required String email,
    required String password,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String locale,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> verifyEmail(String code) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<void>> resendEmailVerify() async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<String>> forgotPassword(String email) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<String>> verifyResetCode({
    required String email,
    required String code,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async =>
      (failure: const ValidationFailure('unused'), data: null);
}

void main() {
  const sampleUser = User(
    id: 1,
    name: 'Test User',
    email: 't@example.com',
    status: 'active',
    locale: 'en',
    emailVerified: true,
    canParticipateFlag: true,
  );

  test('bootstrap network failure keeps session restore failed (token kept)',
      () async {
    final fake = _FakeAuthRepository(
      hasTokenValue: true,
      meFailure: const NetworkFailure(),
    );
    final container = ProviderContainer(
      overrides: [authRepositoryProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await container.read(authControllerProvider.notifier).bootstrap();
    expect(container.read(authControllerProvider), isA<AuthSessionRestoreFailed>());
    expect(await fake.hasToken(), isTrue);
  });

  test('bootstrap unauthorized → Unauthenticated', () async {
    final fake = _FakeAuthRepository(
      hasTokenValue: true,
      meFailure: const UnauthorizedFailure(),
    )..tokenCleared = true;
    final container = ProviderContainer(
      overrides: [authRepositoryProvider.overrideWithValue(fake)],
    );
    addTearDown(container.dispose);

    await container.read(authControllerProvider.notifier).bootstrap();
    expect(container.read(authControllerProvider), isA<Unauthenticated>());
  });

  test('User.canParticipate false for pending_documents', () {
    const pending = User(
      id: 2,
      name: 'B',
      email: 'b@x.com',
      status: 'pending_documents',
      locale: 'en',
      emailVerified: true,
    );
    expect(pending.canParticipate, isFalse);
    expect(pending.needsIdentity, isTrue);
    expect(sampleUser.canParticipate, isTrue);
  });
}
