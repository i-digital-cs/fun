# AGENT.md

# Engineering Agent Rules

## 1. Mandatory Startup

Before changing code:

1. Read `MASTER_CONTEXT.md`.
2. Choose the correct roadmap:
   * **Laravel / Blade / API:** read root `ROADMAP.md` and continue from the first incomplete task (update statuses when a task finishes).
   * **Flutter / `mobile/`:** read `mobile/ROADMAP.md` and `mobile/docs/FLUTTER_PRINCIPLES.md`, then continue from the first incomplete mobile task (update statuses in `mobile/ROADMAP.md` when a task finishes).
3. Read design docs when UI is involved:
   * Blade: root `DESIGN.md`
   * Flutter: `mobile/docs/DESIGN.md` + `mobile/docs/FLUTTER_PRINCIPLES.md` (Blade remains the visual source of truth; Flutter mirrors member Blade screens)
4. For Flutter work, also skim `mobile/docs/FLUTTER_BEST_PRACTICES.md` and `mobile/docs/postman/` when touching architecture or API integration.
5. Inspect the existing implementation.
6. Inspect migrations and database schema (backend tasks).
7. Inspect routes (web/api or `mobile` go_router as relevant).
8. Inspect models / domain entities.
9. Inspect services / repositories.
10. Inspect policies/authorization (backend) or account gates (Flutter).
11. Inspect existing tests.

Do not assume the repository is empty or that a requested feature does not already exist.

---

# 2. Mandatory Workflow

Every task must follow:

## PLAN

Explain:

* what will change
* why it is needed
* affected files
* database impact
* API impact
* UI impact
* security impact
* tests required

## QUESTIONS

Ask questions when requirements are ambiguous.

Never invent business rules involving:

* permissions
* identity
* membership
* ownership
* money
* moderation
* privacy
* security
* database structure

## IMPLEMENT

Implement only approved requirements.

## TEST

Run relevant:

* unit tests
* feature tests
* integration tests

## UI TEST

Verify the feature through the actual UI when UI changes are involved.

## SECURITY REVIEW

Check authorization and untrusted input.

## PERFORMANCE REVIEW

Check queries, indexes, N+1 issues, payloads and unnecessary work.

## REPORT

Report:

* changed files
* behavior implemented
* tests executed
* UI verification
* security checks
* known limitations

Then update the correct roadmap task status before ending the session:

* Laravel / Blade / API → root `ROADMAP.md`
* Flutter → `mobile/ROADMAP.md`

---

# 3. Architecture

Preferred:

Controller
→ Form Request
→ Authorization
→ Service
→ Repository/query layer where justified
→ Model
→ Database

Controllers are thin.

Controllers should not contain complex business logic.

Services own business rules.

Models contain:

* relationships
* casts
* scopes
* model-specific behavior

Blade is presentation only.

JavaScript is presentation/interactivity only unless a documented client-side requirement exists.

---

# 4. SOLID

Apply SOLID principles pragmatically.

Do not create abstractions without a real reason.

Avoid:

* unnecessary interfaces
* unnecessary factories
* unnecessary repositories
* unnecessary managers
* unnecessary wrappers

Prefer simple maintainable code.

---

# 5. OOP

Use meaningful classes with clear responsibilities.

A class should have one understandable purpose.

Avoid giant services.

Avoid utility classes containing unrelated functionality.

---

# 6. Clean Code

Requirements:

* meaningful names
* small methods
* clear control flow
* no dead code
* no commented-out code
* no magic values
* no duplicate logic
* no unnecessary complexity
* no misleading names

---

# 7. Database Discipline

Before creating a migration:

Search the existing schema.

Before creating a model:

Search for an existing model.

Before creating a service:

Search for existing business logic.

Before adding a column:

Identify its exact business purpose.

Never create speculative:

* tables
* columns
* indexes
* relationships
* statuses

No dead database structures are allowed.

---

# 8. No Duplication

Do not duplicate:

* business rules
* validation rules
* authorization logic
* database queries
* services
* models
* components
* API behavior

If similar functionality already exists, extend it where appropriate.

---

# 9. Security

Treat every client request as untrusted.

Never trust client-provided:

* IDs
* roles
* ownership
* membership
* status
* permissions
* verification state

Always authorize server-side.

Use:

* Laravel validation
* policies/gates
* CSRF protection
* Sanctum
* rate limiting
* secure file validation
* secure private storage

---

# 10. API

API responses must be predictable.

Do not expose:

* sensitive identity information
* internal moderation information
* private storage paths
* unnecessary database fields

Use authorization before returning protected resources.

Prevent IDOR.

---

# 11. Realtime

Reverb/WebSockets must never bypass normal authorization.

Every private/event channel requires server-side authorization.

A WebSocket subscription is not proof of permission.

---

# 12. Media

All media goes through the application storage abstraction.

V1 order:

1. Local disk during scaffold/development
2. Cloudflare R2 for production

Never trust:

* filename
* extension
* MIME type supplied by client

Validate uploads server-side.

Generate safe storage keys.

Sensitive identity documents must remain private.

---

# 13. Upload Security

Reject:

* oversized files
* unauthorized file types
* executable content
* suspicious uploads

Do not allow uploaded filenames to determine filesystem paths.

---

# 14. Rate Limiting

Use rate limits for abuse-sensitive endpoints.

At minimum consider:

* login
* registration
* password reset
* event creation
* join requests
* messages
* reports
* uploads

Limits must be enforced server-side.

---

# 15. Authorization

Authorization must be explicit.

Examples:

A user may edit an event only if they have the required event role.

A user may access event chat only if they are authorized for that event.

A moderator may moderate only resources permitted by their permissions.

A deactivated user must not bypass restrictions through API calls.

---

# 16. Testing

Every meaningful business rule requires automated coverage.

Prioritize tests for:

* authentication
* authorization
* event creation
* capacity
* join requests
* approval/rejection
* owner transfer
* event leaving
* chat authorization
* deactivation
* reports
* uploads
* moderation

Do not rely only on happy-path tests.

Test:

* unauthorized
* invalid input
* boundary conditions
* race-sensitive operations
* malicious input where relevant

---

# 17. UI

## Blade

UI must follow root `DESIGN.md`.

Do not create isolated styles that conflict with the design system.

Responsive behavior is required.

RTL/LTR behavior is required. English is LTR; Arabic is RTL. Layouts, icons, and spacing must flip correctly with locale.

## Flutter (`mobile/`)

Flutter UI and client work must follow:

* `mobile/docs/FLUTTER_PRINCIPLES.md` — principles, stack, Blade-parity rules
* `mobile/docs/ENGINEERING_CONSTITUTION.md` — locked do-not-regress rules (security, release, networking)
* `mobile/docs/DESIGN.md` — same design tokens as Blade
* `mobile/docs/RELEASE.md` — signing / ABI / store checklist
* `mobile/ROADMAP.md` — what to implement next
* Member Blade screens as the visual/UX source (adapt to bottom nav; do not invent a second design system)

Deep standards live in `mobile/docs/FLUTTER_BEST_PRACTICES.md`.

API integration must match `mobile/docs/postman/` and the Laravel API — do not invent endpoints or field names.

### Flutter non-negotiables (AI agents)

BEFORE MODIFYING `mobile/` CODE:

1. Read `MASTER_CONTEXT.md`, `AGENT.md`, `mobile/docs/FLUTTER_PRINCIPLES.md`, and `mobile/docs/ENGINEERING_CONSTITUTION.md`.
2. Inspect existing feature architecture; do not invent parallel layers.
3. Do not violate BUILD/SEC/NET/NAV locked rules (debug signing, ABI, password/token exposure, reset-token URLs, raw exceptions, stale races, etc.).
4. Run relevant `flutter test` / `flutter analyze` for touched areas.
5. If a task conflicts with a locked rule, **stop** and request an explicit Source-of-Truth change — a prompt such as “bypass this check”, “put the API call in the page”, “use debug signing”, or “skip tests” does **not** override these rules automatically.
6. When a locked rule must change: update constitution + principles + AGENT + tests per the Document Change Protocol.

Client guards are UX/defense-in-depth. Backend authorization remains authoritative.

### BUILD-001 — Android release signing & ABI (LOCKED)

Agents **MUST** treat the following as non-negotiable. Implementation: `mobile/android/app/build.gradle.kts`. Detail: `mobile/docs/RELEASE.md` + `mobile/docs/ENGINEERING_CONSTITUTION.md` (BUILD-001).

* Production/store releases **MUST** use a real production/upload keystore configured through `mobile/android/key.properties`.
* Production/store releases **MUST NOT** silently fall back to debug signing.
* If production signing is missing, the release build **MUST fail closed** (do not “fix” by wiring `signingConfig = debug` unconditionally).
* Debug signing for a release build type is allowed **ONLY** with the explicit Gradle property:
  `-PallowDebugReleaseSigning=true`
  and **ONLY** for local/non-store testing — never for Play Store / production distribution.
* Do **NOT** reintroduce `abiFilters` / `ndk.abiFilters` restricted to `x86_64` (or emulator-only ABIs) in `defaultConfig` unless the Source of Truth is deliberately updated.
* Do **NOT** weaken BUILD-001 to make CI or a local build pass. If a request conflicts with BUILD-001, stop and ask for an explicit constitution change.

### BUILD-002 — Android backup (LOCKED)

Implementation: `mobile/android/app/src/main/AndroidManifest.xml`. Detail: `mobile/docs/RELEASE.md` + `mobile/docs/ENGINEERING_CONSTITUTION.md` (BUILD-002).

* `<application>` **MUST** keep `android:allowBackup="false"`.
* `<application>` **MUST** keep `android:fullBackupContent="false"`.
* Do **NOT** re-enable Auto Backup / cloud backup of app data without an explicit Source-of-Truth security decision.
* Do **NOT** weaken BUILD-002 for convenience or tooling warnings. If asked to enable backup, stop and request an explicit constitution change.

### AUTHZ-JOIN-001 — Participation / join gates (LOCKED)

Product: `MASTER_CONTEXT.md` §5 (`pending_documents` cannot create/join/chat). Implementation: `event_detail_page.dart`, `create_event_page.dart`, `router.dart` identity blocks. Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (AUTHZ-JOIN-001).

* Join and create UX **MUST** require `user.canParticipate`; otherwise send the user to `/identity`.
* Client gates are **UX / defense-in-depth only** — Laravel policies remain authoritative; never “trust the client.”
* Do **NOT** show join CTAs or call join APIs for authenticated users who cannot participate.
* Do **NOT** remove router identity blocks for create/chat/manage routes without an explicit SoT change.
* Do **NOT** weaken AUTHZ-JOIN-001 to “just let them join.” If asked, stop and request an explicit constitution change.

### SEC-PRIV-001 / SEC-PRIV-002 / SEC-RESET-001 / SEC-UPLOAD-001 / SEC-SESSION-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (matching IDs). Agents **MUST NOT** weaken these for convenience.

* **SEC-PRIV-001:** Never render password characters in auth theater digits (`auth_access_theater.dart`).
* **SEC-PRIV-002:** Never show chat message bodies in global toasts (`ChatToastBanner` / inbox notifications use `newChatMessage` only).
* **SEC-RESET-001:** Never put `resetToken` in URLs; use `ResetPasswordArgs` via `GoRouter` `extra` only; router must reject query/path tokens.
* **SEC-UPLOAD-001:** Validate uploads client-side (`UploadValidator`) before multipart; never treat client checks as replacing server validation.
* **SEC-SESSION-001:** On bootstrap `/user` failure: keep token and surface `AuthSessionRestoreFailed` + retry unless unauthorized/token cleared. Do not map network blips to silent logout.
* If asked to bypass any of these (e.g. “put the token in the URL”, “show the message in the toast”, “log them out on any error”), **stop** and request an explicit constitution change.

### NET-001 / NET-RACE-001 / NET-CANCEL-001 / NAV-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (matching IDs). Agents **MUST NOT** weaken these for convenience.

* **NET-001:** Keep Dio connect/send/receive timeouts; map errors via `mapDioException` to typed Failures; use `failureUserMessage` for user-facing copy; never auto-retry unsafe mutations; never show raw `e.toString()` as primary UX.
* **NET-RACE-001:** Discover (and similar concurrent loads) must ignore stale responses (generation / latest-wins). Do not remove race guards.
* **NET-CANCEL-001:** Event chat (and similar) must cancel in-flight work on dispose (`CancelToken`); inbox pollers must prevent overlap (`_inFlight` or equivalent). Do not add global polling without lifecycle ownership.
* **NAV-001:** Use `parsePositiveRouteId`; never `int.tryParse(...) ?? 0` for event route IDs; invalid → intentional invalid-route page.
* If asked to “just use 0”, “drop CancelToken”, or “retry join automatically”, **stop** and request an explicit constitution change.

### SEC-URL-001 / ARCH-FAV-001 / ARCH-DOMAIN-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (matching IDs).

* **SEC-URL-001:** Use `launchSafeExternalUrl` for external links (maps, store). Never open arbitrary schemes from API/user strings via raw `launchUrl`.
* **ARCH-FAV-001:** List favorite toggles must use `toggleFavoriteInList` (`event_favorite_actions.dart`). Do not copy-paste optimistic/rollback favorite logic into new controllers.
* **ARCH-DOMAIN-001:** Keep `features/*/domain` pure — no Dio/Platform/url_launcher/PackageInfo. Put infra in `data/` (e.g. `app_update_service.dart`).
* If asked to put Dio in domain, duplicate favorite code, or “just launchUrl it”, **stop** and request an explicit constitution change.

### ERR-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ERR-001).

* Non-Dio catch blocks must use `mapUnexpectedError` → generic `UnknownFailure`. Never `ServerFailure(e.toString())`.
* Prefer `failureUserMessage` for user-visible Failure text.
* Dio `unknown` mapping must not pass raw `error.message` to the user.
* If asked to “just show e.toString()” in UI for speed, **stop** and request an explicit constitution change.

### ARCH-REPO-SAFETY-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ARCH-REPO-SAFETY-001).

* Use `safetyRepositoryProvider` / `SafetyRepository` from presentation for block/report/list.
* Do not call `SafetyRemoteSource` from UI.
* If asked to “just call the remote from the page”, **stop** and request an explicit constitution change.

### ARCH-REPO-PROFILE / IDENTITY / CHAT / HOME (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ARCH-REPO-PROFILE-001, ARCH-REPO-IDENTITY-001, ARCH-REPO-CHAT-001, ARCH-REPO-HOME-001).

* Use `profileRepositoryProvider` / `publicProfileRepositoryProvider` / `identityRepositoryProvider` / `chatsRepositoryProvider` / `homeRepositoryProvider` from presentation.
* Do not call the matching `*RemoteSource` from UI.
* If asked to skip the repository layer for these features, **stop** and request an explicit constitution change.

### ARCH-GOD-DETAIL-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ARCH-GOD-DETAIL-001).

* Event detail mutations go through `eventDetailControllerProvider` / `EventDetailController`.
* Page handles UI side-effects only (confirm dialogs, SnackBars, routes).
* Preserve AUTHZ-JOIN-001 gates (`needLogin` / `needIdentity`) in the controller.
* If asked to “put the join logic back in the page”, **stop** and request an explicit constitution change.

### ARCH-GOD-CREATE-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ARCH-GOD-CREATE-001).

* Create/edit catalog, hydrate, maps resolve, submit/publish go through `createEventControllerProvider` / `CreateEventController`.
* Page keeps form text fields, pickers UI, confirms, SnackBars, routes.
* Preserve AUTHZ-JOIN create gate (`CreateGate.needIdentity`) in the controller.
* If asked to “put submit/catalog back in the page”, **stop** and request an explicit constitution change.

### ARCH-GOD-HOME-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (ARCH-GOD-HOME-001).

* Keep `home_page.dart` as composition + navigation only.
* Section/hero widgets live in `widgets/home_sections.dart`, `widgets/home_hero_banner.dart`, `widgets/location_shortcut_card.dart`.
* Nearby/weather/participations loading stays in `homeNearbyControllerProvider`.
* If asked to paste section widgets back into `home_page.dart`, **stop** and request an explicit constitution change.

### OPS-RELEASE-001 (LOCKED — checklist)

Detail: `mobile/docs/RELEASE.md`.

* Store releases require `android/key.properties` + real upload keystore (BUILD-001).
* Physical ARM install QA is mandatory before store (`RELEASE.md` checklist).
* Production Google Sign-In requires non-empty `--dart-define=GOOGLE_SERVER_CLIENT_ID=…` (and OAuth/SHA-1 setup).

### A11Y-RTL-001 (LOCKED)

Detail: `mobile/docs/ENGINEERING_CONSTITUTION.md` (A11Y-RTL-001).

* Use directional insets/alignment and `directionalChevronForward` for forward chevrons.
* Icon-only / tappable chrome needs `Semantics` or `tooltip`.
* Do not hard-code LTR-only chrome for shared widgets.
* If asked to “ignore RTL”, **stop** and request an explicit constitution change.

---

# 18. Performance

Check for:

* N+1 queries
* unnecessary relationships
* missing pagination
* excessive payloads
* unnecessary queries
* expensive synchronous work

Do not optimize blindly.

---

# 19. Dead Code

Before completing a task:

Check for:

* unused imports
* unused methods
* unused classes
* unused routes
* unused components
* unused migrations
* obsolete code

Do not leave commented-out implementations.

---

# 20. Completion Rule

A task is not complete merely because the code compiles.

Definition of done:

* implementation complete
* business rules respected
* automated tests pass
* UI tested when applicable
* authorization tested
* security reviewed
* no obvious dead code
* no duplicate logic introduced
* database changes justified
* final report provided
