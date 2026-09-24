import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/presentation/create_event_controller.dart';

class _FakeAuthRepository implements AuthRepository {
  _FakeAuthRepository({this.hasTokenValue = false, this.meUser});

  bool hasTokenValue;
  User? meUser;

  @override
  Future<bool> hasToken() async => hasTokenValue;

  @override
  Future<Result<User>> me() async => (failure: null, data: meUser);

  @override
  Future<Result<void>> logout() async => (failure: null, data: null);

  @override
  Future<Result<User>> login({
    required String email,
    required String password,
  }) async => (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> loginWithGoogle({
    String? idToken,
    String? accessToken,
    String? locale,
  }) async => (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> register({
    required String name,
    required String email,
    required String password,
    required String passwordConfirmation,
    required String locale,
  }) async => (failure: const ValidationFailure('unused'), data: null);

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
  }) async => (failure: const ValidationFailure('unused'), data: null);

  @override
  Future<Result<User>> resetPassword({
    required String email,
    required String resetToken,
    required String password,
    required String passwordConfirmation,
  }) async => (failure: const ValidationFailure('unused'), data: null);
}

void main() {
  test(
    'ARCH-GOD-CREATE-001 bootstrap gates identity when !canParticipate',
    () async {
      final container = ProviderContainer(
        overrides: [
          authRepositoryProvider.overrideWithValue(
            _FakeAuthRepository(
              hasTokenValue: true,
              meUser: const User(
                id: 1,
                name: 'Pending',
                email: 'p@example.com',
                status: 'pending_documents',
                locale: 'en',
              ),
            ),
          ),
        ],
      );
      addTearDown(container.dispose);
      await container.read(authControllerProvider.notifier).bootstrap();

      final result = await container
          .read(createEventControllerProvider(0).notifier)
          .bootstrap();
      expect(result.gate, CreateGate.needIdentity);
    },
  );

  test('ARCH-GOD-CREATE-001 create mode uses eventId 0', () {
    final container = ProviderContainer(
      overrides: [
        authRepositoryProvider.overrideWithValue(_FakeAuthRepository()),
      ],
    );
    addTearDown(container.dispose);
    final ctrl = container.read(createEventControllerProvider(0).notifier);
    expect(ctrl.isEdit, isFalse);
    expect(
      container.read(createEventControllerProvider(9).notifier).isEdit,
      isTrue,
    );
  });
}
