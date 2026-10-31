import 'dart:io';

import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/core/qa/fake_dms_bus.dart';
import 'package:fun_mobile/core/qa/fake_dms_repository.dart';
import 'package:fun_mobile/core/qa/fake_qa_users.dart';
import 'package:fun_mobile/features/chat/domain/message_identity.dart';

void main() {
  late FakeDmsBus bus;
  late FakeDmsRepository userA;
  late FakeDmsRepository userB;

  setUp(() {
    bus = FakeDmsBus()..seed();
    userA = FakeDmsRepository(
      bus: bus,
      viewerId: () => FakeQaUsers.userA.id,
    );
    userB = FakeDmsRepository(
      bus: bus,
      viewerId: () => FakeQaUsers.userB.id,
    );
  });

  test('two-user text round-trip with single bubbles and ordering', () async {
    final sendA = await userA.postMessage(
      1,
      'Hello from A',
      clientUuid: 'uuid-a-1',
    );
    expect(sendA.failure, isNull);
    expect(sendA.data?.body, 'Hello from A');

    var forB = await userB.messages(1);
    expect(forB.data.map((m) => m.body), ['Hello from A']);
    expect(forB.data.length, 1);

    final unreadBefore = await userB.list();
    expect(unreadBefore.unreadTotal, 1);

    await userB.markRead(1);
    final reply = await userB.postMessage(
      1,
      'Reply from B',
      clientUuid: 'uuid-b-1',
    );
    expect(reply.failure, isNull);

    final history = await userA.messages(1);
    expect(history.data.map((m) => m.body).toList(), [
      'Hello from A',
      'Reply from B',
    ]);
    expect(history.data.map((m) => m.id).toList(), [1, 2]);

    // Re-open preserves history.
    final again = await userA.messages(1);
    expect(again.data.length, 2);
  });

  test('duplicate client_uuid does not create a second message', () async {
    await userA.postMessage(1, 'Once', clientUuid: 'same-uuid');
    await userA.postMessage(1, 'Once', clientUuid: 'same-uuid');
    final msgs = await userA.messages(1);
    expect(msgs.data.length, 1);
    expect(msgs.data.single.clientUuid, 'same-uuid');
  });

  test('failed send is recoverable and does not leave a server row', () async {
    bus.failNextPost = true;
    final failed = await userA.postMessage(
      1,
      'Will fail',
      clientUuid: 'fail-1',
    );
    expect(failed.failure, isNotNull);
    expect(failed.data, isNull);
    expect((await userA.messages(1)).data, isEmpty);

    final ok = await userA.postMessage(1, 'Retry ok', clientUuid: 'fail-1');
    expect(ok.failure, isNull);
    expect((await userA.messages(1)).data.length, 1);
  });

  test('optimistic upsert + live confirm collapses to one bubble', () {
    final pending = (
      id: 0,
      uuid: 'c-live',
      body: 'pending',
    );
    final confirmed = (
      id: 44,
      uuid: 'c-live',
      body: 'pending',
    );
    final next = upsertMessageByIdentity(
      existing: [pending],
      incoming: confirmed,
      idOf: (m) => m.id,
      clientUuidOf: (m) => m.uuid,
    );
    expect(next.length, 1);
    expect(next.single.id, 44);
  });

  test('image upload records media_kind and mime-compatible path', () async {
    final dir = await Directory.systemTemp.createTemp('fun_mock_img');
    final file = File('${dir.path}/shot.jpg');
    await file.writeAsBytes(List<int>.filled(64, 7));

    final result = await userA.postMessage(
      1,
      '',
      clientUuid: 'img-1',
      mediaPath: file.path,
      mediaKind: 'image',
    );
    expect(result.failure, isNull);
    expect(result.data?.messageType, 'image');
    expect(result.data?.mediaMime, 'image/jpeg');
    expect(bus.lastPostFields?['media_kind'], 'image');
    expect(bus.lastPostFields?['media_path'], file.path);

    final bytes = await userB.media(result.data!.id);
    expect(bytes.failure, isNull);
    expect(bytes.data, isNotEmpty);

    await dir.delete(recursive: true);
  });

  test('image upload failure leaves no media bytes', () async {
    bus.failNextPost = true;
    final result = await userA.postMessage(
      1,
      '',
      clientUuid: 'img-fail',
      mediaPath: '/tmp/missing.jpg',
      mediaKind: 'image',
    );
    expect(result.failure, isNotNull);
    expect(bus.media(1), isNull);
  });

  test('voice upload structure uses audio/mp4 and duration_ms', () async {
    final dir = await Directory.systemTemp.createTemp('fun_mock_voice');
    final file = File('${dir.path}/voice.m4a');
    await file.writeAsBytes(List<int>.filled(32, 1));

    final result = await userA.postMessage(
      1,
      '',
      clientUuid: 'voice-1',
      mediaPath: file.path,
      mediaKind: 'voice',
      durationMs: 1500,
    );
    expect(result.failure, isNull);
    expect(result.data?.messageType, 'voice');
    expect(result.data?.mediaMime, 'audio/mp4');
    expect(result.data?.mediaDurationMs, 1500);
    expect(bus.lastPostFields?['duration_ms'], 1500);

    await dir.delete(recursive: true);
  });

  test('voice shorter than 500ms is rejected', () async {
    final result = await userA.postMessage(
      1,
      '',
      clientUuid: 'voice-short',
      mediaPath: '/tmp/v.m4a',
      mediaKind: 'voice',
      durationMs: 200,
    );
    expect(result.failure, isNotNull);
    expect((await userA.messages(1)).data, isEmpty);
  });

  test('missing media returns NotFound-style failure', () async {
    final missing = await userA.media(999);
    expect(missing.failure, isNotNull);
    expect(missing.data, isNull);
  });

  test('unread clears after markRead for viewer', () async {
    await userA.postMessage(1, 'ping', clientUuid: 'u1');
    expect((await userB.list()).unreadTotal, 1);
    await userB.markRead(1);
    expect((await userB.list()).unreadTotal, 0);
    expect((await userA.list()).unreadTotal, 0);
  });
}
