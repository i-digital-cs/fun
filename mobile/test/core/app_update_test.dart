import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/site/domain/app_update.dart';

void main() {
  group('compareSemver', () {
    test('orders versions', () {
      expect(compareSemver('1.0.0', '1.0.1'), lessThan(0));
      expect(compareSemver('1.2.0', '1.1.9'), greaterThan(0));
      expect(compareSemver('1.0.0', '1.0.0'), 0);
    });
  });
}
