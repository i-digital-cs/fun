import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/utils/route_ids.dart';

void main() {
  test('parsePositiveRouteId rejects invalid and zero', () {
    expect(parsePositiveRouteId(null), isNull);
    expect(parsePositiveRouteId(''), isNull);
    expect(parsePositiveRouteId('abc'), isNull);
    expect(parsePositiveRouteId('0'), isNull);
    expect(parsePositiveRouteId('-1'), isNull);
    expect(parsePositiveRouteId('42'), 42);
  });
}
