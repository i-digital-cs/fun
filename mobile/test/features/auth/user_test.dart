import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';

void main() {
  group('User', () {
    test('initials and firstName', () {
      const user = User(
        id: 1,
        name: 'Sara Ali',
        email: 's@example.com',
        status: 'active',
        locale: 'en',
      );
      expect(user.firstName, 'Sara');
      expect(user.initials, 'SA');
    });

    test('status helpers', () {
      const active = User(
        id: 1,
        name: 'A',
        email: 'a@x.com',
        status: 'active',
        locale: 'en',
      );
      const pending = User(
        id: 2,
        name: 'B',
        email: 'b@x.com',
        status: 'pending_documents',
        locale: 'en',
      );
      const deactivated = User(
        id: 3,
        name: 'C',
        email: 'c@x.com',
        status: 'deactivated',
        locale: 'en',
      );

      expect(active.canParticipate, isTrue);
      expect(pending.needsIdentity, isTrue);
      expect(pending.canParticipate, isFalse);
      expect(deactivated.isDeactivated, isTrue);
    });
  });
}
