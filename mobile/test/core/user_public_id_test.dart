import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/identity/user_public_id.dart';

void main() {
  group('UserPublicId', () {
    test('accepts canonical Funity-F###### ids', () {
      expect(UserPublicId.isValid('Funity-F001115'), isTrue);
      expect(UserPublicId.normalize('Funity-F001115'), 'Funity-F001115');
    });

    test('rejects chat-like / non-canonical ids that caused route 404', () {
      expect(UserPublicId.isValid('chatqaa0cc175b9'), isFalse);
      expect(UserPublicId.normalize('chatqaa0cc175b9'), isNull);
      expect(UserPublicId.normalize(''), isNull);
      expect(UserPublicId.normalize(null), isNull);
    });

    test('normalizes digits and case variants', () {
      expect(UserPublicId.normalize('1115'), 'Funity-F001115');
      expect(UserPublicId.normalize('funity-f1115'), 'Funity-F001115');
    });
  });
}
