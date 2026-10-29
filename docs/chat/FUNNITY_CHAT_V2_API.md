# Chat V2 — API

Existing endpoints remain backward compatible.

## Inbox

`GET /api/chats` — event chat inbox (unchanged).  
`GET /api/dms` — DM conversations (unchanged).

Web unified inbox is Blade-only via `BuildUnifiedInboxService`.

## Send (extensions when migration active)

Optional body fields on:

- `POST /api/events/{event}/messages`
- `POST /api/dms/{conversation}/messages`
- Web POST equivalents

| Field | Type | Notes |
|-------|------|-------|
| `body` | string | Required for text |
| `client_uuid` | string 8–36 | Idempotent retry key |
| `reply_to_id` | int | Same-conversation only |

Retry with the same `client_uuid` returns the original message (no duplicate row).

## Reactions

Event: existing like/dislike.  
DM emoji reactions table ships in migration; API endpoints land with M3 completion after migrate approval.
