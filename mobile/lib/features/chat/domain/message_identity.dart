/// Stable identity for chat/DM bubbles (server id and/or client_uuid).
///
/// Dedup must not rely on body text or timestamps alone.
bool sameChatIdentity({
  required int aId,
  required String? aClientUuid,
  required int bId,
  required String? bClientUuid,
}) {
  if (aId > 0 && aId == bId) return true;
  final a = aClientUuid?.trim();
  final b = bClientUuid?.trim();
  if (a != null && a.isNotEmpty && a == b) return true;
  return false;
}

/// Whether the composer may attempt a send.
bool canAttemptSend({
  required String body,
  required bool hasMedia,
  required bool hasVoice,
  required bool sending,
}) {
  if (sending) return false;
  return body.trim().isNotEmpty || hasMedia || hasVoice;
}

/// Upsert [incoming] into [existing] using [sameChatIdentity].
List<T> upsertMessageByIdentity<T>({
  required List<T> existing,
  required T incoming,
  required int Function(T) idOf,
  required String? Function(T) clientUuidOf,
}) {
  final inId = idOf(incoming);
  final inUuid = clientUuidOf(incoming);
  var found = false;
  final next = <T>[];
  for (final m in existing) {
    if (sameChatIdentity(
      aId: idOf(m),
      aClientUuid: clientUuidOf(m),
      bId: inId,
      bClientUuid: inUuid,
    )) {
      next.add(incoming);
      found = true;
    } else {
      next.add(m);
    }
  }
  if (!found) next.add(incoming);
  next.sort((a, b) => idOf(a).compareTo(idOf(b)));
  return next;
}
