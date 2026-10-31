# Flutter Chat Phase 4 QA — Messaging Stabilization

**Date:** 2026-10-31  
**Device:** `bidex_api34` / `emulator-5554` / `com.funnity.app`  
**Branch:** `main` @ `8f09cd1` (+ uncommitted guest/typography + Phase 4 chat fixes)  
**API:** `https://funnity.com` (read-only inspection; no backend edits)  
**Admin / Laravel / migrations:** Not modified  

---

## Verdict: **CONDITIONAL**

| Area | Result | Evidence |
|------|--------|----------|
| Architecture audit | **PASS** | Inventory below |
| Text send/receive hardening | **PASS** (code + unit) | Identity upsert + send guard |
| Image messaging UX | **PASS** (code) | DM + event fullscreen preview |
| Voice record/play hardening | **PASS** (code + unit) | Min 500ms; exclusive playback |
| Conversation list / unread | **PASS** (code) | Existing inbox; logout invalidate |
| Real-time (Pusher + poll) | **PASS** (code) | No new stack; disconnect on logout |
| Guest chat → Login | **PASS** (emulator) | Screenshots `chat_phase4_qa/02_*` |
| Arabic after chat gate | **PASS** (emulator) | `04_login_ar.png` |
| Logout private cleanup | **PASS** (code) | Disconnect + invalidate lists + badges |
| Live authenticated send/receive/image/voice | **BLOCKED** | No approved non-prod account; no prod message writes |
| Automated tests | **PASS** | **144/144** |
| Release readiness | **CONDITIONAL** | Client stabilizations ready; live E2E messaging deferred |

Do **not** treat as full **PASS** until authenticated messaging is verified on a non-production account.

---

## 1. Git baseline

| Item | Value |
|------|-------|
| HEAD | `8f09cd13c64b4a3f69f53af2b5e53b27260a197f` |
| Branch | `main` |
| Commit / push | Not performed (awaiting approval) |
| Parallel work | Admin Dashboard V2.1 untouched |

---

## 2. Architecture inventory

Transport (inspected, not assumed): **Pusher Channels / Reverb** via `dart_pusher_channels` (`ChatRealtimeClient`) + **HTTP poll fallback** (inbox ~15s / thread ~8–60s depending on socket health). FCM is tray/background only; in-app toasts prefer Pusher.

| Component | Responsibility | Data source | Current behavior | Defect / risk | Fix applied |
|-----------|----------------|-------------|------------------|---------------|-------------|
| `chats_page.dart` | Event + DM inbox, filters, unread | `GET /api/chats`, `GET /api/dms` | Recent/Unread; navigation to threads | Logout left cached lists | Invalidate on logout |
| `dm_chat_page.dart` | 1:1 thread | `GET/POST /api/dms/{id}/…`, private media | Text/image/voice; live + poll | Live dedupe by id only; multi voice; short clips; no image lightbox | Identity upsert; send guard; coordinator; min duration; preview |
| `event_chat_page.dart` | Event thread | `GET/POST /api/events/{id}/messages…` | Same media model | Same class of defects | Same hardening |
| `message_identity.dart` | Dedup / send gate | N/A | Shared helpers | New | `sameChatIdentity`, `canAttemptSend`, `upsertMessageByIdentity` |
| `voice_playback_coordinator.dart` | Exclusive playback | Local | Single active stopper claim | New | Stops other registered players |
| `chat_realtime_client.dart` | User + event + DM private channels | `/api/site` broadcast + `/api/broadcasting/auth` | Subscribe/unsubscribe streams | Logout did not disconnect | `disconnect()` from topbar logout |
| `dash_topbar.dart` | Chat icon + logout | Unread providers | Guest → login `returnTo=/chats` | Incomplete logout teardown | Disconnect + invalidate |
| Composer / `record` / `audioplayers` | Capture AAC-LC `.m4a`, play bytes | Multipart `media` + `media_kind` | Matches backend Voice V3 contract | Accidental &lt;500ms clips | Soft-drop short recordings |
| Auth / `requireAuth` | Guest gate | Router allowlist | `/chats`, `/dms/:id`, event chat not guest-browsable | — | Preserved; tests added |

Message states used: pending optimistic bubble → server message with `id` + `client_uuid`. **No fabricated delivered/read receipts.**

---

## 3. Root causes addressed

1. Live/poll merge could leave duplicate bubbles when optimistic `id=0` met confirmed row sharing `client_uuid`.
2. Composer could re-enter send while `_sending` under racey taps.
3. Multiple voice bubbles could play at once.
4. Sub-500ms “taps” became voice drafts.
5. DM (and event) images lacked fullscreen preview.
6. Logout cleared badges but left realtime socket + inbox provider cache.

---

## 4. Files changed (Phase 4 messaging)

| Path | Change |
|------|--------|
| `mobile/lib/features/chat/domain/message_identity.dart` | **New** identity helpers |
| `mobile/lib/features/chat/presentation/voice_playback_coordinator.dart` | **New** exclusive playback |
| `mobile/lib/features/friends/presentation/dm_chat_page.dart` | Upsert, send guard, voice, preview, failures |
| `mobile/lib/features/events/presentation/event_chat_page.dart` | Same |
| `mobile/lib/features/home/presentation/widgets/dash_topbar.dart` | Logout disconnect + invalidate |
| `mobile/test/features/chat/*` | Identity, guest gate, voice coordinator, RTL |
| `docs/FLUTTER_WEB_PARITY.md` | §10.33 Chat Phase 4 |
| `docs/flutter/FLUTTER_CHAT_PHASE4_QA.md` | This report |
| `docs/flutter/chat_phase4_qa/*` | Emulator evidence |

Guest/typography files remain dirty from prior phases; not re-scoped here.

---

## 5. Text messaging

| Check | Status |
|-------|--------|
| Compose + send guard while sending | Implemented + unit (`canAttemptSend`) |
| Optimistic → confirm via `client_uuid` / id | Implemented + unit (`upsertMessageByIdentity`) |
| Failure snack via `failureUserMessage` | Implemented |
| Stable id dedupe (not body/time) | Implemented |
| Live authenticated send/receive | **Not verified** (no non-prod account) |
| Offline queue | Not added (architecture does not safely support it) |

---

## 6. Image messaging

| Check | Status |
|-------|--------|
| Picker + upload via existing repos | Preserved |
| Auth-gated private media bytes | Preserved |
| Fullscreen InteractiveViewer preview | Implemented (DM + event) |
| Live upload/preview on device | **Not verified** |

---

## 7. Voice messaging

| Check | Status |
|-------|--------|
| AAC-LC `.m4a` via `record` | Preserved (backend-compatible) |
| Pause/resume/stop recording UI | Preserved |
| Min duration 500ms | Implemented |
| Exclusive playback | Implemented + unit |
| Mic permission denial UX | Preserved existing path |
| Live record/play on emulator | **Not verified** (would write to prod if authenticated) |

---

## 8. Conversation list

| Check | Status |
|-------|--------|
| Ordering / preview / unread / empty/error | Preserved existing |
| Guest cannot open inbox content | Emulator: chat icon → Login |
| Logout invalidates `chatsListProvider` / `dmsListProvider` | Implemented |

---

## 9. Real-time

| Check | Status |
|-------|--------|
| Pusher private user / `events.{id}.chat` / `dms.{id}` | Preserved |
| Poll fallback | Preserved |
| Logout `disconnect()` | Implemented |
| New realtime dependency | **Not added** |

---

## 10. Authentication / security

| Check | Status |
|-------|--------|
| Guest `/chats`, `/dms/:id`, `/events/:id/chat` not browsable | Unit + router allowlist |
| Guest chat icon → `/login?returnTo=/chats` | Emulator PASS |
| No auto-send after login | Preserved (`requireAuth` only navigates) |
| Private media stays authenticated byte fetch | Preserved |
| Tokens / private bodies not logged in toasts | Preserved SEC-PRIV-002 behavior |

---

## 11. UI/UX

- Brand colors / light mode / Tajawal + IBM Plex preserved.
- Image lightbox (DM + event).
- Safer failure messaging.
- Arabic login after chat gate verified on emulator (`04_login_ar.png`).
- Full authenticated Arabic thread UI: **not live-verified**.

---

## 12. Automated tests

```text
flutter pub get
flutter analyze   # 15 pre-existing infos/warnings; none introduced by chat helpers
flutter test      # 144/144 passed
```

Prior baseline was 133. Phase 3 guest + typography + Phase 4 chat tests raised the suite to **144**.

New chat-focused tests:

- `test/features/chat/message_identity_test.dart`
- `test/features/chat/guest_chat_gate_test.dart`
- `test/features/chat/voice_playback_coordinator_test.dart`
- `test/features/chat/chat_rtl_layout_test.dart`

---

## 13. Emulator screenshots

Directory: `docs/flutter/chat_phase4_qa/`

| File | Scenario |
|------|----------|
| `01_guest_home.png` | Guest Home (browse-first intact) |
| `02_guest_chat_login.png` | Guest taps chat → Login |
| `04_login_ar.png` | Arabic login after chat gate |

**Not captured (policy):** authenticated send/receive/image/voice against production users.

---

## 14. Backend dependencies

No blocking client-side contract gaps found for text/image/voice already documented in parity (Voice V3 / `client_uuid` / private media).

**`docs/flutter/CHAT_BACKEND_HANDOFF.md`:** not created — no backend defect requiring Dashboard-complete handoff.

Optional future (non-blocking): document explicit max voice duration server-side alignment with client clamp `120000` ms if product wants a hard API error instead of client soft-gate.

---

## 15. Remaining defects / gaps

1. Live authenticated messaging E2E on non-prod accounts.
2. Emulator mic/image flows under auth.
3. Physical device Safari iOS / Chrome Android still **NOT VERIFIED** (carried from Voice V3 notes).
4. Pre-existing `flutter analyze` infos outside chat scope left untouched.

---

## 16. Release readiness

**CONDITIONAL** — ship client stabilizations after review; gate “messaging production ready” on a non-prod authenticated E2E pass.

Checklist for that pass:

- [ ] Login with fixture account  
- [ ] Open DM + event chat  
- [ ] Send/receive text  
- [ ] Send image + preview  
- [ ] Record/play voice  
- [ ] Unread badge updates  
- [ ] Logout clears inbox UI for next session  
