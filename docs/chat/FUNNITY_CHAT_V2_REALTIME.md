# Chat V2 — Realtime

## Confirmed transport

- Production: **Pusher Channels** via Laravel Broadcasting (`BROADCAST_CONNECTION=pusher`).
- Web: `resources/js/realtime.js` → Laravel Echo.
- Flutter: `ChatRealtimeClient` + `/api/site` broadcast config.
- Auth: `/broadcasting/auth` (web session) and `/api/broadcasting/auth` (Sanctum).

## Channels

| Channel | Auth | Events |
|---------|------|--------|
| `private-events.{id}.chat` | Event members | `message.posted`, `message.reacted` |
| `private-dms.{id}` | Conversation participants + friends | `message.posted` |
| `private-App.Models.User.{id}` | Owner | toasts / DM / chat notifications |

## Reliability notes

- Broadcasts use `ShouldBroadcastNow` (no queue required for socket fan-out).
- Broadcast failures are caught — HTTP send still succeeds.
- Flutter polls (8s / 60s) when socket absent.
- Client UUID idempotency activates after Chat V2 migration.
- **Physical multi-device realtime PASS not claimed** in this delivery — requires owner-approved live verification.
