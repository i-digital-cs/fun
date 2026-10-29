# Chat V2 — Flutter handoff

## Current mobile stack

- Inbox: `features/chat/presentation/chats_page.dart`
- Event thread: `features/events/presentation/event_chat_page.dart`
- DM thread: `features/friends/presentation/dm_chat_page.dart`
- Realtime: `features/chat/data/chat_realtime_client.dart`

## Required parity (in progress)

1. Unified inbox filters: All / Unread / Direct / Events (Communities = soon).
2. Pass `client_uuid` on send once API migration is live.
3. Reply UI + `reply_to_id`.
4. Home mini-map section (~240px) using `DiscoverMapRemote` — lazy, non-blocking.
5. Do not auto-request GPS on Home.

## Web reference

`/chats` two-panel workspace + `/dashboard` Explore Around You map.
