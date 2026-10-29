# Funnity Flutter — Pre-Release Audit Report

**Date:** 2026-10-29  
**Scope:** Static audit, backend parity, safe source repairs, Android Closed Testing handoff prep  
**Runtime / AAB / Play Console:** **Not executed** on this host  
**Verdict:** **CONDITIONAL** — ready for Windows validation after committing these source changes; device/FCM/voice/map runtime still required

---

## A. Repository Baseline

| Item | Value |
|------|--------|
| Branch | `main` (only; no new branch created) |
| Baseline commit (pre-fix) | `0598e3c` — *Make voice retries idempotent before media storage.* |
| Working tree | Dirty with Flutter audit fixes + docs (see §J) |
| Unrelated work | Preserved; no `reset --hard` / `clean` / force-push |
| Flutter root | `/home/d14098/funnity.com/mobile` |

---

## B. Flutter Architecture

| Area | Implementation |
|------|----------------|
| Structure | Feature folders under `lib/features/*` + `lib/core` + `lib/app` |
| State | Riverpod (`flutter_riverpod`) |
| HTTP | Dio + Sanctum Bearer via `ApiClient` + `FlutterSecureStorage` |
| Navigation | `go_router` |
| Maps | `flutter_map` + Explore V4 remote (`include_approximate`, bounds query) |
| Audio | `record` (AAC-LC → `.m4a`) + `audioplayers` |
| Push | `firebase_core` / `firebase_messaging` |
| i18n | `flutter_localizations` + `lib/l10n` EN/AR |
| Version | `1.0.2+3` |
| Application ID | `com.funnity.app` |

Compile-time config: `--dart-define` via `Env` (`API_BASE_URL`, `FLAVOR`, Google / Reverb overrides).

---

## C. Backend Parity Matrix

| Feature | Backend Contract | Flutter Implementation | Status | Required Fix |
|---------|------------------|------------------------|--------|--------------|
| Auth login/register/logout | Sanctum token API | Bearer interceptor; secure token store; 401 clears token | OK | — |
| Stateful Sanctum (web) | `statefulApi()` for browser | Flutter does **not** use cookies | OK | Keep Bearer-only |
| DM list / open / messages | `/api/dms*` | `DmsRemoteSource` | OK | — |
| DM text / reply / react | JSON + emoji react | Implemented | OK | — |
| DM image / voice multipart | `media`, `media_kind`, `duration_ms`, `client_uuid` | Multipart + AAC `audio/mp4` | Fixed | FormData Content-Type; MIME; retry UUID |
| DM private media | `GET /dms/media/{id}` Bearer bytes | Byte fetch + player | OK | — |
| Event chat text / react | `/events/{id}/messages` | Implemented | OK | — |
| Event chat media | Same multipart + `/messages/{id}/media` | Was missing | Fixed | Post + playback + composer |
| Voice V3 lifecycle | Pause/resume/preview/retry idempotent | DM + event recording controls; retry UUID | Fixed | Device QA still needed |
| Explore / Living Map | Explore V4 map discovery API | `flutter_map` + approximate styling | OK | Visual device review |
| Events CRUD / join / leave | `/api/events*` | Repository + screens | OK | — |
| Tickets / QR | `/events/{id}/ticket` | Ticket remote + UI | OK | Device scan QA |
| Communities | No member Communities routes in `routes/api.php` | No Flutter communities feature | N/A | Track as product gap if web adds API |
| FCM register / deep links | API device tokens + allowlisted paths | `PushNotificationService` + navigation | Static OK | Runtime QA |
| Profile / identity / friends | Matching `/api` surfaces | Present | OK | — |

---

## D. Critical Issues

| Severity | Issue | Disposition |
|----------|-------|-------------|
| High | Global Dio `Content-Type: application/json` broke / risked FormData multipart boundary | **Fixed** in `ApiClient` |
| High | DM/event voice retry minted a **new** `client_uuid` each attempt (duplicate risk vs backend idempotency) | **Fixed** — reuse pending UUID |
| High | Event chat could not send/play voice or images despite backend support | **Fixed** |
| Medium | `sendTimeout` 20s too short for large voice uploads | **Fixed** — 60s default / 90s media |
| Medium | Auth fake repositories missing Apple / account-deletion methods → analyze/test errors | **Fixed** |
| Low | Stale brand color assertions in `widget_test` | **Fixed** |
| Low | Discover filter unit tests lagged `discovery_mode` contract | **Fixed** (tests aligned to code) |
| Info | No Flutter Communities module | Documented; no backend member Communities API |
| Info | Web pending-bubble chrome richer than Flutter | Documented limitation |

---

## E. Fixes Applied

| File(s) | Rationale |
|---------|-----------|
| `mobile/lib/core/network/api_client.dart` | FormData-safe headers; longer timeouts; JSON Content-Type only when appropriate |
| `mobile/lib/features/friends/data/dms_remote_source.dart` | Voice/image MIME + upload timeouts |
| `mobile/lib/features/friends/presentation/dm_chat_page.dart` | Stable `client_uuid`, retry UI, draft retention on failure |
| `mobile/lib/features/events/data/events_remote_source.dart` | Multipart post + authenticated media bytes |
| `mobile/lib/features/events/domain/events_repository.dart` + `events_repository_impl.dart` | Media API surface |
| `mobile/lib/features/events/presentation/event_chat_page.dart` | Voice V3 controls, image attach, private media playback |
| `mobile/pubspec.yaml` (+ lock) | Direct `http_parser` dependency |
| Auth-related unit test fakes | Match `AuthRepository` |
| `mobile/test/widget_test.dart`, `discover_filters_test.dart` | Match current tokens / query contract |
| `docs/FLUTTER_WEB_PARITY.md` §29 | Event chat voice/image parity note |
| `docs/flutter/ANDROID_CLOSED_TESTING_HANDOFF.md` | Windows build/upload checklist |
| This report | Audit record |

Formatting-only churn may appear in a few friends/test files from `dart format`.

---

## F. Static Validation

Tooling used on server: **Flutter 3.47.8 / Dart 3.13.7** at `/home/d14098/flutter-3.47` (default `/home/d14098/flutter` is 3.35 / Dart 3.9 and **cannot** resolve `sdk: ^3.13.1`).

| Check | Result |
|-------|--------|
| `flutter pub get` | **PASS** (with Flutter 3.47) |
| `dart format` (changed paths) | **PASS** |
| `flutter analyze` | **PASS** (0 errors; remaining infos/warnings pre-existing / non-blocking) |
| `flutter test` | **PASS** (`+80` — All tests passed with Flutter 3.47) |
| AAB / emulator / Play upload | **NOT RUN** (owner directive) |
| Physical device voice / FCM / map | **NOT RUN** |

Do not treat this as compiler validation on the owner’s Windows machine until they re-run the same commands locally.

---

## G. Android Configuration

| Item | Value / notes |
|------|----------------|
| `applicationId` | `com.funnity.app` — **unchanged** |
| Namespace | `com.funnity.app` |
| Versions | From Flutter/`pubspec` (`1.0.2` / `3`) |
| `minSdk` / `targetSdk` / `compileSdk` | `flutter.minSdkVersion` / `targetSdkVersion` / `compileSdkVersion` |
| JDK | 17 |
| Signing | `key.properties` → release; fail-closed without it |
| Firebase | `google-services.json` present; plugin applied conditionally |
| Permissions | INTERNET, POST_NOTIFICATIONS, RECORD_AUDIO, COARSE/FINE location |
| Backup | `allowBackup=false` (BUILD-002) |
| Shrinker / ProGuard | No custom proguard rules file; release uses Flutter defaults + obfuscate flag in handoff |
| Flavors | Compile-time `FLAVOR` define only (no productFlavors Gradle matrix) |

---

## H. Windows Handoff

Follow **[ANDROID_CLOSED_TESTING_HANDOFF.md](./ANDROID_CLOSED_TESTING_HANDOFF.md)**.

Summary:

1. Install Flutter with Dart ≥ 3.13.1 and JDK 17.  
2. `cd mobile && flutter pub get && flutter analyze && flutter test`.  
3. Configure `android/key.properties` (never commit).  
4. Bump `versionCode` from Play Console (do not guess).  
5. `flutter build appbundle --release ...`  
6. Upload AAB to **existing** Closed Testing track.

---

## I. Remaining Risks

| Risk | Notes |
|------|-------|
| Voice pipeline on device | AAC-LC MIME accepted by backend; still needs two-device QA |
| Event chat voice UX | Newly wired; needs regression vs web V3 |
| FCM | Config present; delivery/cold-start not verified here |
| Map visuals | `flutter_map` ≠ MapLibre; markers/approximate styling need eye QA |
| Network races | Poll + realtime dual path still relies on id/`client_uuid` dedupe |
| Communities | Product gap if web communities ship without API + Flutter |
| Uncommitted source | Owner must commit/push before cloning on Windows if using remote |

---

## J. Git Status

At report time:

- Branch: `main` tracking `origin/main`
- Baseline remote HEAD was `0598e3c`
- Local changes: Flutter source fixes + docs (**not automatically committed** — create a commit on Windows or ask the agent to commit explicitly)
- Push: **not performed**

---

## K. Verdict

**CONDITIONAL**

Ready for **Windows validation → signed AAB → Closed Testing upload**, provided:

1. These source changes are committed on `main` (or transferred intact).  
2. Windows runs analyze/test green.  
3. Play `versionCode` is set from Console.  
4. Device QA covers voice DM + event chat, FCM deep links, and Discover map.

Not **READY FOR WINDOWS VALIDATION** alone without acknowledging runtime gaps — and not **BLOCKED** by any remaining static compile-critical defect found in this pass.
