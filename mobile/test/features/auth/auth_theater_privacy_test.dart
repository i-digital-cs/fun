import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/auth/presentation/widgets/auth_access_theater.dart';

void main() {
  test('password digit source never reveals password characters', () {
    final digits = AuthAccessTheater.digitsFrom(
      source: AuthDigitSource.password,
      password: 'Secret123',
    );
    expect(digits, everyElement(anyOf('', '•')));
    expect(digits, isNot(contains('S')));
    expect(digits, isNot(contains('e')));
  });
}
