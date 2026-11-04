# Flutter handoff — Unified web chat (2026-10-31)

**Flutter code changes from this task:** None required.  
**API message CRUD:** Unchanged.

## What changed (web / optional deep-link fields)

Web member UI now always uses Chat V2:

- DM: `/chats?c=dm-{conversationId}`
- Event: `/chats?c=event-{eventId}`

JSON fields that previously echoed **web** URLs now use those paths:

| Field | Where | Notes |
|---|---|---|
| `url` | DM conversation list / `POST /api/dms/with/{user}` | Web deep link only |
| `chat_url` | Event chat list / push-related payloads | Web deep link only |
| `api_path` | DM open response | Still `/dms/{id}/messages` |

Flutter should continue using Sanctum API endpoints (`/api/dms/…`, `/api/events/{id}/messages`, `/api/chats`). Do **not** parse web `/chats?c=` unless you intentionally open an in-app WebView.

## Authorization

Unchanged. Event chat still requires `chat` policy; DMs still require friendship / conversation membership. Unauthorized legacy web routes still return 403 before redirect.

## Message types

Text / image / voice / file — same as existing Chat V2 / mobile contracts. Schema extensions remain gated by `ChatV2Capabilities` until migration approved.
