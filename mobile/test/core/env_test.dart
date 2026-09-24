import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/config/env.dart';

void main() {
  test('default Env points at production API and prod flavor', () {
    expect(Env.apiBaseUrl, 'https://www.funnity.com/api');
    expect(Env.flavor, 'prod');
    expect(Env.isProd, isTrue);
    expect(Env.usesLocalApi, isFalse);
  });
}
