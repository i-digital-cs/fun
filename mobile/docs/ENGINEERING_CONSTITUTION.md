# ENGINEERING_CONSTITUTION.md

# Fun Mobile — Permanent Engineering Constitution

This document locks production rules discovered during the 2026 remediation audits.

**Hierarchy (Source of Truth):**

1. `MASTER_CONTEXT.md` (product + platform)
2. `AGENT.md` (agent workflow + non-negotiables)
3. `mobile/docs/FLUTTER_PRINCIPLES.md` (Flutter stack/architecture)
4. **This file** (locked remediation rules / do-not-regress)
5. `mobile/docs/DESIGN.md` + root `DESIGN.md` (UI/UX)
6. Tests under `mobile/test/`

Code demonstrates rules. Tests protect rules. Docs explain rules. Agent rules prevent AI regression.

---

## Traceability index

| ID | Area | Summary |
|----|------|---------|
| BUILD-001 | Release | ARM ABIs required; release signing via `key.properties`; no silent debug signing; fail closed; opt-in local debug only |
| BUILD-002 | Android | `allowBackup=false` + `fullBackupContent=false`; no silent Auto Backup |
| SEC-PRIV-001 | Auth UX | Never display password characters in theater digits |
| SEC-PRIV-002 | Chat | Never echo private message bodies in global SnackBars |
| SEC-RESET-001 | Auth | Reset tokens travel via `GoRouter` `extra`, never query/path |
| SEC-URL-001 | Links | External launches limited to `http`/`https` |
| SEC-UPLOAD-001 | Uploads | Client MIME/size validation; server authoritative |
| SEC-SESSION-001 | Auth | Network bootstrap failure ≠ logout; keep token + retry |
| AUTHZ-JOIN-001 | Events | Client gate join/create on `canParticipate`; server authoritative |
| NET-001 | Dio | connect/send/receive timeouts; typed Failure mapping |
| NET-RACE-001 | Discover | Latest-request-wins generation counter |
| NET-CANCEL-001 | Chat | CancelToken + dispose; no overlapping inbox polls |
| NAV-001 | Router | Invalid IDs must not become `0` |
| ARCH-FAV-001 | Events | Shared optimistic favorite helper |
| ARCH-DOMAIN-001 | Site | Domain stays free of Dio/Platform/url_launcher |
| ERR-001 | Errors | Prefer `Failure` + `failureUserMessage`; no raw `e.toString()` to users |
| ARCH-REPO-SAFETY-001 | Safety | UI uses `SafetyRepository`; not `SafetyRemoteSource` |
| ARCH-REPO-PROFILE-001 | Profile | UI uses `ProfileRepository` / `PublicProfileRepository` |
| ARCH-REPO-IDENTITY-001 | Identity | UI uses `IdentityRepository` |
| ARCH-REPO-CHAT-001 | Chat | UI uses `ChatsRepository` |
| ARCH-REPO-HOME-001 | Home | UI uses `HomeRepository` (weather/participations/suggest) |
| ARCH-GOD-DETAIL-001 | Events UI | Event detail mutations in `EventDetailController` |
| ARCH-GOD-CREATE-001 | Events UI | Create/edit catalog+submit in `CreateEventController` |
| ARCH-GOD-HOME-001 | Home UI | Home sections/hero extracted from `home_page.dart` |
| OPS-RELEASE-001 | Release ops | ARM device QA + Google client ID checklist in RELEASE.md |
| A11Y-RTL-001 | A11y / RTL | Directional chrome + Semantics; AR→RTL tests |

---

## BUILD-001 — Traceability record

```text
ID: BUILD-001
Problem:
  Release buildType used signingConfigs.debug unconditionally, and
  defaultConfig ndk.abiFilters was limited to x86_64 (emulator-only),
  so store/production Android artifacts could be debug-signed and/or
  uninstallable on real ARM devices.

Evidence:
  mobile/android/app/build.gradle.kts (pre-remediation):
  - signingConfig = signingConfigs.getByName("debug") under buildTypes.release
  - abiFilters += listOf("x86_64") under defaultConfig.ndk

Decision:
  Fail closed for missing production signing. Allow debug signing for
  release builds ONLY via explicit -PallowDebugReleaseSigning=true for
  local/non-store testing. Do not restrict defaultConfig to x86_64.
  AI agents must not weaken these rules just to make a build pass.

Implementation:
  mobile/android/app/build.gradle.kts
  - Loads android/key.properties when present → signingConfigs.release
  - Else if allowDebugReleaseSigning=true → debug signing (local only)
  - Else → GradleException (fail closed)
  - No abiFilters in defaultConfig
  mobile/android/key.properties.example (template; key.properties gitignored)

Verification:
  - Without key.properties: release packaging tasks (assemble/bundle/package/sign *Release*) fail closed
  - Debug / flutter run (assembleDebug) must not require key.properties
  - With -PallowDebugReleaseSigning=true: release may use debug keystore (local)
  - Grep: no abiFilters in defaultConfig; no unconditional release→debug signing

Permanent rule:
  - Production/store releases MUST use a real upload/production keystore.
  - Production/store releases MUST NOT silently fall back to debug signing.
  - Missing production signing MUST fail closed.
  - Debug release signing ONLY via -PallowDebugReleaseSigning=true (local/non-store).
  - Do NOT reintroduce x86_64-only abiFilters in defaultConfig without SoT change.
  - Agents MUST NOT weaken BUILD-001 to make builds pass.

Files updated (documentation lock):
  MASTER_CONTEXT.md
  AGENT.md
  mobile/docs/ENGINEERING_CONSTITUTION.md
  mobile/docs/RELEASE.md
  mobile/docs/FLUTTER_PRINCIPLES.md
  .cursor/rules/flutter-engineering.mdc
  (DESIGN.md unchanged — no release/build section there)
```

---

## BUILD-002 — Traceability record

```text
ID: BUILD-002
Problem:
  Android <application> omitted allowBackup, so platform default
  (allowBackup=true) could include app data in Auto Backup / cloud
  backup surfaces, increasing exposure risk for local state near
  auth-related storage.

Evidence:
  Pre-remediation main AndroidManifest.xml <application> had no
  android:allowBackup / android:fullBackupContent attributes.

Decision:
  Explicitly disable backup: allowBackup=false and
  fullBackupContent=false. Re-enabling requires an explicit SoT
  security decision. Agents must not flip these for convenience.

Implementation:
  mobile/android/app/src/main/AndroidManifest.xml
  - android:allowBackup="false"
  - android:fullBackupContent="false"

Verification:
  Grep confirms both attributes present on <application>.
  Docs state the same permanent rule.

Permanent rule:
  - Application backup MUST remain disabled (allowBackup=false).
  - fullBackupContent MUST remain false unless SoT changes.
  - Agents MUST NOT re-enable backup to silence warnings or “make
    something work.”

Files updated (documentation lock):
  MASTER_CONTEXT.md
  AGENT.md
  mobile/docs/ENGINEERING_CONSTITUTION.md
  mobile/docs/RELEASE.md
  mobile/docs/FLUTTER_PRINCIPLES.md
  .cursor/rules/flutter-engineering.mdc
  (DESIGN.md unchanged — no Android backup section)
```

---

## AUTHZ-JOIN-001 — Traceability record

```text
ID: AUTHZ-JOIN-001
Problem:
  Authenticated users who cannot participate (e.g. pending_documents)
  could see EventJoinActions and call join from event detail, while
  create/chat were already identity-gated — inconsistent with
  MASTER_CONTEXT §5 and FLUTTER_PRINCIPLES.

Evidence:
  event_detail_page.dart previously showed EventJoinActions for any
  Authenticated user; _join only checked Authenticated, not
  canParticipate. Router blocked /create and chat routes but not join
  on /events/:id.

Decision:
  Client UX must gate join (and create) on user.canParticipate and
  send non-participants to /identity. Server remains authoritative.
  Do not remove gates to “just let them join.”

Implementation:
  - event_detail_page.dart: !_canParticipate → identity CTA;
    _join checks canParticipate before API
  - create_event_page.dart: bootstrap refuses !canParticipate → identity
  - router.dart: _isIdentityBlockedRoute for create/chats/manage
  - User.canParticipate / needsIdentity domain helpers + unit tests

Verification:
  Code grep: AUTHZ-JOIN-001 comment + canParticipate gates present.
  Tests: user_test / session_restore_test assert pending_documents
  ⇒ canParticipate false.

Permanent rule:
  - Join/create UX MUST require canParticipate; else /identity.
  - Client gates are defense-in-depth only; Laravel is authoritative.
  - Agents MUST NOT show join CTAs or skip identity for non-participants.

Files updated (documentation lock):
  MASTER_CONTEXT.md
  AGENT.md
  mobile/docs/ENGINEERING_CONSTITUTION.md
  mobile/docs/FLUTTER_PRINCIPLES.md
  mobile/docs/DESIGN.md (§19)
  .cursor/rules/flutter-engineering.mdc
```

---

## SEC-PRIV-001 — Traceability record

```text
ID: SEC-PRIV-001
Problem:
  Auth access theater digitsFrom(password) rendered the first characters
  of the real password on-screen (shoulder surfing / screen recording risk).

Evidence:
  auth_access_theater.dart previously used password[i] for digit tiles.

Decision:
  Password digit source MUST render bullets only (filled count may reflect
  length up to 4). Never show plaintext password characters.

Implementation:
  auth_access_theater.dart AuthDigitSource.password → '•' tiles
  test/features/auth/auth_theater_privacy_test.dart

Verification:
  Grep: no password[i]; privacy test asserts no plaintext chars.

Permanent rule:
  Never display password characters in UI theater/animations.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## SEC-PRIV-002 — Traceability record

```text
ID: SEC-PRIV-002
Problem:
  Inbox polling toast echoed "name: body", leaking private chat content
  on a shared device / over-shoulder.

Evidence:
  Former ChatToastListener SnackBar content used lastMessage body.

Decision:
  Global chat notifications MUST use generic localized copy only
  (newChatMessage). Never include message body in global toasts/banners.

Implementation:
  chat_toast_banner.dart documents SEC-PRIV-002; displays l10n.newChatMessage
  (open action still deep-links to the thread).

Verification:
  Grep chat presentation: newChatMessage present; no body interpolation in toast UI.

Permanent rule:
  Never echo private message bodies in global notifications.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## SEC-RESET-001 — Traceability record

```text
ID: SEC-RESET-001
Problem:
  Password-reset token was passed as a GoRouter query parameter
  (/reset-password?email=&resetToken=), exposing it to history,
  screenshots, and logs.

Evidence:
  verify_reset_code_page previously context.go with queryParameters resetToken;
  router read q['resetToken'].

Decision:
  Reset tokens MUST travel only via in-memory GoRouter extra
  (ResetPasswordArgs). Router MUST reject query/path token delivery.

Implementation:
  ResetPasswordArgs on reset_password_page.dart
  verify_reset_code_page → context.go('/reset-password', extra: ...)
  router.dart: accepts ResetPasswordArgs only; otherwise ForgotPasswordPage
  with SEC-RESET-001 comment

Verification:
  Grep: resetToken not accepted from queryParameters in router builder.

Permanent rule:
  Never put password-reset tokens in URLs.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## SEC-UPLOAD-001 — Traceability record

```text
ID: SEC-UPLOAD-001
Problem:
  Identity/event image picks uploaded via multipart with only picker
  resize/quality — no client MIME/extension/size checks.

Evidence:
  identity_page / create_event_page pickImage → path with no validation.

Decision:
  Client MUST validate type/size (UploadValidator) before upload for UX
  and resource protection. Server remains authoritative.

Implementation:
  core/utils/upload_validator.dart (max 8MB; jpg/jpeg/png/webp/heic/heif)
  identity_page.dart + create_event_page.dart call validateImageFile
  test/core/error_mapper_upload_test.dart

Verification:
  Grep UploadValidator on identity/create; unit tests for missing/bad/ok.

Permanent rule:
  Client upload validation required; never skip; never claim it replaces server.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## SEC-SESSION-001 — Traceability record

```text
ID: SEC-SESSION-001
Problem:
  bootstrap() mapped any /user failure to Unauthenticated without clearing
  distinction — network blips looked like logout while token could remain.

Evidence:
  auth_controller.bootstrap previously set Unauthenticated on any me() failure.

Decision:
  Unauthorized / cleared token → Unauthenticated.
  Other failures with token retained → AuthSessionRestoreFailed + splash retry.
  Do not fake logout on transient errors.

Implementation:
  AuthSessionRestoreFailed state; bootstrap branching; router keeps splash;
  splash retry UI; session_restore_test.dart

Verification:
  Tests: network failure → AuthSessionRestoreFailed + token kept;
  unauthorized → Unauthenticated.

Permanent rule:
  Network restore failure ≠ logout; keep token and offer retry.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## NET-001 — Traceability record

```text
ID: NET-001
Problem:
  Dio had no sendTimeout; HTTP statuses other than 401/422 collapsed to
  generic ServerFailure; user-facing paths sometimes used e.toString().

Evidence:
  api_client.dart BaseOptions lacked sendTimeout; error_mapper mapped
  most statuses to ServerFailure only.

Decision:
  Require connect/send/receive timeouts; expand Failure taxonomy
  (403/404/409/429); expose failureUserMessage for UI. No automatic
  retries for unsafe mutations.

Implementation:
  api_client.dart: connect/send/receive 20s
  failures.dart: Forbidden/NotFound/Conflict/RateLimit
  error_mapper.dart: status mapping + failureUserMessage
  test/core/error_mapper_upload_test.dart

Verification:
  Grep sendTimeout; mapper tests for 401/403/404/409/422/429.

Permanent rule:
  Typed Failure pipeline; timeouts required; no unsafe auto-retry;
  no raw exceptions as primary user copy.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## NET-RACE-001 — Traceability record

```text
ID: NET-RACE-001
Problem:
  DiscoverController.load had no generation guard — rapid filter/search
  could apply stale results over newer ones.

Evidence:
  discover_controller load() awaited discover without request id.

Decision:
  Latest-request-wins via monotonic _loadGeneration; ignore stale
  completions.

Implementation:
  discover_controller.dart NET-RACE-001 generation check after await.

Verification:
  Grep _loadGeneration / generation != _loadGeneration.

Permanent rule:
  Concurrent discover/search loads must discard stale responses.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## NET-CANCEL-001 — Traceability record

```text
ID: NET-CANCEL-001
Problem:
  Event chat polling had no CancelToken; overlapping inbox polls possible;
  requests could continue after leave.

Evidence:
  event_chat_page Timer.periodic without cancel token; chat toast poll
  without in-flight guard (pre-remediation).

Decision:
  CancelToken on chat message loads; cancel on dispose; inbox _inFlight
  (or equivalent) to prevent overlap. Polling retained as product choice.

Implementation:
  event_chat_page.dart CancelToken + dispose cancel
  events_remote_source/repository messages(cancelToken)
  chats_page ChatToastListener _inFlight

Verification:
  Grep CancelToken in event_chat_page; _inFlight in chats_page.

Permanent rule:
  Screen-scoped network work must cancel/guard on dispose; no unbounded
  overlapping pollers.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## NAV-001 — Traceability record

```text
ID: NAV-001
Problem:
  Event routes used int.tryParse(id) ?? 0, causing spurious API calls
  with eventId 0 on bad deep links / typos.

Evidence:
  router.dart event builders previously coalesced parse failures to 0.

Decision:
  parsePositiveRouteId returns null for invalid/non-positive IDs;
  show intentional invalid-route page.

Implementation:
  core/utils/route_ids.dart (NAV-001)
  router.dart uses parsePositiveRouteId + _InvalidRoutePage
  test/core/route_ids_test.dart

Verification:
  Grep: no ?? 0 for event path id parsing in router; route_ids_test passes.

Permanent rule:
  Never coerce invalid route IDs to 0.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §19, flutter-engineering.mdc
```

---

## SEC-URL-001 — Traceability record

```text
ID: SEC-URL-001
Problem:
  External opens used launchUrl without scheme allowlisting (maps/store
  URLs from API could theoretically use unexpected schemes).

Evidence:
  event_detail maps open and openStoreUrl previously launched without
  http/https restriction.

Decision:
  All external launches of server/user-provided URLs MUST use
  launchSafeExternalUrl (http/https only + canLaunchUrl).

Implementation:
  core/utils/safe_url.dart (SEC-URL-001)
  event_detail_page.dart maps → launchSafeExternalUrl
  site/data/app_update_service.dart openStoreUrl → launchSafeExternalUrl

Verification:
  Grep launchUrl call sites under lib: only inside safe_url.dart
  (plus any intentional non-URL cases if added later must be reviewed).

Permanent rule:
  Never unrestricted launchUrl for untrusted/external URL strings.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-FAV-001 — Traceability record

```text
ID: ARCH-FAV-001
Problem:
  Discover and home nearby duplicated optimistic favorite + rollback
  logic (drift risk).

Evidence:
  Nearly identical toggleFavorite methods in discover_controller and
  home_nearby_controller (pre-remediation).

Decision:
  Shared toggleFavoriteInList helper with onOptimistic callback;
  list controllers must reuse it. Detail-page local favorite may remain
  page-local if it does not fork a third list pipeline.

Implementation:
  features/events/presentation/event_favorite_actions.dart
  discover_controller + home_nearby_controller call toggleFavoriteInList
  test/features/events/favorite_actions_test.dart

Verification:
  Grep toggleFavoriteInList used by discover + home nearby.

Permanent rule:
  Do not duplicate list favorite optimistic/rollback logic.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-DOMAIN-001 — Traceability record

```text
ID: ARCH-DOMAIN-001
Problem:
  site/domain/app_update.dart imported Dio, Platform, PackageInfo,
  url_launcher — domain polluted with infrastructure.

Evidence:
  Pre-remediation app_update.dart mixed compareSemver with checkForUpdate
  and openStoreUrl.

Decision:
  Domain keeps pure types + compareSemver only. Infrastructure moves to
  data/app_update_service.dart.

Implementation:
  features/site/domain/app_update.dart — pure (ARCH-DOMAIN-001 comment)
  features/site/data/app_update_service.dart — checkForUpdate / openStoreUrl
  router imports service + domain types

Verification:
  Grep site/domain: no dio/url_launcher/dart:io imports.

Permanent rule:
  Domain must not depend on Dio/Platform/PackageInfo/url_launcher/Flutter UI.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ERR-001 — Traceability record

```text
ID: ERR-001
Problem:
  Remote parse/catch paths returned ServerFailure(e.toString()), which
  could surface exception text via failure.message in SnackBars.
  Dio unknown mapped error.message similarly.

Evidence:
  events_remote_source + home_remote_source catch → ServerFailure(e.toString())
  mapDioException default → UnknownFailure(error.message)

Decision:
  Non-Dio failures MUST use mapUnexpectedError → const UnknownFailure.
  Dio unknown MUST use const UnknownFailure (no raw Dio message).
  Prefer failureUserMessage for UI. Server/API messages on typed
  Failures remain OK.

Implementation:
  core/errors/error_mapper.dart — mapUnexpectedError; Dio default hardened
  events_remote_source.dart + home_remote_source.dart catch paths
  test/core/error_mapper_upload_test.dart ERR-001 cases
  FLUTTER_BEST_PRACTICES.md example corrected

Verification:
  Grep lib: no ServerFailure(e.toString()) / Failure(e.toString())
  Tests: mapUnexpectedError + Dio unknown do not leak secrets

Permanent rule:
  Never put e.toString() into Failures destined for UI.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, FLUTTER_BEST_PRACTICES.md, flutter-engineering.mdc
```

---

## ARCH-REPO-SAFETY-001 — Traceability record

```text
ID: ARCH-REPO-SAFETY-001
Problem:
  Safety block/report/list called SafetyRemoteSource directly from
  presentation (event detail/chat/members, profile, blocks, report).

Evidence:
  Multiple presentation files used safetyRemoteSourceProvider.

Decision:
  Thin SafetyRepository (catalog/auth pattern). Models in domain.
  UI uses safetyRepositoryProvider only.

Implementation:
  features/safety/domain/safety_models.dart
  features/safety/domain/safety_repository.dart
  features/safety/data/safety_repository_impl.dart
  core_providers.dart safetyRepositoryProvider
  All prior UI call sites migrated
  test/features/safety/safety_repository_test.dart

Verification:
  Grep presentation: no safetyRemoteSourceProvider (only core_providers)

Permanent rule:
  Do not call SafetyRemoteSource from UI while SafetyRepository exists.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-REPO-PROFILE-001 / ARCH-REPO-IDENTITY-001 / ARCH-REPO-CHAT-001 / ARCH-REPO-HOME-001 — Traceability

```text
IDs: ARCH-REPO-PROFILE-001, ARCH-REPO-IDENTITY-001, ARCH-REPO-CHAT-001,
     ARCH-REPO-HOME-001
Problem:
  Profile, public profile, identity, chats, and home still called
  *RemoteSource from presentation (inconsistent with auth/events/catalog/safety).

Decision:
  Thin repositories (pass-through) for each feature; UI uses *RepositoryProvider.

Implementation:
  profile: ProfileRepository + PublicProfileRepository
  identity: IdentityRepository
  chat: ChatsRepository (provider moved to core_providers)
  home: HomeRepository (weather, participations, suggest)
  Presentation call sites migrated; remotes remain data-only via providers

Verification:
  Grep presentation: no *RemoteSourceProvider except intentional none
  (searchSuggest folded into HomeRepository)

Permanent rule:
  Do not call these remotes from UI while repositories exist.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-GOD-DETAIL-001 — Traceability record

```text
ID: ARCH-GOD-DETAIL-001
Problem:
  event_detail_page.dart (~1200+ lines) owned load + join/favorite/owner
  mutations via local setState (god page).

Decision:
  Extract EventDetailController (StateNotifier family) for load/mutations;
  page retains confirms, SnackBars, navigation. AUTHZ-JOIN gates stay in
  controller (needLogin / needIdentity).

Implementation:
  event_detail_controller.dart
  event_detail_page.dart thinned to watch/controller calls
  EventDetail.copyWith for favorite optimistic updates
  test/features/events/event_detail_controller_test.dart

Verification:
  Controller tests for join gates; flutter analyze clean on detail files.

Permanent rule:
  Do not re-embed event detail repository mutation loops into the page.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-GOD-CREATE-001 — Traceability record

```text
ID: ARCH-GOD-CREATE-001
Problem:
  create_event_page.dart owned catalog load, edit hydrate, maps resolve,
  and submit/publish via local setState (god page).

Decision:
  Extract CreateEventController (family: 0=create, >0=edit). Form
  TextEditingControllers stay on the page; network/catalog/place/submit
  state lives in the controller. AUTHZ create gate = CreateGate.needIdentity.

Implementation:
  create_event_controller.dart
  create_event_page.dart thinned to watch/controller + form fields
  test/features/events/create_event_controller_test.dart

Verification:
  Controller tests for identity gate + isEdit; analyze clean.

Permanent rule:
  Do not re-embed create/edit catalog/submit repository loops into the page.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc
```

---

## ARCH-GOD-HOME-001 — Traceability record

```text
ID: ARCH-GOD-HOME-001
Problem:
  home_page.dart (~1350 lines) inlined many private section widgets
  (categories, hero search, empty/starting-soon/safety/how/create).

Decision:
  Keep HomePage as composition shell. Extract section widgets to
  widgets/home_sections.dart, hero to widgets/home_hero_banner.dart,
  LocationShortcutCard to widgets/location_shortcut_card.dart.
  Feed logic remains HomeNearbyController.

Implementation:
  home_page.dart ~410 lines composition
  widgets/home_sections.dart, home_hero_banner.dart, location_shortcut_card.dart

Verification:
  flutter analyze lib/features/home/presentation — clean
  Grep: no large private section classes in home_page.dart

Permanent rule:
  Do not re-merge extracted home section widgets into home_page.dart.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, flutter-engineering.mdc, FINAL_REMEDIATION_REPORT.md
```

---

## OPS-RELEASE-001 — Traceability record

```text
ID: OPS-RELEASE-001
Problem:
  ARM device QA and Google OAuth client ID are ops/manual gates, not
  app-code defects, but must stay visible so agents do not treat them
  as “done” when only CI unit tests pass.

Decision:
  Keep mandatory checklist in RELEASE.md: physical ARM APK install;
  GOOGLE_SERVER_CLIENT_ID dart-define for production Google login;
  real keystore for store builds (BUILD-001).

Implementation:
  mobile/docs/RELEASE.md checklist (existing) + SoT cross-links

Verification:
  RELEASE.md contains ARM + GOOGLE_SERVER_CLIENT_ID checklist items
  build.gradle.kts has no x86_64-only abiFilters

Permanent rule:
  Do not ship store builds without RELEASE.md QA/ops items addressed.

Files updated (documentation lock):
  AGENT.md, ENGINEERING_CONSTITUTION.md,   FINAL_REMEDIATION_REPORT.md
```

---

## A11Y-RTL-001 — Traceability record

```text
ID: A11Y-RTL-001
Problem:
  Shared chrome used LTR-hardcoded padding/alignment/chevrons; some
  icon-only controls lacked Semantics/tooltips. Full screen a11y was
  deferred debt.

Decision:
  Lock directional helpers + Semantics for shared chrome. Arabic → RTL
  covered by widget tests. Broader Semantics remain incremental when
  touching screens (not a full rewrite).

Implementation:
  core/utils/rtl.dart — directionalChevronForward
  dash_topbar: EdgeInsetsDirectional, AlignmentDirectional, locale Semantics,
    profile tooltip
  chats toast: AlignmentDirectional.bottomEnd
  event_card fill bar: AlignmentDirectional.centerStart + directional chevron
  now_playing / profile / location_shortcut: directional chevrons
  location_shortcut: Semantics button label
  test/core/a11y_rtl_test.dart

Verification:
  flutter test test/core/a11y_rtl_test.dart
  Grep shared chrome for EdgeInsets.only(left:…) / bottomRight toast

Permanent rule:
  Prefer directional APIs; Semantics/tooltip on icon-only chrome.

Files updated (documentation lock):
  MASTER_CONTEXT.md, AGENT.md, ENGINEERING_CONSTITUTION.md,
  FLUTTER_PRINCIPLES.md, DESIGN.md §16 note, flutter-engineering.mdc
```

---

## DO NOT REGRESS

* DO NOT use debug signing for store/production release builds.
* DO NOT silently wire `signingConfig = debug` under `buildTypes.release` without the explicit `-PallowDebugReleaseSigning=true` opt-in.
* DO NOT remove ARM production ABI support (`armeabi-v7a` / `arm64-v8a` via Flutter defaults).
* DO NOT reintroduce `ndk.abiFilters = x86_64` (or emulator-only ABI sets) in `defaultConfig`.
* DO NOT weaken BUILD-001 (or any locked rule) merely to make a build or CI job pass.
* DO NOT re-enable `android:allowBackup` / Auto Backup (`BUILD-002`) without an explicit SoT security decision.
* DO NOT show join/create participation CTAs when `!user.canParticipate` (`AUTHZ-JOIN-001`).
* DO NOT bypass backend authorization with client-only checks.
* DO NOT expose passwords, tokens, or KYC image bytes in UI/logs/toasts.
* DO NOT render password characters in auth theater (`SEC-PRIV-001`).
* DO NOT echo private chat message bodies in global notifications (`SEC-PRIV-002`).
* DO NOT put password-reset tokens in URLs (`SEC-RESET-001`).
* DO NOT skip client upload type/size validation (`SEC-UPLOAD-001`); server remains authoritative.
* DO NOT map session-restore network failures to silent logout while a token remains (`SEC-SESSION-001`).
* DO NOT allow UI-only authorization to replace server authorization.
* DO NOT skip identity/`canParticipate` UX gates for create/join/chat.
* DO NOT allow stale async responses to overwrite newer state (use generation or CancelToken) (`NET-RACE-001` / `NET-CANCEL-001`).
* DO NOT remove Dio send/connect/receive timeouts or typed Failure mapping (`NET-001`).
* DO NOT auto-retry unsafe mutations (join/leave/create/upload/password).
* DO NOT coerce invalid route IDs to `0` (`NAV-001`).
* DO NOT create global polling without lifecycle ownership (cancel on dispose; prevent overlap).
* DO NOT expose raw exceptions (`e.toString()`) as user-facing copy (`ERR-001`); use `mapUnexpectedError` / `failureUserMessage`.
* DO NOT open untrusted/external URLs with unrestricted `launchUrl` (`SEC-URL-001`); use `launchSafeExternalUrl`.
* DO NOT duplicate list favorite optimistic/rollback logic (`ARCH-FAV-001`); reuse `toggleFavoriteInList`.
* DO NOT put Dio/Platform/url_launcher/PackageInfo/Flutter UI in domain (`ARCH-DOMAIN-001`).
* DO NOT call `*RemoteSource` from presentation when the feature has an established repository boundary (auth/events/catalog/**safety/profile/identity/chat/home**).
* DO NOT call `SafetyRemoteSource` from UI (`ARCH-REPO-SAFETY-001`); use `SafetyRepository`.
* DO NOT call Profile/PublicProfile/Identity/Chats/Home remotes from UI (`ARCH-REPO-PROFILE-001` / `ARCH-REPO-IDENTITY-001` / `ARCH-REPO-CHAT-001` / `ARCH-REPO-HOME-001`).
* DO NOT re-embed event detail join/favorite/lifecycle repository loops into `event_detail_page.dart` (`ARCH-GOD-DETAIL-001`); use `EventDetailController`.
* DO NOT re-embed create/edit catalog/maps/submit repository loops into `create_event_page.dart` (`ARCH-GOD-CREATE-001`); use `CreateEventController`.
* DO NOT re-merge extracted home section/hero widgets into `home_page.dart` (`ARCH-GOD-HOME-001`).
* DO NOT treat unit tests alone as store-ready; follow `RELEASE.md` ARM + Google client ID checklist (`OPS-RELEASE-001`).
* DO NOT hard-code LTR-only chevrons/asymmetric padding for shared chrome (`A11Y-RTL-001`); use directional APIs + Semantics/tooltip on icon-only controls.
* DO NOT introduce UseCase layers without a concrete shared problem.
* DO NOT add duplicate architecture (second HTTP client, second token store, second favorite toggle).
* DO NOT skip tests for security/auth/navigation/upload/networking changes.
* DO NOT change locked business rules without updating this constitution + MASTER_CONTEXT + AGENT.md.

---

## Document change protocol

When changing a locked rule:

1. Identify the rule ID.
2. Explain why it must change.
3. List affected features/files.
4. Update `MASTER_CONTEXT.md`, `AGENT.md`, this file, and Flutter principles/DESIGN as needed.
5. Add/update tests.
6. Re-scan for violations.
7. Record the decision in the PR/report.

Silent constitution changes are forbidden.

---

## Reminder

Client guards are **UX / defense-in-depth**.  
**Laravel policies + Sanctum remain authoritative.**
