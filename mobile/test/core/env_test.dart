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
      '702014926680-h8o7h7pih3ttuo1g2abf5aj4ekqeje97.apps.googleusercontent.com',
    );
  });
}
