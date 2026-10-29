# Voice V3 retry / idempotency

## Server

- `POST /api/dms/{conversation}/messages` (and web JSON mirror) looks up `client_uuid` **before** storing media.
- Replay with the same sender + conversation + `client_uuid` returns the existing message (`200`) and does **not** write a second private-disk object.
- Covered by `ChatV2ExtensionsTest::test_voice_retry_reuses_client_uuid_without_duplicating_message_or_media`.

## Client (web)

- On send failure, the composer keeps `voiceDraftFile` (original Blob/File) and the pending bubble’s `client_uuid`.
- Retry reuses that UUID and reseats the draft into the file input.
- Echo `.message.posted` and HTTP confirmation both reconcile by `id` / `client_uuid` (no duplicate bubble).

## Limitation

- **Page reload / new tab** clears the in-memory draft. Recovery after reload is **not** supported; the user must record again.
- Physical Safari iOS / Chrome Android mic+retry remain **pending**.
