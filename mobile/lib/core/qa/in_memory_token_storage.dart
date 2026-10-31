import 'package:fun_mobile/core/storage/token_storage.dart';

/// Non-persistent token for SAFE_MOCK_MESSAGING only.
class InMemoryTokenStorage implements TokenStorage {
  InMemoryTokenStorage({String? initialToken}) : _token = initialToken;

  String? _token;

  @override
  Future<String?> read() async => _token;

  @override
  Future<void> write(String token) async {
    _token = token;
  }

  @override
  Future<void> clear() async {
    _token = null;
  }
}
