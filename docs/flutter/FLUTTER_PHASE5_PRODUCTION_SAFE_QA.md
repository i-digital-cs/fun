# Flutter Phase 5 — Production-Safe Messaging QA

**Date:** 2026-10-31  
**Device:** `bidex_api34` / `emulator-5554` / `com.funnity.app`  
**Branch:** `main` @ `8f09cd1` (+ uncommitted Phases 3–5 Flutter work)  
**Backend:** Production only — **no state-changing E2E against prod**  
**Admin / Laravel:** Not modified  

---

## Verdict: **CONDITIONAL**

| Area | Result |
|------|--------|
| Safe mock harness isolation | **PASS** |
| Mock two-user text messaging | **PASS** (automated) |
| Mock image / voice uploads | **PASS** (automated) |
| Mock auth journeys + logout cleanup | **PASS** (automated) |
| Production read-only GETs | **PASS** (automated) |
| Private endpoints reject anonymous | **PASS** (401/403/404) |
| Guest gates / returnTo hardening | **PASS** (automated) |
| Release-build mock exclusion | **PASS** (code + unit) |
| Emulator SAFE_MOCK UI | **PASS** (banner + mock auth + Direct DM) — see §11 |
| Real authenticated Production messaging | **BLOCKED** (no staging; policy forbids writes) |
| Full release readiness | **CONDITIONAL** |

Mock PASS ≠ live Production messaging PASS.

---

## 1. Git baseline

| Item | Value |
|------|-------|
| HEAD | `8f09cd13c64b4a3f69f53af2b5e53b27260a197f` |
| Branch | `main` |
| Commit / push | Not performed |
| Preserved dirty tree | Guest, typography, Chat Phase 4, Phase 5 harness |

---

## 2. Test harness architecture

### Activation (explicit only)

```powershell
flutter build apk --debug `
  --dart-define=SAFE_MOCK_MESSAGING=true `
  --dart-define=BUILD_PROOF=phase5
```

### Components

| Piece | Location | Role |
|-------|----------|------|
| `SafeMock` | `lib/core/qa/safe_mock.dart` | Compile-time flag; inactive unless define set **and** not release |
| `FakeDmsBus` | `lib/core/qa/fake_dms_bus.dart` | In-memory DM transport (User A ↔ B) |
| `FakeDmsRepository` | `lib/core/qa/fake_dms_repository.dart` | Implements real `DmsRepository` |
| `FakeQaAuthRepository` | `lib/core/qa/fake_qa_auth_repository.dart` | Fake Sanctum session; OTP/forgot disabled |
| `FakeQaUsers` | `lib/core/qa/fake_qa_users.dart` | Deterministic User A / User B |
| Wire-up | `lib/main.dart` | Overrides auth + DMS **only** when `SafeMock.isActive` |
| UI marker | `FunApp` banner + Chats inbox strip | **SAFE MOCK** label |

Default builds (no define) use Production API for network calls; messaging write E2E is still forbidden by policy.

### Isolation

- `Env.assertSafeForRelease()` throws if `SAFE_MOCK_MESSAGING=true` in release.
- `SafeMock.assertIsolatedFromRelease()` same guard.
- Fake auth refuses OTP / forgot-password (no Production OTP).
- Fake bus never opens sockets or HTTP.

---

## 3. Files changed (Phase 5)

| Path | Change |
|------|--------|
| `mobile/lib/core/qa/*` | Safe mock harness |
| `mobile/lib/main.dart` | Conditional overrides |
| `mobile/lib/app/app.dart` | SAFE MOCK corner banner |
| `mobile/lib/features/chat/presentation/chats_page.dart` | Inbox mock strip |
| `mobile/lib/core/config/env.dart` | Release rejects SAFE_MOCK define |
| `mobile/test/harness/*` | Messaging / auth / upload / prod-readonly / isolation tests |
| `docs/flutter/FLUTTER_PHASE5_PRODUCTION_SAFE_QA.md` | This report |
| `docs/flutter/phase5_safe_mock_qa/*` | Emulator evidence |
| `docs/FLUTTER_WEB_PARITY.md` | §10.34 Phase 5 note |

---

## 4. Mock messaging results

| Scenario | Status |
|----------|--------|
| User A → User B text | **PASS** |
| User B reply | **PASS** |
| Single bubble / ordering | **PASS** |
| `client_uuid` idempotency | **PASS** |
| Failed send + retry | **PASS** |
| Unread + markRead | **PASS** |
| History on reopen | **PASS** |
| Identity upsert collapse | **PASS** |

---

## 5. Mock image results

| Scenario | Status |
|----------|--------|
| Local jpeg fixture upload fields | **PASS** |
| `media_kind=image`, mime jpeg | **PASS** |
| Media bytes fetch | **PASS** |
| Upload failure → no bytes | **PASS** |
| `UploadValidator` MIME/size | **PASS** |
| Fullscreen preview (Phase 4 UI) | Preserved (code) |

No Production media uploads.

---

## 6. Mock voice results

| Scenario | Status |
|----------|--------|
| `media_kind=voice`, `audio/mp4`, `duration_ms` | **PASS** |
| `<500ms` rejected | **PASS** |
| Exclusive playback coordinator | **PASS** (Phase 4 tests) |
| Live mic on device | Not required for mock harness |

No Production voice uploads.

---

## 7. Authentication results

| Scenario | Status |
|----------|--------|
| Fake login → `/chats`, `/events/:id`, public profile returnTo | **PASS** |
| External returnTo rejected | **PASS** |
| Logout clears bus + token | **PASS** |
| Account switch clears private bus | **PASS** |
| Expired session → no `me` | **PASS** |
| Guest routes gated | **PASS** |
| Mock OTP / forgot disabled | **PASS** |
| Join remains manual post-login | **PASS** |

No real OTP / account creation on Production.

---

## 8. Production read-only checks

Executed via `test/harness/prod_readonly_api_test.dart` against `https://funnity.com/api`:

| Call | Expectation | Result |
|------|-------------|--------|
| `GET /site` | 200 branding | **PASS** |
| `GET /events?per_page=5` | 200 public list | **PASS** |
| `GET /dms` (no token) | 401/403 | **PASS** |
| `GET /dms/media/1` (no token) | 401/403/404 | **PASS** |
| `GET /events/1/messages` (no token) | 401/403/404 | **PASS** |

No POST/PUT/PATCH/DELETE.

---

## 9. Security verification

| Check | Status |
|-------|--------|
| Guest cannot browse `/chats` / `/dms` | **PASS** |
| Anonymous private media/chat GETs denied | **PASS** |
| `returnTo` blocks external URLs | **PASS** |
| Account switch wipes mock private state | **PASS** |
| Fake sessions excluded from release | **PASS** |
| No token logging added | **PASS** |

---

## 10. Automated test totals

```text
flutter pub get
flutter analyze   # harness: 1 info (prefer_initializing_formals)
flutter test      # 171/171 passed
```

Baseline was 144. Phase 5 harness added production-safe coverage → **171**.

---

## 11. Emulator evidence

Build / install:

```text
flutter build apk --debug --dart-define=SAFE_MOCK_MESSAGING=true --dart-define=BUILD_PROOF=phase5
adb install -r build/app/outputs/flutter-apk/app-debug.apk
```

Logcat proof: `FUNNITY_BUILD_PROOF=phase5 … safe_mock=true`

Evidence directory: `docs/flutter/phase5_safe_mock_qa/`

| Asset | Intent |
|-------|--------|
| `04_monkey.png` / `06_after_chat_tap.png` | SAFE MOCK ribbon + Arabic guest/auth shell |
| `07_chats_or_alt.png` / `15_chats_direct.png` | Mock User A (“Test” / TA) authenticated Home |
| `11_chats.png` / `17_chats.png` | Inbox strip: “SAFE MOCK — in-memory messaging only” |
| `24_direct.png` | Direct list → **Test User B** (no Production DMs) |
| `25_dm_thread.png` / `26_dm_sent.png` | DM thread + composer with `HelloFromMockA` |
| `27_composer.xml` | Accessibility tree includes sent bubble `HelloFromMockA` + timestamp |

**Important:** SAFE_MOCK success is **in-memory**. It must not be reported as Production messaging success.

Notifications still hit Production when opened (401 Unauthorized) — expected; event inbox uses `FakeChatsRepository` empty list.

---

## 12. Release-build isolation

| Guard | Status |
|-------|--------|
| Default `SAFE_MOCK_MESSAGING=false` | **PASS** |
| Default `API_BASE_URL=https://funnity.com/api` | **PASS** |
| Release + local API → throws | Existing |
| Release + SAFE_MOCK → throws | **PASS** (new) |
| versionCode / versionName | Unchanged |
| Production AAB | Not generated |

---

## 13. Remaining real E2E gaps

1. Authenticated DM/event chat against a **non-prod** backend (does not exist yet).
2. Physical device mic / camera / FCM tray.
3. Staging environment + disposable fixtures (recommended before full PASS).

---

## 14. Final verdict

**CONDITIONAL** — highest confidence achievable without risking Production users/data.

Ship readiness for store messaging claims remains blocked until a staging (or approved fixture) environment exists for authenticated write E2E.
