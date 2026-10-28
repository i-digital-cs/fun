import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/config/env.dart';

void main() {
  test('publicSiteOrigin strips trailing /api from default API base', () {
    // Default dart-define is https://funnity.com/api
    expect(Env.publicSiteOrigin, 'https://funnity.com');
  });
}
