import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/config/env.dart';

void main() {
  test('default Env points at production API and prod flavor', () {
    expect(Env.apiBaseUrl, 'https://funnity.com/api');
    expect(Env.flavor, 'prod');
    expect(Env.isProd, isTrue);
    expect(Env.usesLocalApi, isFalse);
    expect(
      Env.googleServerClientId,
      '51496053622-4fu1e7u5v06cuf2omc0i39r4smn9ottl.apps.googleusercontent.com',
    );
  });
}
