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
4. **Online API first.** Prefer `https://fun.idigital-cs.com/api`. Do not hardcode localhost for default builds.
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
| **Domain** | Pure Dart entities, repository contracts | Flutter, Dio, prefs |
| **Data** | Dio, DTOs, mappers, secure storage | Widgets |
| **Presentation** | Riverpod, UI, go_router | Raw `Dio` / `Map` JSON in widgets |

**Rules**

- Feature-first folders.
- Presentation → repository abstractions (or use cases) only.
- `main.dart` thin: bindings + `runApp`.
- Config via `--dart-define` (`API_BASE_URL`, `FLAVOR`).

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

## 5. Networking

- One configured Dio client (`core/network/api_client.dart`)
- Interceptors: Bearer attach, 401 → clear token + logout, debug logging only
- Map JSON → typed models → UI; never pass `Map<String, dynamic>` into widgets
- Multipart for event image + identity (`id_front`, `id_back`, `selfie`)
- Discover filters: **empty `city_id` = nationwide**; **omitted `city_id` = user city fallback** (Blade “All cities” vs nearby)

Typed failures: `NetworkFailure`, `ServerFailure`, `UnauthorizedFailure`, `ValidationFailure`.

---

## 6. Auth & security

- Tokens only in secure storage; never `SharedPreferences`
- Logout clears token + provider session
- Never log tokens, passwords, or identity images
- Hide UI by role/status, but **server enforces** authorization
- Gate create/join when `user.status != active` → identity upload (`pending_documents`)
- HTTPS only in production builds

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

Minimum coverage focus: auth session, discover filters (all cities vs nearby), join CTA states, error mapping.

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
| `ROADMAP.md` | Phases & tasks |
| `docs/DESIGN.md` | Tokens, components, journeys |
| `docs/FLUTTER_BEST_PRACTICES.md` | Full engineering standards |
| `docs/postman/` | HTTP collection + env |
| `README.md` | How to run |

---

*Living document — update when permanent stack or product decisions change.*
