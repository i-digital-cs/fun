import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/core/storage/token_storage.dart';
import 'package:fun_mobile/features/auth/data/auth_remote_source.dart';
import 'package:fun_mobile/features/auth/data/auth_repository_impl.dart';

class _MemoryTokenStorage implements TokenStorage {
  String? value;

  @override
  Future<void> clear() async => value = null;

  @override
  Future<String?> read() async => value;

  @override
  Future<void> write(String token) async => value = token;
}

class _LogoutOnlyRemote extends AuthRemoteSource {
  _LogoutOnlyRemote(ApiClient client) : super(client);

  bool called = false;

  @override
  Future<({Failure? failure, void data})> logout() async {
    called = true;
    return (failure: null, data: null);
  }
}

class _UnauthorizedLogoutRemote extends AuthRemoteSource {
  _UnauthorizedLogoutRemote(ApiClient client) : super(client);

  @override
  Future<({Failure? failure, void data})> logout() async {
    return (failure: const UnauthorizedFailure(), data: null);
  }
}

void main() {
  test('AuthRepositoryImpl.logout clears token even when remote succeeds',
      () async {
    final storage = _MemoryTokenStorage()..value = 'secret';
    final client = ApiClient(
      tokenStorage: storage,
      locale: 'en',
      onUnauthorized: () {},
    );
    final remote = _LogoutOnlyRemote(client);
    final repo = AuthRepositoryImpl(remote: remote, tokenStorage: storage);

    final result = await repo.logout();

    expect(result.failure, isNull);
    expect(remote.called, isTrue);
    expect(storage.value, isNull);
  });

  test('AuthRepositoryImpl.logout clears token when remote returns 401',
      () async {
    final storage = _MemoryTokenStorage()..value = 'stale';
    final client = ApiClient(
      tokenStorage: storage,
      locale: 'en',
      onUnauthorized: () {},
    );
    final remote = _UnauthorizedLogoutRemote(client);
    final repo = AuthRepositoryImpl(remote: remote, tokenStorage: storage);

    final result = await repo.logout();

    expect(result.failure, isNull);
    expect(storage.value, isNull);
  });
}
