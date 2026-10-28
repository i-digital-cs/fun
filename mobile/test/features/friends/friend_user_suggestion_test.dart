import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';

void main() {
  test('parses people suggestion row without coordinates', () {
    final user = FriendUser.fromSuggestionRow({
      'user': {
        'id': 9,
        'public_id': 'F001009',
        'name': 'Maya',
        'initials': 'M',
        'profile_image_path': null,
        'city': {'id': 1, 'name': 'Cairo'},
      },
      'friendship': {
        'status': null,
        'direction': null,
        'can_request': true,
        'can_message': false,
      },
    });

    expect(user.id, 9);
    expect(user.publicId, 'F001009');
    expect(user.cityName, 'Cairo');
    expect(user.friendship.canRequest, isTrue);

    final encoded = user.toString();
    expect(encoded.contains('latitude'), isFalse);
  });
}
