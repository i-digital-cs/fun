import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/chat/domain/message_identity.dart';

void main() {
  group('sameChatIdentity', () {
    test('matches on positive server id', () {
      expect(
        sameChatIdentity(
          aId: 42,
          aClientUuid: 'a',
          bId: 42,
          bClientUuid: 'b',
        ),
        isTrue,
      );
    });

    test('matches on client_uuid when ids differ', () {
      expect(
        sameChatIdentity(
          aId: 0,
          aClientUuid: 'uuid-1',
          bId: 99,
          bClientUuid: 'uuid-1',
        ),
        isTrue,
      );
    });

    test('does not match on empty uuid or body-like coincidence alone', () {
      expect(
        sameChatIdentity(
          aId: 1,
          aClientUuid: '',
          bId: 2,
          bClientUuid: '',
        ),
        isFalse,
      );
      expect(
        sameChatIdentity(
          aId: 1,
          aClientUuid: null,
          bId: 2,
          bClientUuid: null,
        ),
        isFalse,
      );
    });
  });

  group('canAttemptSend', () {
    test('blocks while sending and empty composer', () {
      expect(
        canAttemptSend(
          body: '',
          hasMedia: false,
          hasVoice: false,
          sending: false,
        ),
        isFalse,
      );
      expect(
        canAttemptSend(
          body: 'hi',
          hasMedia: false,
          hasVoice: false,
          sending: true,
        ),
        isFalse,
      );
    });

    test('allows text, image, or voice payloads', () {
      expect(
        canAttemptSend(
          body: 'hi',
          hasMedia: false,
          hasVoice: false,
          sending: false,
        ),
        isTrue,
      );
      expect(
        canAttemptSend(
          body: '',
          hasMedia: true,
          hasVoice: false,
          sending: false,
        ),
        isTrue,
      );
      expect(
        canAttemptSend(
          body: '',
          hasMedia: false,
          hasVoice: true,
          sending: false,
        ),
        isTrue,
      );
    });
  });

  group('upsertMessageByIdentity', () {
    test('replaces pending by client_uuid and sorts by id', () {
      final pending = (id: 0, uuid: 'c-1', body: 'pending');
      final confirmed = (id: 10, uuid: 'c-1', body: 'ok');
      final other = (id: 5, uuid: 'c-2', body: 'other');

      final next = upsertMessageByIdentity(
        existing: [pending, other],
        incoming: confirmed,
        idOf: (m) => m.id,
        clientUuidOf: (m) => m.uuid,
      );

      expect(next.map((m) => m.id).toList(), [5, 10]);
      expect(next.map((m) => m.body).toList(), ['other', 'ok']);
    });

    test('appends when identity is new', () {
      final next = upsertMessageByIdentity(
        existing: [(id: 1, uuid: 'a', body: 'a')],
        incoming: (id: 2, uuid: 'b', body: 'b'),
        idOf: (m) => m.id,
        clientUuidOf: (m) => m.uuid,
      );
      expect(next.length, 2);
      expect(next.last.id, 2);
    });
  });
}
