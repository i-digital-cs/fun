# Unified Web Chat — Audit & Implementation

**Date:** 2026-10-31  
**Branch / worktree:** `feature/unify-web-chat` @ `/home/d14098/funnity-chat-unify`  
**Production:** `/home/d14098/funnity.com` — **DEPLOYED** (see `UNIFIED_CHAT_PRODUCTION_DEPLOYMENT.md`)  
**Reference UX:** `https://funnity.com/chats?c=dm-{id}` (Chat V2)

---

## 1. ROOT CAUSE

Funnity shipped **Chat V2** (`GET /chats`, two-panel inbox + `panel-direct` / `panel-event` with voice, images, replies) while leaving the earlier **standalone** screens live:

| Era | Surface |
|---|---|
| Legacy DM | `GET /dms/{conversation}` → `dms/show.blade.php` (text-only composer) |
| Legacy event | `GET /events/{id}/{slug}/chat` → `events/chat.blade.php` (older single-thread page) |
| Modern | `GET /chats?c=dm-{id}\|event-{id}` → `chats/index.blade.php` + modern panels |

Both eras share the **same** backend (DM + event message tables, `dms.store`, `events.chat.store`, media streams, Echo channels). The split was a **web UI migration incomplete**: inbox links already pointed at Chat V2, but profile/friends/event CTAs and notification deep links still opened the legacy pages.

Community chat was never built (no membership tables) — documented gap only.

---

## 2. LEGACY CHAT INVENTORY

| URL / route name | Controller | View / JS | Role after change |
|---|---|---|---|
| `GET /dms/{id}` `dms.show` | `Web\Friends\DirectMessageController@show` | was `dms/show` + text-only | **302 →** `/chats?c=dm-{id}` |
| `GET /dms/with/{user}` `dms.open` | `…@open` | redirect | **302 →** `/chats?c=dm-{id}` (create-or-find) |
| `POST /dms/{id}` `dms.store` | `…@store` | used by Chat V2 panel | **Kept** (JSON + form) |
| `GET /dms/media/{message}` | media stream | — | **Kept** |
| `GET /events/{id}/{slug}/chat` `events.chat` | `Web\Events\EventChatController@show` | was `events/chat` | **302 →** `/chats?c=event-{id}` after `authorize('chat')` |
| `POST …/chat` `events.chat.store` | `…@store` | panel posts here | **Kept**; non-JSON redirect → Chat V2 |
| react / reactions / media | same controller | — | **Kept** |
| `GET /events/{id}/chat` | closure 301 | was → slug chat | still → `events.chat` (then V2) |
| Admin `admin…/events/{id}/chat` | moderation | admin only | **Unchanged** (admin, not member UX) |

**Entry points previously opening legacy UI**

| Entry | Before | After |
|---|---|---|
| Header / sidebar / navbar Messages | `/chats` | `/chats` (unchanged) |
| Profile → Message | `dms.open` → `dms.show` | `dms.open` → **Chat V2 DM** |
| Friends → Message | `dms.open` → `dms.show` | `dms.open` → **Chat V2 DM** |
| Event show → Open chat | `events.chat` page | **Chat V2 event** |
| Participations cards | `events.chat` page | **Chat V2 event** |
| Notification / broadcast `chat_url` | legacy paths | **Chat V2** helpers |
| Inbox row links | already `?c=dm-` / `?c=event-` | unchanged |

Conversation keys (unchanged): `dm-{numericId}`, `event-{numericId}`.

---

## 3. CANONICAL CHAT

**Single source of truth (web member UX):**

- Route: `chats.index` → `GET /chats`
- Selection: `?c=dm-{conversationId}` or `?c=event-{eventId}`
- Controller: `App\Http\Controllers\Web\Chat\ChatInboxController`
- Views: `chats/index.blade.php`, `components/chat/panel-direct.blade.php`, `components/chat/panel-event.blade.php`
- JS: `chat-v2.js` + `dm-chat.js` / `event-chat.js`

Helpers (new):

- `chat_dm_url($conversation)` → `/chats?c=dm-{id}`
- `chat_event_url($event)` → `/chats?c=event-{id}`

Legacy Blade pages (`dms/show`, `events/chat`) remain in the tree for rollback but are **no longer rendered** by GET handlers.

---

## 4. ENTRY POINTS UPDATED

- `DirectMessageController::show` / `open` / HTML `store` redirect  
- `EventChatController::show` / HTML `store` redirect  
- `DirectMessageService` conversation `url` + notify `chatUrl`  
- `Api\Friends\DirectMessageController::open` `url`  
- `PostEventMessageService` notify URL  
- `ListUserChatsService` `chat_url`  
- `MessageReactedNotification` / `UserMessageReacted`  
- Event show + home/community participations links  

---

## 5. FEATURE PARITY (Chat V2 panels)

| Feature | DM panel | Event panel | Notes |
|---|---|---|---|
| Text | Yes | Yes | Same APIs |
| Images | Yes | Yes | Capability-gated columns may apply (`ChatV2Capabilities`) |
| Voice record/play | Yes | Yes | Same |
| Fullscreen image | Link/open media URL | Same | Native browser / target=_blank (not a separate lightbox app) |
| Timestamps | Yes | Yes | |
| Read/unread | Inbox + markRead | Inbox + markRead | |
| Realtime | Echo `dms.{id}` | Echo `events.{id}.chat` | |
| History | Yes | Yes | |
| Mobile web | Dashboard chat workspace | Same | |

Gaps **not** invented: community chat.

---

## 6. BACKEND COMPATIBILITY / FLUTTER

- **No schema changes.**  
- **No API path changes** for message CRUD (`/api/dms/…`, `/api/events/{id}/messages`).  
- Optional JSON field `url` / `chat_url` for **web** deep links now points at `/chats?c=…`. Flutter apps that ignore web URLs are unaffected.  
- Handoff: `docs/flutter/UNIFIED_CHAT_BACKEND_HANDOFF.md`

---

## 7. TEST RESULTS

```text
php artisan test --filter='UnifiedChatRedirectTest|ChatV2InboxTest|EventChatTest|ChatInboxTest'
Tests: 20 passed (88 assertions)
```

Covered: legacy DM/event redirects, `dms.open`, unauthorized event chat forbidden, Chat V2 panels, API event chat regression.

Browser E2E voice/image upload **not** re-run in this pass (existing Chat V2 panels unchanged).

---

## 8. FILES CHANGED

- `app/Support/helpers.php` — `chat_dm_url`, `chat_event_url`
- `app/Http/Controllers/Web/Friends/DirectMessageController.php`
- `app/Http/Controllers/Web/Events/EventChatController.php`
- `app/Http/Controllers/Api/Friends/DirectMessageController.php`
- `app/Services/Friends/DirectMessageService.php`
- `app/Services/Events/Chat/PostEventMessageService.php`
- `app/Services/Events/Chat/ListUserChatsService.php`
- `app/Notifications/MessageReactedNotification.php`
- `app/Events/UserMessageReacted.php`
- `resources/views/events/show.blade.php`
- `resources/views/components/community/participations.blade.php`
- `resources/views/components/home/participations.blade.php`
- `resources/js/chat-toast.js` *(toast fallbacks → `/chats?c=…`)*
- `tests/Feature/UnifiedChatRedirectTest.php` *(new)*
- `tests/Feature/EventChatTest.php`
- `docs/web/UNIFIED_CHAT_AUDIT_AND_IMPLEMENTATION.md`
- `docs/flutter/UNIFIED_CHAT_BACKEND_HANDOFF.md`

---

## 9. DEPLOYMENT STATUS

**DEPLOYED** to production (2026-10-31). Dirty Marketing/OTP/FCM tree preserved; `routes/web.php` not overwritten. Details: `docs/web/UNIFIED_CHAT_PRODUCTION_DEPLOYMENT.md`.

---

## 10. FINAL VERDICT

**PASS** (production).
