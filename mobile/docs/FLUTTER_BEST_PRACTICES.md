# Master Guide: Flutter & Dart Production Standards, Rules, and Best Practices

Single, unified reference for building enterprise-grade, scalable, secure, and performant Flutter applications with Dart — including this repo’s `mobile/` client.

---

## Table of Contents

1. [Architecture & Design Principles (Clean Code, OOP, SOLID)](#1-architecture--design-principles-clean-code-oop-solid)
2. [Dart Idioms & Coding Guidelines](#2-dart-idioms--coding-guidelines)
3. [State Management Standards](#3-state-management-standards)
4. [UI & Rendering Performance Optimization](#4-ui--rendering-performance-optimization)
5. [Memory Management & Resource Handling](#5-memory-management--resource-handling)
6. [Caching & Data Persistence Strategies](#6-caching--data-persistence-strategies)
7. [Security & Data Protection](#7-security--data-protection)
8. [Networking & API Integration](#8-networking--api-integration)
9. [Error Handling & Resiliency](#9-error-handling--resiliency)
10. [Testing Strategy](#10-testing-strategy)
11. [Build, Flavors & CI/CD Pipelines](#11-build-flavors--cicd-pipelines)
12. [Navigation](#12-navigation)
13. [Localization & RTL](#13-localization--rtl)
14. [Accessibility](#14-accessibility)
15. [Observability](#15-observability)
16. [Git & PR Hygiene](#16-git--pr-hygiene)
17. [Checklist Before Merge](#17-checklist-before-merge)
18. [Quick Do / Don’t](#18-quick-do--dont)
19. [Stack Notes for This Repo](#19-stack-notes-for-this-repo)

---

## 1. Architecture & Design Principles (Clean Code, OOP, SOLID)

### Clean Architecture Layers

Organize code strictly by **feature** and **layer** for separation of concerns, testability, and maintainability.

```
lib/
├── app/                      # App config, global routes, themes
├── core/                     # Shared utilities, network clients, base widgets, errors
└── features/
    └── feature_name/
        ├── data/             # DTOs, API sources, local sources, repository implementations
        ├── domain/           # Entities, abstract repositories, use cases / interactors
        └── presentation/     # Pages, widgets, state controllers / notifiers
```

| Layer | Responsibility | Must not depend on |
|-------|----------------|--------------------|
| **Domain** (pure Dart) | Entities, repository contracts, use cases, business rules | Flutter UI, Dio, Hive, shared_preferences |
| **Data** | API / DB, DTO ↔ Entity mapping, caching | Widgets |
| **Presentation** | Screens, widgets, UI state | Direct HTTP, raw JSON maps |

**Rules**

- Presentation talks to **use cases** or **repository abstractions** — never to `Dio` directly.
- Domain has **zero** Flutter / third-party data-driver imports.
- `main.dart` stays thin: bindings, DI overrides, `runApp` only.
- Config (base URL, flags) via `--dart-define` / env files — not scattered literals.
- Platform channels / plugins sit behind adapters so features stay testable.

### SOLID Principles in Dart / Flutter

**S — Single Responsibility**

- Widgets render UI and forward interaction to controllers/notifiers.
- Business logic lives in use cases / notifiers — not in UI event handlers.
- One class = one reason to change.

**O — Open / Closed**

- Extend via composition, abstract classes, or interfaces — do not edit core classes for every variant.

**L — Liskov Substitution**

- `MockAuthRepository` and `HttpAuthRepository` must honor the same contract (errors, return types).

**I — Interface Segregation**

- Prefer small interfaces (`UserReader`, `UserWriter`) over a god `UserManager`.

**D — Dependency Inversion**

- High-level modules depend on abstractions.
- Inject with Riverpod / Provider / `get_it` — swap implementations in tests via overrides.

### OOP Habits

- Prefer **composition over inheritance** for widgets.
- Immutable models (`const`, `copyWith`, `equatable` / `freezed`).
- Encapsulate invariants in entities (e.g. capacity ≥ members).
- Avoid utility God classes; group by domain.

---

## 2. Dart Idioms & Coding Guidelines

### Immutability & Declarations

- Prefer **`const` constructors** for widgets and models whenever possible.
- Use **`final` by default**; `var` only when reassignment is required.
- Use **`freezed` or `equatable`** for deep equality on entities and state objects.

### Type Safety & Null Safety

- Avoid **`late`** unless initialization is guaranteed before access (e.g. `AnimationController` in `initState`).
- Avoid forced unwrap **`!`** unless null is proven.
- Prefer Dart 3 **sealed classes** + exhaustive `switch` for states:

```dart
sealed class AuthState {}
class Unauthenticated extends AuthState {}
class Authenticated extends AuthState {
  Authenticated(this.user);
  final User user;
}

Widget buildUI(AuthState state) => switch (state) {
  Unauthenticated() => const LoginScreen(),
  Authenticated(:final user) => HomeScreen(user: user),
};
```

### Naming Conventions

| Kind | Style | Example |
|------|--------|---------|
| Variables, methods, params | `lowerCamelCase` | `isLoading`, `fetchEvents` |
| Classes, enums, mixins, extensions, typedefs | `UpperCamelCase` | `EventRepository` |
| Files, packages, directories | `lowercase_with_underscores` | `event_repository.dart` |
| Private members | leading `_` | `_api`, `_load()` |
| Booleans | `is` / `has` / `can` | `canJoin`, `hasMore` |

### Functions & Widgets

- Short functions; early returns; one job each.
- Extract widgets when `build` exceeds ~80–100 lines.
- **Do not** use private helper methods that return widgets (`Widget _buildRow()`) — they share the parent `BuildContext` and rebuild with the parent. Use a separate `StatelessWidget` instead.
- Delete dead code; comments explain **why**, not what.
- Public APIs get `///` dartdoc.
- Enforce with `dart format` + `flutter analyze` (zero issues on PRs).

---

## 3. State Management Standards

### Unidirectional Data Flow (UDF)

- State flows **down** to the UI; events flow **up** to handlers/notifiers.
- Never mutate state in place — always emit a new copy (`state = state.copyWith(...)`).

### Local vs Global

| Kind | Examples | Where |
|------|----------|--------|
| **Ephemeral** | Tab index, scroll offset, hover, text field focus | `StatefulWidget` / `HookWidget` |
| **App / domain** | Auth session, locale, event lists | Riverpod / Bloc / Provider |

### Rules

- **Keep widgets dumb:** no network, DB, or heavy transforms inside widgets.
- UI observes state; controllers mutate it.
- Side effects (API, navigation, snackbars) live in notifiers or explicit handlers — **not** in `build()`.
- Use `ref.watch(provider.select(...))` to limit rebuilds.
- Dispose controllers / subscriptions in `dispose` / `ref.onDispose`.
- Never use `BuildContext` across `await` without `context.mounted` / `mounted`.

### This project

- Prefer **Riverpod** (`StateNotifier` / `AsyncNotifier` / `FutureProvider`).
- Override providers in `main` and in tests.

### Anti-patterns

- Global singletons for session.
- `setState` for app-wide auth/locale.
- Fetching inside `build()` without a provider/cache.
- Direct Dio calls from widgets.

---

## 4. UI & Rendering Performance Optimization

### Rebuild Optimization

- Break large `build()` trees into small `StatelessWidget`s to localize rebuilds.
- Prefer widget classes over `_buildXxx()` helper methods.
- Use **`const`** aggressively.
- Wrap expensive / frequently redrawn subtrees (custom paint, heavy animation) in **`RepaintBoundary`**.
- Avoid `Opacity` / heavy effects inside long scrolling lists when a simpler fade works.

### List Rendering

- Always use **`ListView.builder`** / **`GridView.builder`** / slivers for dynamic or large collections.
- Provide **`itemExtent`** or **`prototypeItem`** when sizes are fixed to skip layout measurement:

```dart
ListView.builder(
  itemCount: items.length,
  itemExtent: 72.0,
  itemBuilder: (context, index) => ItemTile(item: items[index]),
);
```

- Stable keys when filtering/reordering.
- Paginate feeds; never unbounded loads.

### Images & Assets

- Compress assets; correct resolutions.
- `cached_network_image` with `memCacheWidth` / `memCacheHeight` when display size is known.
- Prefetch only the next screen’s critical assets.

### Startup

- Defer non-critical init until after first frame.
- Parallelize network calls on splash when safe.

### Profiling

- Use Flutter DevTools (CPU, memory, rebuild profiler).
- Validate on mid-tier Android, not only flagships.

---

## 5. Memory Management & Resource Handling

### Explicit Disposal

Always dispose in `dispose()` / `ref.onDispose`:

- `TextEditingController`
- `ScrollController`
- `AnimationController`
- `StreamController` / `StreamSubscription`
- `FocusNode`
- `ChangeNotifier` listeners
- Timer / ticker

### Offload Heavy Work (Isolates)

Heavy CPU work (large JSON parse, image processing, crypto) must leave the UI isolate:

```dart
Future<List<DataModel>> parseLargeJson(String jsonString) async {
  return Isolate.run(() {
    final parsed = jsonDecode(jsonString) as List<dynamic>;
    return parsed
        .map((e) => DataModel.fromJson(e as Map<String, dynamic>))
        .toList();
  });
}
```

Also acceptable: `compute(...)` for simpler one-shot work.

### Leak Prevention

- Cancel in-flight HTTP when leaving a page if results are discarded.
- Do not retain `BuildContext`, `State`, or huge lists in long-lived singletons.

---

## 6. Caching & Data Persistence Strategies

### Multi-Tier Architecture

1. **In-memory** — RAM / Riverpod state for the active session (fastest).
2. **Local storage** — offline / cold start (secure or plain depending on data).
3. **Remote** — REST / GraphQL source of truth.

Pattern: **stale-while-revalidate** when product allows (show cache → refresh).

### Tool Selection

| Need | Tool |
|------|------|
| JWT / secrets | `flutter_secure_storage` (Keychain / Keystore) |
| Flags, theme, locale | `shared_preferences` / `hive` |
| Queryable offline data | `isar` / `drift` (SQLite) |
| Images | `cached_network_image` (+ memory bounds) |

### Rules

- Cache keys include **locale** and filters (`cityId`, `categoryId`, query).
- Invalidate on: logout, locale change, successful mutations (create / join / leave).
- TTL for dynamic feeds; avoid infinite caches for event lists.
- **Never** store tokens in plain `SharedPreferences`.
- Define offline UX explicitly (read-only vs fail).

---

## 7. Security & Data Protection

### Key Management & Configuration

- **Never hardcode** API secrets or private tokens in Dart sources.
- Inject at compile time: `--dart-define`, `--dart-define-from-file=config.json`, or native vaults.
- Assume anything in the client binary can be extracted.

### Secure Storage & Encryption

| Data | Store |
|------|--------|
| Access / refresh tokens | `flutter_secure_storage` |
| Prefs (locale, theme) | `shared_preferences` / Hive |
| PII cache | Encrypted storage or do not cache |

- **Logout must scrub** memory caches + secure disk storage + provider state.

### Network Security

- HTTPS / TLS 1.2+ only in production.
- Disable cleartext HTTP in `AndroidManifest.xml` / `Info.plist` for release.
- Optional **certificate / public-key pinning** for high-risk apps (with a pin-rotation process).
- Do not disable certificate validation.

### AuthZ

- Hide admin UI by role, but **server enforces** authorization.
- Treat deep links as untrusted (validate IDs + session).

### Application Integrity

```bash
flutter build apk --release --obfuscate --split-debug-info=./build/debug-symbols
```

- Keep debug symbols for crash deobfuscation.
- Optional: root/jailbreak detection; block screenshots on highly sensitive screens.
- Minimize OS permissions; justify each in store listings.

### Logging

- Never log tokens, passwords, or identity documents.
- Redact PII in Crashlytics / Sentry.

### Input & WebView

- Validate forms client-side; still validate server-side.
- Restrict WebView navigation; disable JS unless required.

---

## 8. Networking & API Integration

### Centralized HTTP Client

One configured **Dio** (or `http.Client`) for the app:

- Base URL from config
- Connect / receive timeouts
- Default headers (`Accept`, `Accept-Language`)

### Interceptors

| Interceptor | Role |
|-------------|------|
| **Auth** | Attach `Authorization: Bearer …` |
| **Unauthorized** | On 401: clear session once, redirect login (no loops) |
| **Retry** | Exponential backoff for transient 5xx / network (idempotent GETs only) |
| **Logging** | Verbose in **debug only**; disabled in production |

### Contracts

- Map JSON → typed DTOs (`json_serializable` / `freezed`) → domain entities.
- Never pass `Map<String, dynamic>` into UI.
- Explicit pagination (`page`, `hasMore`, append vs replace).
- Multipart uploads: validate type/size before send; show progress.

### Auth

- Tokens only over HTTPS.
- Prefer secure storage (migrate off plain prefs if still used).

---

## 9. Error Handling & Resiliency

### Typed / Functional Failures

Avoid leaking untyped exceptions across layers. Prefer result types (`Either<Failure, T>` via `fpdart` / `dartz`) or a project `Result` / `ApiException` hierarchy:

```dart
abstract class Failure {
  const Failure(this.message);
  final String message;
}

class ServerFailure extends Failure {
  const ServerFailure([super.message = 'Server Error']);
}

Future<Either<Failure, User>> getUser(String id) async {
  try {
    final user = await api.fetchUser(id);
    return Right(user);
  } on DioException catch (e) {
    return Left(mapDioException(e));
  } catch (e) {
    // ERR-001: never Left(ServerFailure(e.toString()))
    return Left(mapUnexpectedError(e));
  }
}
```

Map once in the client: offline, timeout, 401, 403, 404, 422 (validation), 5xx. Non-Dio parse failures → `mapUnexpectedError` / `UnknownFailure` (generic message only).

### Retries

- Retry idempotent GETs with backoff.
- Do **not** blind-retry non-idempotent POSTs (payments, join) without idempotency keys.

### Global Error Zones

```dart
void main() {
  FlutterError.onError = (details) {
    FlutterError.presentError(details);
    // Forward to Crashlytics / Sentry
  };

  PlatformDispatcher.instance.onError = (error, stack) {
    // Unhandled async errors → reporter
    return true;
  };

  runApp(const MyApp());
}
```

### UI Contract

Every remote screen: **loading → error (retry) → empty → success**.

---

## 10. Testing Strategy

| Layer | Focus | Tools |
|-------|--------|--------|
| **Unit** (Domain/Data target **> 80%**) | Use cases, mappings, notifiers | `test`, `mocktail` |
| **Widget** | Interactions, empty/error UI, layout | `flutter_test` |
| **Integration** | Auth → discover → join journeys | `integration_test` + device/emulator |
| **Golden** (optional) | Design-sensitive widgets | golden tests |

### Rules

- Override Riverpod providers — never hit production API in unit/widget tests.
- Deterministic clocks / locales.
- Cover auth, join/leave, and error mapping first — not vanity 100% line coverage.

---

## 11. Build, Flavors & CI/CD Pipelines

### Flavors / Environments

Maintain **dev / staging / prod** with separate:

- Bundle / application IDs
- App display names
- API base URLs
- Icons (optional)

Example iOS configs:

```
ios/Runner/Configs/
├── dev.xcconfig
├── staging.xcconfig
└── prod.xcconfig
```

Android: product flavors + `--dart-define=API_BASE_URL=...` (or define-from-file).

**Never** ship a store build pointed at localhost.

### Release Builds

```bash
flutter build apk --release --obfuscate --split-debug-info=./build/debug-symbols \
  --dart-define=API_BASE_URL=https://www.funnity.com/api

flutter build appbundle --release ...
flutter build ipa --release ...
```

- Version via `pubspec.yaml` (`x.y.z+build`).
- Signing keys only in CI secrets — never commit keystores.

### CI/CD Automation (PRs to main)

```bash
dart format --output=none --set-exit-if-changed .
flutter analyze .
flutter test --coverage
```

Automate signed builds via Fastlane / GitHub Actions / Codemagic / Bitrise → Play internal track / TestFlight.

---

## 12. Navigation

- Single declarative router (`go_router` / Navigator 2.0).
- Central auth redirects (guest → login; pending identity → upload).
- Path params for IDs; refresh by id rather than passing stale full objects.
- Use `push` vs `go` deliberately (stack vs replace).
- Friendly fallback for unknown routes.

---

## 13. Localization & RTL

- All user-visible strings via l10n (ARB / `intl`).
- Support `en` + `ar`; drive `Directionality` from locale.
- Prefer directional insets (`EdgeInsetsDirectional`, start/end thinking).
- Format dates/numbers with `intl` + active locale.
- Send matching `Accept-Language` on API calls.
- Manually verify RTL for new screens (nav, chips, back affordances).

---

## 14. Accessibility

- Semantics labels on icon-only controls.
- Contrast ≥ WCAG AA on brand colors.
- Tap targets ≥ 48×48 dp.
- Survive system font scale 1.5–2.0× without clipping.
- Focus order matches visual order (especially RTL).

---

## 15. Observability

- Crash reporting in release (Crashlytics / Sentry).
- Breadcrumbs for navigation + API failures (no secrets).
- Consistent analytics event names (`event_join_requested`).
- Feature flags for risky rollouts when available.

---

## 16. Git & PR Hygiene

- Small PRs: one feature or fix.
- Do not commit: `build/`, `.dart_tool/`, keystores, `.env`, IDE junk.
- Before push: format → analyze → test.
- Update this guide when the team adopts a permanent new pattern.

---

## 17. Checklist Before Merge

- [ ] No secrets in source or logs  
- [ ] Tokens in secure storage; logout clears session  
- [ ] Loading / empty / error / success handled  
- [ ] Lists virtualized; images cached with bounds when possible  
- [ ] No widget helpers returning widgets; prefer small `StatelessWidget`s  
- [ ] Controllers / subscriptions disposed  
- [ ] Heavy parse/CPU off UI isolate when needed  
- [ ] API errors mapped; no raw Dio in UI  
- [ ] EN/AR + RTL checked for new UI  
- [ ] Unit/widget tests for new business logic  
- [ ] `flutter analyze` clean  
- [ ] Flavor / dart-defines documented for the environment  

---

## 18. Quick Do / Don’t

| Do | Don’t |
|----|--------|
| Feature → data / domain / presentation | Giant `helpers.dart` / `screens/` dump |
| Repository + typed DTOs/entities | `jsonDecode` inside widgets |
| Riverpod (or Bloc) for domain state | Globals for session |
| Secure storage for tokens | Token in SharedPreferences |
| `ListView.builder` + `itemExtent` when fixed | Huge `Column` of cards |
| Separate widgets (not `_buildRow()`) | Giant single `build()` |
| `Isolate.run` / `compute` for heavy work | Block UI isolate |
| Central theme tokens | Random hex per screen |
| Typed `Failure` / `ApiException` | `catch (_)` and ignore |
| HTTPS + server-side authZ | Trust client-only role checks |
| Obfuscated release + debug symbols | Unsigned / debug store builds |

---

## 19. Stack Notes for This Repo

| Concern | Choice |
|---------|--------|
| State | `flutter_riverpod` |
| HTTP | `dio` + `ApiClient` |
| Nav | `go_router` |
| Images | `cached_network_image` |
| Fonts | `google_fonts` (IBM Plex Sans) |
| API | Sanctum Bearer → `https://www.funnity.com/api` |
| Design | Root `DESIGN.md` |
| Roadmap | `ROADMAP.md` (Flutter tasks F2+) |

**Current gap vs this guide (intentional v1):** tokens still use SharedPreferences in the scaffold — migrate to `flutter_secure_storage` before production store release. Prefer introducing `domain/` use cases as features grow past the thin repository pattern.

---

*Living document — update when architecture decisions change.*
