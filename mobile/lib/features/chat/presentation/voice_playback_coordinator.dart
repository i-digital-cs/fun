/// Ensures at most one in-thread voice bubble plays at a time.
class VoicePlaybackCoordinator {
  VoicePlaybackCoordinator._();

  static final VoicePlaybackCoordinator instance = VoicePlaybackCoordinator._();

  final Map<Object, Future<void> Function()> _stoppers = {};

  void register(Object key, Future<void> Function() stop) {
    _stoppers[key] = stop;
  }

  void unregister(Object key) {
    _stoppers.remove(key);
  }

  Future<void> claim(Object key) async {
    final others = _stoppers.entries.where((e) => e.key != key).toList();
    for (final entry in others) {
      try {
        await entry.value();
      } catch (_) {
        // Best-effort stop; playback UI recovers on next interaction.
      }
    }
  }
}
