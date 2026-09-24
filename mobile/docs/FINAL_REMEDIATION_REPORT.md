# FINAL REMEDIATION REPORT — fun/mobile

Date: 2026-09-24  
Scope: Combined Audit #1 + Gap Audit #2 → implement → verify → constitution lock

---

## Executive Summary

P0 release blockers and the highest-risk P1 security/auth/lifecycle findings were remediated **without a rewrite**. Feature-first Riverpod architecture was preserved. Permanent rules are encoded in `mobile/docs/ENGINEERING_CONSTITUTION.md`, `AGENT.md`, `FLUTTER_PRINCIPLES.md`, `DESIGN.md` §19, and `.cursor/rules/flutter-engineering.mdc`.

**Tests:** `flutter test` — all passed (40+) after remediation.  
**Build:** Release signing without `key.properties` now **fails closed** (verified). `abiFilters` x86_64-only **removed**.

---

## Findings Resolved

| ID | Finding | Severity | Status | Evidence |
|----|---------|----------|--------|----------|
| BUILD-001 | x86_64-only ABI + debug release signing | P0 | **Fixed** | `android/app/build.gradle.kts`; Gradle fails without keystore/`-PallowDebugReleaseSigning` |
| BUILD-002 | `allowBackup` default true | P1 | **Fixed** | `AndroidManifest.xml` `allowBackup=false` |
| AUTHZ-JOIN-001 | Join UI not gated by `canParticipate` | P1 | **Fixed** | `event_detail_page.dart` identity CTA + `_join` guard |
| SEC-PRIV-001 | Password chars in theater | P1 | **Fixed** | `auth_access_theater.dart` + privacy test |
| SEC-PRIV-002 | Chat body in SnackBar | P1 | **Fixed** | `chats_page.dart` uses `newChatMessage` |
| SEC-RESET-001 | Reset token in URL query | P1 | **Fixed** | `ResetPasswordArgs` via `extra`; router rejects query |
| SEC-UPLOAD-001 | No client upload validation | P1 | **Fixed** | `UploadValidator` + identity/create pickers |
| SEC-SESSION-001 | Bootstrap network → fake logout | P1 | **Fixed** | `AuthSessionRestoreFailed` + splash retry |
| SEC-URL-001 | Unrestricted `launchUrl` | P2 | **Fixed + Doc-locked** | `launchSafeExternalUrl` http(s) only; SoT locked |
| NET-001 | No sendTimeout; coarse status map | P1 | **Fixed** | `api_client.dart` + expanded `error_mapper` |
| NET-RACE-001 | Discover stale responses | P1 | **Fixed** | generation counter in `DiscoverController` |
| NET-CANCEL-001 | Chat poll no cancel | P1 | **Fixed** | CancelToken + dispose; inbox `_inFlight` |
| NAV-001 | `?? 0` route IDs | P1 | **Fixed** | `parsePositiveRouteId` + invalid route page |
| ARCH-FAV-001 | Duplicated favorite logic | P2 | **Fixed + Doc-locked** | `event_favorite_actions.dart`; SoT locked |
| ARCH-DOMAIN-001 | `app_update` domain impurity | P2 | **Fixed + Doc-locked** | pure domain + `app_update_service.dart`; SoT locked |
| ERR-001 | Hardcoded EN / unused validation map | P2 | **Fixed + Doc-locked** | `mapUnexpectedError`; no `ServerFailure(e.toString())`; Dio unknown hardened |
| ARCH-REPO-SAFETY-001 | Safety RemoteSource-from-UI | P2 | **Fixed + Doc-locked** | `SafetyRepository` + UI migrated |
| ARCH-REPO-PROFILE-001 | Profile RemoteSource-from-UI | P2 | **Fixed + Doc-locked** | `ProfileRepository` + `PublicProfileRepository` |
| ARCH-REPO-IDENTITY-001 | Identity RemoteSource-from-UI | P2 | **Fixed + Doc-locked** | `IdentityRepository` |
| ARCH-REPO-CHAT-001 | Chat RemoteSource-from-UI | P2 | **Fixed + Doc-locked** | `ChatsRepository` |
| ARCH-REPO-HOME-001 | Home RemoteSource-from-UI | P2 | **Fixed + Doc-locked** | `HomeRepository` (weather/participations/suggest) |
| ARCH-GOD-DETAIL-001 | event_detail god page | P2 | **Fixed + Doc-locked** | `EventDetailController`; page thinned |
| ARCH-GOD-CREATE-001 | create_event god page | P2 | **Fixed + Doc-locked** | `CreateEventController`; form fields on page |
| ARCH-GOD-HOME-001 | home_page god page | P2 | **Fixed + Doc-locked** | sections/hero extracted; ~410-line composition page |
| OPS-RELEASE-001 | ARM QA + Google client ID | P2 | **Doc-locked checklist** | `RELEASE.md` — manual ops, not auto-closed by unit tests |
| A11Y-RTL-001 | RTL / Semantics gaps | P2 | **Fixed + Doc-locked** | directional chrome + tests; incremental screen Semantics OK |
| TEST-001 | Critical flows untested | P1 | **Improved** | session restore, privacy, upload, mapper, favorites, route ids |

---

## Findings Not Resolved

| ID | Reason | Risk | Next Action |
|----|--------|------|-------------|
| God pages | Detail/create/home composition extracted | — | — |
| Profile/chat/identity still RemoteSource from UI | **Resolved** via ARCH-REPO-* | — | — |
| Google `GOOGLE_SERVER_CLIENT_ID` empty default | Ops/config (`OPS-RELEASE-001`) | Google login may lack id_token | Set dart-define in store builds |
| Physical ARM APK install | Ops/manual (`OPS-RELEASE-001`) | Medium until QA | Manual QA per RELEASE.md |
| Full a11y / RTL audit | **Partial** via A11Y-RTL-001 (chrome + tests) | Low residual | Incremental Semantics when editing screens |
| WebSocket chat | Product decision: polling retained | Battery | Only if Reverb product requires |

---

## Backend-Dependent Findings

| Topic | Client status | Server |
|-------|---------------|--------|
| Join when `pending_documents` | UX gated | **Must** reject (Laravel policy) — assumed per MASTER_CONTEXT; not re-tested against live API here |
| Google access_token-only | Client may send | Server must validate |
| Token TTL / refresh | Single Sanctum token; no refresh client | Server TTL policy |
| Upload MIME/size | Client validates ≤8MB images | Server remains authoritative |

---

## Tests Added

* `test/core/error_mapper_upload_test.dart`
* `test/core/route_ids_test.dart`
* `test/features/auth/session_restore_test.dart`
* `test/features/auth/auth_theater_privacy_test.dart`
* `test/features/events/favorite_actions_test.dart`
* Fixed `event_join_actions_test` leave label

---

## Build Verification

| Check | Result |
|-------|--------|
| `abiFilters` in defaultConfig | **Absent** |
| Release without `key.properties` | **Fails closed** with GradleException (verified) |
| Local override | `-PallowDebugReleaseSigning=true` documented |
| `key.properties.example` | Added; `key.properties` gitignored |
| `flutter test` | All passed |

---

## Security Verification

* Password digits not rendered (test)
* Reset token not in query routes
* Chat toast no body
* Upload client validation (test)
* allowBackup false
* Safe URL launcher

---

## Architecture Changes

* Shared favorite helper
* Domain/data split for app update
* Expanded Failure taxonomy
* Join UX aligned with MASTER_CONTEXT identity rules
* No UseCase layer added; no stack migration

---

## Documentation Changes

| File | Change |
|------|--------|
| `mobile/docs/ENGINEERING_CONSTITUTION.md` | **Created** — IDs + DO NOT REGRESS + change protocol |
| `mobile/docs/FLUTTER_PRINCIPLES.md` | Architecture, networking, auth updated |
| `mobile/docs/RELEASE.md` | Signing/ABI locked checklist |
| `mobile/docs/DESIGN.md` | §19 Flutter UX rules |
| `MASTER_CONTEXT.md` | Mobile architecture + constitution pointer |
| `AGENT.md` | Flutter non-negotiables for AI agents |
| `.cursor/rules/flutter-engineering.mdc` | **Created** |
| `mobile/README.md` | Doc table updated |

---

## Permanent Rules Added

See **DO NOT REGRESS** in `mobile/docs/ENGINEERING_CONSTITUTION.md` (debug signing, ARM ABIs, authz, privacy, races, remotes, retries, polling lifecycle, safe URL, shared favorites, domain purity, ERR-001, safety repository, tests).

---

## Remaining Technical Debt

1. Manual RELEASE.md gates: ARM device APK install + production `GOOGLE_SERVER_CLIENT_ID`.
2. Incremental Semantics on remaining screens when touched.

---

## Success criteria checklist

* [x] Confirmed P0/P1 resolved or documented
* [x] Relevant tests pass
* [x] Build/release settings verified (fail-closed signing; no ABI filter)
* [x] Security-sensitive behavior verified + tested where practical
* [x] Architecture more consistent without rewrite
* [x] Re-scan of original failure patterns
* [x] Documentation matches implementation
* [x] Agent rules prevent regression
