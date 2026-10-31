import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/qa/safe_mock.dart';

void main() {
  test('default build keeps SafeMock inactive and prod API', () {
    expect(SafeMock.messagingEnabled, isFalse);
    expect(SafeMock.isActive, isFalse);
    expect(Env.apiBaseUrl, 'https://funnity.com/api');
    expect(Env.flavor, 'prod');
    expect(Env.usesLocalApi, isFalse);
  });

  test('assertSafeForRelease is a no-op for default debug/test config', () {
    expect(Env.assertSafeForRelease, returnsNormally);
    expect(SafeMock.assertIsolatedFromRelease, returnsNormally);
  });
}
