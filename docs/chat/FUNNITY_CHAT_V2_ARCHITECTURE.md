# Funnity Chat V2 — Architecture

## Existing findings (audit)

| Area | Finding |
|------|---------|
| Transport | Laravel Broadcasting + **Pusher** (prod). Optional Reverb config present. Echo on web; `dart_pusher_channels` on Flutter. |
| Event chat | `event_messages` + `event_chat_reads` + `message_reactions` (like/dislike) |
| Direct chat | `direct_conversations` / `direct_messages` / `direct_conversation_reads` |
| Community chat | **Not implemented** (no community membership model) |
| Realtime | `ShouldBroadcastNow` events; FCM for offline; poll fallback on Flutter |
| Idempotency (legacy) | 10s body hash cache only |
| Gaps vs V2 | Narrow single-column UI; no reply/voice/attachments; DM ≠ event UX |

## Chat V2 target

- Unified **conversation-first** workspace (web two-panel; Flutter full-screen thread).
- Shared bubble / composer patterns.
- Optional schema extensions (client UUID, reply, media, DM emoji reactions) via migration `2026_10_29_140000_add_chat_v2_message_extensions` — **not activated on LIVE until owner approval**.
- Capability gates: `App\Services\Chat\ChatV2Capabilities`.

## Services

| Service | Role |
|---------|------|
| `BuildUnifiedInboxService` | Merge event + DM inbox rows |
| `DirectMessageService` | DM auth, send (optional client_uuid/reply), list, read |
| `PostEventMessageService` | Event send (optional client_uuid/reply) |
| Existing react / list / mark-read | Unchanged contracts |

## Security

- DM requires friendship + not blocked (`assertCanMessage`).
- Event chat requires membership + published/completed + not blocked vs host.
- Private media paths planned for voice/attachments (not public disk).
- Do not log message bodies.
