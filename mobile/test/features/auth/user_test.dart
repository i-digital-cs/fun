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

    test('fromJson parses sex and needs_sex', () {
      final withSex = User.fromJson({
        'id': 12,
        'name': 'Sara',
        'email': 'sara@example.com',
        'status': 'active',
        'locale': 'en',
        'sex': 'female',
        'needs_sex': false,
      });
      expect(withSex.sex, 'female');
      expect(withSex.needsSex, isFalse);

      final googleUnset = User.fromJson({
        'id': 13,
        'name': 'Google User',
        'email': 'g@example.com',
        'status': 'active',
        'locale': 'en',
        'sex': null,
        'needs_sex': true,
      });
      expect(googleUnset.sex, isNull);
      expect(googleUnset.needsSex, isTrue);
    });

    test('fromJson infers needsSex when sex missing and flag omitted', () {
      final user = User.fromJson({
        'id': 14,
        'name': 'No Sex',
        'email': 'n@example.com',
        'status': 'active',
        'locale': 'en',
      });
      expect(user.sex, isNull);
      expect(user.needsSex, isTrue);
    });
  });
}
