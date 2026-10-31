# Discover Map Functional Repair + Public Profile / Error Handling

**Date:** 2026-10-30  
**Repo:** `C:\xampp\htdocs\Funity\fun`  
**Flutter app:** `mobile/` (`com.funnity.app`)  
**Emulator:** `emulator-5554` (bidex_api34)  
**API:** `https://funnity.com/api`  
**Overall verdict:** **CONDITIONAL**

This document covers both:

1. Discover map / event discovery functional repair (prior task).
2. Public profile API repair and application-wide safe error handling (extension).

---

## 1. Discover map — status (prior work)

| Area | Status | Notes |
|------|--------|-------|
| Egypt framing / camera flash | Fixed earlier | `initialCameraFit` Egypt; no Africa→Egypt flash |
| Header overflow / Create-in-toolbar | Fixed | Parity with web chrome patterns |
| Event markers / Explore V4 | Working on emulator | Markers for Egyptian cities observed |
| New Cairo focus | Conditionally OK | Prior session; re-check if city list selection drifts |
| Gray map tiles | Not a code defect | Slow OSM tile fetch on emulator; tiles eventually load |
| Fresh-install proof | Verified | `FUNNITY_BUILD_PROOF` + `lastUpdateTime` |

See also: `docs/flutter/WEB_FLUTTER_UI_PARITY_AUDIT.md`, `docs/flutter/WINDOWS_ANDROID_EMULATOR_QA.md`.

---

## 2. Public profile — root cause

### Observed defect

Opening a user public profile on Android showed a blank/error surface with raw Laravel text:

> `The route api/users/chatqaa0cc175b9 could not be found.`

### Flow traced

```
UI tap / deep route `/u/{id}`
  → PublicProfilePage(publicId)
  → publicProfileRepository.show(publicId)
  → PublicProfileRemoteSource → Dio GET /users/{id}
  → Laravel Route::get('/users/{publicId}') with where('publicId', UserPublicId::routePattern())
  → 404 when {publicId} ≠ `Funity-F######`
  → Flutter previously mapped Dio 404 body `message` into Failure.message
  → UI rendered failure.message verbatim
```

### Identifier `chatqaa0cc175b9`

| Question | Finding |
|----------|---------|
| Valid `UserPublicId`? | **No** — must match `Funity-F` + 6 digits (`App\Support\UserPublicId`) |
| Username / numeric id / UUID? | **None of these** — non-canonical string (likely wrong field or corrupted deep-link / chat-related token) |
| Laravel endpoint exists? | **Yes** — `GET /api/users/{publicId}` in `routes/api.php` → `ApiPublicProfileController@show` |
| Flutter URL wrong? | Path `/users/{id}` is correct; **identifier** was wrong |
| Route-model binding? | Constraint via `UserPublicId::routePattern()` (`Funity-F[0-9]{6}`); invalid ids never reach the controller |
| Web? | Canonical ids work (`/u/Funity-F001115`). Invalid ids 404 without matching the API route |
| All profiles broken? | **No** — only non-canonical ids. Live API: `GET /api/users/Funity-F001115` → **200** with profile payload |

**Root cause (compound):**

1. Flutter navigated (or accepted a route) with a non-canonical public id.
2. Laravel correctly rejected it at the route layer (404 + framework message).
3. Flutter `mapDioException` previously surfaced Laravel `message` to the UI.

**Backend change required?** No. Endpoint and contract are correct. Fix is Flutter-side validation + safe error mapping.

---

## 3. API contract verification

| Item | Contract |
|------|----------|
| Method / path | `GET /api/users/{publicId}` |
| `{publicId}` pattern | `Funity-F[0-9]{6}` (`App\Support\UserPublicId::routePattern()`) |
| Controller | `app/Http/Controllers/Api/Profile/PublicProfileController.php` |
| Service | `PublicProfileService::findByPublicId` (validates + queries `users.public_id`) |
| Auth | Public (throttled); friendship / `can_message` depend on Sanctum when present |
| Success shape | `{ data: { id, public_id, name, initials, city, stats, hosted_events, friendship, can_message, ... } }` |

Live probes (2026-10-30):

- `Funity-F001115` → HTTP **200** JSON profile.
- `chatqaa0cc175b9` → HTTP **404** (route not matched; HTML/error page, not controller JSON).

---

## 4. Flutter-side fixes

| Change | File(s) |
|--------|---------|
| Canonical id helper mirroring Laravel | `mobile/lib/core/identity/user_public_id.dart` |
| Skip Dio when id invalid → `NotFoundFailure` | `mobile/lib/features/profile/data/public_profile_remote_source.dart` |
| Guarded navigation `pushPublicProfile` | `mobile/lib/core/navigation/app_navigation.dart` + call sites (DM, event chat/detail, friends, people, profile) |
| Sanitize returnTo `/u/...` | `app_navigation.dart` `sanitizeReturnTo` |
| Profile UI: loading / error / retry / localized copy | `public_profile_page.dart` + `AsyncBody` |
| Safe Dio → Failure mapping (no Laravel/SQL/route leak) | `mobile/lib/core/errors/error_mapper.dart` |
| EN/AR strings | `errorForbidden`, `errorProfileLoad`, `profileUnavailable`, existing network/generic keys |
| Controllers use `failureUserMessage` | auth, discover, discover map, event detail, favorites helper, people suggestions, DM chat |
| Login Apple catch no longer `e.toString()` | `login_page.dart` |

Emulator QA hook (inert unless defined):

- `--dart-define=QA_OPEN_PROFILE=<id>` pushes `/u/<id>` once from Home (for invalid-id evidence).

---

## 5. Backend dependencies

**None for this defect.** Do not change production routes/data for this repair.

Optional future hardening (not required to ship the Flutter fix):

- Ensure notification / chat payloads always emit `public_id` in `Funity-F######` form (investigate any producer of `chatqa…` strings if it reappears).

---

## 6. Global error-handling audit

### Architecture (reused, not duplicated)

- Typed `Failure` hierarchy: `mobile/lib/core/errors/failures.dart`
- Central mapper: `mapDioException` / `mapUnexpectedError` / `failureUserMessage` / `localizedFailureMessage`
- Remote sources already catch `DioException` → `mapDioException` across auth, events, discover, chat, DMs, notifications, profile, safety, etc.

### Classifications mapped

| Class | HTTP / Dio | User-facing (l10n preferred) |
|-------|------------|------------------------------|
| Network / timeout | connection*, timeouts | Offline / timeout |
| Unauthorized | 401 | Unauthorized |
| Forbidden | 403 | Forbidden |
| Not found | 404 | Not found / profile-unavailable on profile screen |
| Validation | 422 | First non-technical field error |
| Rate limit | 429 | Rate limit |
| Server | 5xx / other badResponse | Generic |
| Unknown | default / non-Dio | Generic |

Technical Laravel messages (`The route…`, `SQLSTATE`, stack paths, `api/…`) are stripped via `_isTechnicalMessage`.

### Leakage tightened this pass

- Mapper no longer copies status-body `message` into typed failures (except sanitized 422 field errors).
- Auth / Discover / Event detail / DM / favorites / people suggestions stop forwarding raw `failure.message` where updated.
- Remaining SnackBar sites that still use `failure.message` now receive **safe English defaults** from the mapper (not Laravel text). Prefer migrating them to `localizedFailureMessage` over time.

### Still follow-up (non-blocking for profile)

- Migrate remaining presentation SnackBars (event chat, tickets, blocks, join requests, etc.) to `localizedFailureMessage`.
- Avoid `kDebugMode` embedding of exception text in any user-visible path.

---

## 7. Automated tests

| Test | Path | Result |
|------|------|--------|
| Canonical / invalid id normalize | `test/core/user_public_id_test.dart` | Pass |
| Invalid id never hits API; route JSON sanitized; network/500 safe | `test/features/profile/public_profile_error_test.dart` | Pass |
| Status mapping + no `m404` leak | `test/core/error_mapper_upload_test.dart` | Pass |

Commands:

```bash
cd mobile
flutter test test/core/user_public_id_test.dart test/features/profile/public_profile_error_test.dart test/core/error_mapper_upload_test.dart
```

---

## 8. Android emulator evidence

| Case | Method | Result |
|------|--------|--------|
| Valid profile | Profile → **Open public profile** (`Funity-F001342`) | Loads name, public id, stats; AppBar **Public profile** + Back / Edit | Screenshot: `docs/flutter/parity_evidence/06_public_profile_valid.png` |
| Invalid id | `--dart-define=QA_OPEN_PROFILE=chatqaa0cc175b9` | UI: **This profile is no longer available.** + **Retry**; no Laravel leak | `07_public_profile_invalid.png`, `ui_invalid_pp.xml` |
| Back navigation | Tap Back from invalid profile | Returns to Home shell | `ui_back.xml` |
| Live API | `curl.exe` to production | Valid 200 / invalid 404 as above |
| Build proof | logcat | `profile_repair_20261030` (valid); `profile_invalid_qa` (invalid) |

### 8.1 Invalid-profile UI check — **PASS**

UI dump labels: `Back`, `Public profile`, `This profile is no longer available.`, `Retry`.

Leak scan on `ui_invalid_pp.xml`: `The route` / `api/users` / `chatqaa` / `SQLSTATE` / `could not be found` → all **False**.

---

## 9. Remaining issues

| Issue | Severity | Gate impact |
|-------|----------|-------------|
| Some SnackBars still use English `failure.message` instead of AR/EN l10n | Low | Does not reintroduce Laravel leakage |
| OSM gray tiles on slow emulator network | Low | Discover still usable |
| Producer of `chatqa…` token (if recurring) not yet traced to a single API field | Medium | Guarded at navigation + remote |
| Full Discover “New Cairo” regression not re-run in this extension pass | Medium | Keep overall **CONDITIONAL** |
| No AAB / versionCode change (by request) | — | — |

---

## 10. Release gate

**Verdict: CONDITIONAL**

| Gate criterion | Met? |
|----------------|------|
| Discover shows available events | Conditionally yes (prior + this session Home/Discover usable) |
| New Cairo selection correct | Not re-proved this extension → keeps CONDITIONAL |
| Public profiles load with canonical id | **Yes** (emulator `Funity-F001342`) |
| Invalid / bad id safe UI | **Yes** (emulator + tests) |
| Raw backend errors not shown | **Fixed** at mapper + profile UI + invalid-id short-circuit |
| Recoverable errors have retry | **Yes** on public profile (`AsyncBody` retry) |

**Do not mark PASS** until New Cairo focus is re-confirmed on emulator.

**Not done (per instructions):** AAB build, versionCode bump, commit/push, production DB changes.
