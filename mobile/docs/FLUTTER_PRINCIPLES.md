# FLUTTER_PRINCIPLES.md

# Fun Mobile — Flutter Principles

Single principles guide for the **Fun** Flutter client in `mobile/`.

Use with:

- `ROADMAP.md` — what to build next
- `docs/DESIGN.md` — visual system (must match Blade)
- `docs/FLUTTER_BEST_PRACTICES.md` — deep production standards (architecture, perf, security detail)
- `docs/postman/` — API contracts

If this file and Best Practices disagree on a stack choice, **this file wins for Fun mobile**.

---

## 1. Product principles

1. **Blade is the UI source of truth.** Do not invent a second visual language. Mirror member Blade screens (`resources/views/**`) adapted for mobile (bottom nav instead of desktop sidebar).
2. **API is the business source of truth.** No business rules only in the client. Sanctum Bearer → `API_BASE_URL` (default production).
3. **Member app only (V1).** No admin panel in Flutter.
4. **Online API first.** Prefer `https://www.funnity.com/api`. Do not hardcode localhost for default builds.
5. **EN + AR with RTL.** All user strings via l10n ARB files.

Primary journeys (same as Blade / DESIGN.md):

- Discover → View → Request to Join → Accepted → Chat → Attend → Rate  
- Create → Activity → Location → Date → Capacity → Publish  

---

## 2. Architecture

```
lib/
├── app/           # FunApp, router, theme
├── core/          # ApiClient, storage, errors, shared widgets
├── features/
│   └── <feature>/
│       ├── data/
│       ├── domain/
│       └── presentation/
└── l10n/
```

| Layer | May use | Must not |
|-------|---------|----------|
| **Domain** | Pure Dart entities, repository contracts | Flutter, Dio, prefs, Platform, url_launcher |
| **Data** | Dio, DTOs, mappers, secure storage | Widgets |
| **Presentation** | Riverpod, UI, go_router | Raw `Dio` / `Map` JSON in widgets; RemoteSource when a repository exists |

**Pragmatic Clean Architecture (locked)**

```
UI → Controller/Notifier (when server-backed) → Repository → RemoteSource
```

* Ephemeral UI (form fields, animations, pickers) may stay in `StatefulWidget`.
* Do **not** introduce UseCases unless multiple controllers share non-trivial domain orchestration.
* Auth / events / catalog use repositories. Prefer repositories for new shared API surfaces.
* See `docs/ENGINEERING_CONSTITUTION.md` for do-not-regress rules.

**Rules**

- Feature-first folders.
- Presentation → repository abstractions for established features.
- `main.dart` thin: bindings + `runApp`.
- Config via `--dart-define` (`API_BASE_URL`, `FLAVOR`, `GOOGLE_SERVER_CLIENT_ID`).

---

## 3. Stack (locked for this app)

| Concern | Choice |
|---------|--------|
| State | `flutter_riverpod` |
| HTTP | `dio` + single `ApiClient` + interceptors |
| Nav | `go_router` (shell + overlay routes) |
| Tokens | `flutter_secure_storage` |
| Images | `cached_network_image` |
| Fonts | `google_fonts` — IBM Plex Sans / Arabic |
| Pickers | `image_picker` |
| Design tokens | `AppColors` / `AppTheme` from `DESIGN.md` |
| API | Sanctum `Authorization: Bearer` + `Accept-Language` |

Do not introduce Bloc/GetX/Provider-as-global-state without an explicit roadmap decision.

---

## 4. UI & design rules

From `DESIGN.md` / Blade:

- Primary indigo `#4F46E5`; accent orange CTAs `#F97316` → `#F59E0B`
- Large radii (`~24`), soft borders, glass/ambient where marketing/auth needs it
- One primary orange CTA per view when possible
- Event cards match `x-events.card` (cover, chips, progress, View Event)
- Member shell = **bottom nav** (Home / Discover / Create / My Events / Profile), not desktop sidebar
- Location affordance must be **discoverable** (Home card + Profile), like Blade sidebar/modal

**Widget rules**

- Prefer small `StatelessWidget`s over `_buildRow()` helpers
- `const` constructors where possible
- Every remote screen: **loading → error (retry) → empty → success**
- Lists: `ListView.builder` / slivers; no huge unbounded columns of cards

---

## 5. Networking (NET-001 / NET-RACE-001 / NET-CANCEL-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md` for full traceability.

- One configured Dio client (`core/network/api_client.dart`)
- Timeouts: connect + send + receive (20s) — **required** (`NET-001`)
- Interceptors: Bearer attach, 401 → clear token + logout
- Map JSON → typed models → UI; never pass `Map<String, dynamic>` into widgets
- Multipart for event image + identity (`id_front`, `id_back`, `selfie`) with client upload validation
- Discover filters: **empty `city_id` = nationwide**; **omitted `city_id` = user city fallback**
- Stale-request strategy: generation counters (discover) (`NET-RACE-001`) and/or `CancelToken` (chat) (`NET-CANCEL-001`)
- Do **not** auto-retry unsafe mutations
- Chat may poll; pollers must cancel on dispose and avoid overlap
- Route IDs: `parsePositiveRouteId` — never coerce to `0` (`NAV-001`)

Typed failures: `NetworkFailure`, `ServerFailure`, `UnauthorizedFailure`, `ForbiddenFailure`, `NotFoundFailure`, `ConflictFailure`, `ValidationFailure`, `RateLimitFailure`, `UnknownFailure`.

Use `failureUserMessage` for SnackBars (prefers first 422 field error).

---

## 6. Auth & security

- Tokens only in secure storage; never `SharedPreferences`
- Logout clears token + provider session
- Never log tokens, passwords, or identity images
- Never render password characters in auth theater digits
- Never put reset tokens in URLs (`GoRouter` `extra` only)
- Never echo chat message bodies in global toasts
- Hide UI by role/status, but **server enforces** authorization
- Gate create/join when `!user.canParticipate` → identity upload (`pending_documents`)
- Session restore: network failure keeps token and offers retry (`AuthSessionRestoreFailed`)
- HTTPS only in production builds
- External links: `http`/`https` only (`launchSafeExternalUrl`)

### Release / Android (BUILD-001 — locked)

See `docs/RELEASE.md` and `docs/ENGINEERING_CONSTITUTION.md` (BUILD-001). Agents must not weaken these rules to make a build pass.

- Store/production releases **MUST** use `android/key.properties` + real upload/production keystore.
- Store/production releases **MUST NOT** silently use debug signing.
- Missing production signing **MUST fail closed**.
- Debug signing for release is allowed **ONLY** with `-PallowDebugReleaseSigning=true` (local/non-store only).
- Do **NOT** set `defaultConfig` `abiFilters` to `x86_64`-only (or other emulator-only sets) without an explicit SoT change.

### Android backup (BUILD-002 — locked)

See `docs/RELEASE.md` and `docs/ENGINEERING_CONSTITUTION.md` (BUILD-002).

- `AndroidManifest.xml` `<application>` **MUST** keep `android:allowBackup="false"` and `android:fullBackupContent="false"`.
- Do **NOT** re-enable Auto Backup without an explicit SoT security decision.

### Participation gates (AUTHZ-JOIN-001 — locked)

See `MASTER_CONTEXT.md` §5 and `docs/ENGINEERING_CONSTITUTION.md` (AUTHZ-JOIN-001).

- Join and create UX **MUST** require `user.canParticipate`; otherwise send the user to identity.
- Client gates are defense-in-depth; **server authorization remains authoritative**.
- Do **NOT** expose join CTAs to authenticated non-participants.

### Sensitive data & session (SEC-* — locked)

See `docs/ENGINEERING_CONSTITUTION.md` (SEC-PRIV-001, SEC-PRIV-002, SEC-RESET-001, SEC-UPLOAD-001, SEC-SESSION-001).

- Never render password characters in auth theater (bullets only).
- Never echo chat message bodies in global toasts/banners.
- Never put reset tokens in URLs (`ResetPasswordArgs` / `extra` only).
- Validate image uploads client-side before multipart; server remains authoritative.
- Session restore: keep token + retry on non-auth failures (`AuthSessionRestoreFailed`); 401 still logs out.

### Networking & navigation (NET-* / NAV-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md` (NET-001, NET-RACE-001, NET-CANCEL-001, NAV-001).

- Dio timeouts + typed `Failure` mapping required; no unsafe auto-retries; no raw `e.toString()` as primary UX.
- Discover concurrent loads must use latest-request-wins generation.
- Chat loads/polls must CancelToken on dispose and prevent overlapping inbox polls.
- Invalid route IDs must not become `0` (`parsePositiveRouteId` + invalid-route page).

### URL / favorites / domain purity (SEC-URL-001 / ARCH-FAV-001 / ARCH-DOMAIN-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md`.

- External opens: `launchSafeExternalUrl` only (http/https).
- List favorites: shared `toggleFavoriteInList` — do not fork duplicate pipelines.
- Domain stays pure (no Dio/Platform/url_launcher/PackageInfo); infra in data layer.

### Error surfacing (ERR-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md`.

- Non-Dio catches: `mapUnexpectedError` → generic `UnknownFailure` — never `ServerFailure(e.toString())`.
- Prefer `failureUserMessage` for user-visible Failure text.
- Dio unknown must not leak raw `error.message`.

### Safety repository (ARCH-REPO-SAFETY-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md`.

- Block/report/listBlocks via `SafetyRepository` / `safetyRepositoryProvider` only — not `SafetyRemoteSource` from UI.

### Repository boundaries (ARCH-REPO-PROFILE/IDENTITY/CHAT/HOME — locked)

See `docs/ENGINEERING_CONSTITUTION.md`.

- Profile / public profile / identity / chats / home: use matching `*Repository` providers from presentation only.

### Event detail controller (ARCH-GOD-DETAIL-001 — locked)

See `docs/ENGINEERING_CONSTITUTION.md`.

- Load/join/favorite/owner mutations via `EventDetailController` / `eventDetailControllerProvider`.
- Page keeps confirms, SnackBars, and navigation only.
- Create/edit catalog+submit via `CreateEventController` / `createEventControllerProvider` (form text fields may stay on the page).
- Home composition in `home_page.dart`; sections/hero in `widgets/` (`ARCH-GOD-HOME-001`); feed via `HomeNearbyController`.
- Store readiness: `RELEASE.md` ARM install + `GOOGLE_SERVER_CLIENT_ID` checklist (`OPS-RELEASE-001`).
- RTL/a11y: directional insets/chevrons (`directionalChevronForward`); Semantics/tooltip on icon-only chrome; AR → RTL (`A11Y-RTL-001`).

---

## 7. State & navigation

- Ephemeral UI (tabs, text fields) → `StatefulWidget`
- Domain/session (auth, feeds) → Riverpod notifiers/providers
- No network calls inside `build()`
- Check `mounted` / `context.mounted` after `await`
- Auth redirects in `go_router`; deep links validate IDs + session

---

## 8. Localization

- All copy in `lib/l10n/app_en.arb` + `app_ar.arb`
- Send `Accept-Language` matching app locale
- Use directional insets (`EdgeInsetsDirectional`, start/end)
- Manually verify RTL for new screens

---

## 9. Testing & quality gate

Before merge / handoff:

```bash
dart format .
flutter analyze .
flutter test
```

Minimum coverage focus: auth session restore, logout token clear, discover filters, join CTA states, error status mapping, upload validation, route id parsing, password theater privacy, favorite optimistic helper.

Locked remediation rules: `docs/ENGINEERING_CONSTITUTION.md`.
Release checklist: `docs/RELEASE.md`.

---

## 10. Roadmap discipline

- Implement only the **first incomplete** tasks in `ROADMAP.md` unless the user scopes otherwise
- After a task: mark `done`, note date/limitations
- UI tasks must name the Blade file they mirror
- Do not mark a Blade-parity task `done` if a listed section is still a placeholder

---

## 11. Quick Do / Don’t

| Do | Don’t |
|----|--------|
| Mirror Blade member screens | Invent admin or new brand colors |
| Feature → data/domain/presentation | Dump everything in `screens/` |
| Repository + typed models | `jsonDecode` in widgets |
| All cities = clear `city_id` | Force nearby-only forever |
| Secure storage for tokens | Token in SharedPreferences |
| Orange for conversion CTAs | Multiple competing primary CTAs |
| Update `ROADMAP.md` | Ship features without roadmap status |

---

## 12. Related docs

| Doc | Role |
|-----|------|
| `docs/ENGINEERING_CONSTITUTION.md` | Locked do-not-regress rules |
| `docs/RELEASE.md` | Store / signing / ABI checklist |
| `ROADMAP.md` | Phases & tasks |
| `docs/DESIGN.md` | Tokens, components, journeys |
| `docs/FLUTTER_BEST_PRACTICES.md` | Full engineering standards |
| `docs/postman/` | HTTP collection + env |
| `README.md` | How to run |

---

*Living document — update when permanent stack or product decisions change.*
