import 'package:flutter_test/flutter_test.dart';
import 'package:fun_mobile/features/chat/presentation/voice_playback_coordinator.dart';

void main() {
  test('claim stops other registered players', () async {
    final stopped = <Object>[];
    final coord = VoicePlaybackCoordinator.instance;

    coord.register('a', () async {
      stopped.add('a');
    });
    coord.register('b', () async {
      stopped.add('b');
    });

    await coord.claim('a');
    expect(stopped, ['b']);

    stopped.clear();
    await coord.claim('b');
    expect(stopped, ['a']);

    coord.unregister('a');
    coord.unregister('b');
  });
}
