# Chat V2 — Implementation notes

## Milestone status

| Milestone | Status |
|-----------|--------|
| M1 Architecture + realtime audit + idempotency hooks | Done (schema gated) |
| M2 Web two-panel workspace | Done (Blade + chat-v2.js) |
| M3 Reply / reactions / voice / attachments | Partial — reply+client_uuid wired when migration approved; voice/attachments deferred |
| M4 Flutter Chat V2 | Partial — parity doc updated; full UI rewrite continues |
| M5 Home Mini Map | Done (web); Flutter section pending device verify |

## Web Chat V2

- Route: `GET /chats?c=dm-{id}|event-{id}`
- Controller: `Web\Chat\ChatInboxController`
- Views: `chats/index.blade.php`, `components/chat/panel-*.blade.php`
- JS: `resources/js/chat-v2.js` (+ existing `dm-chat.js` / `event-chat.js`)

Deep links `/dms/{id}` and `/events/.../chat` remain for compatibility.

## Migration (owner approval required)

`database/migrations/2026_10_29_140000_add_chat_v2_message_extensions.php`

Do **not** run on LIVE until approved. Code uses `ChatV2Capabilities` so production keeps working without the new columns.

## Community chat

**Deferred.** No community membership tables exist. Documented gap — not silently invented.
