# Funnity V2.1 — Social Discovery, Geo Discovery & Navigation Audit

**Date:** 28 October 2026  
**Branch inspected:** `feature/funnity-v2-homepage` (V2 visual work preserved; uncommitted screenshot/report files present)  
**Scope:** Audit only — **no feature implementation** in this deliverable  
**Verdict: AUDIT COMPLETE**

Legend:

- **CONFIRMED** — verified in code and/or read-only DB probes  
- **ASSUMPTION** — inferred; needs product or manual verification  
- **RISK** — plausible defect from architecture; needs scenario test before claiming a bug fix  

---

## 1. Executive summary

Funnity already has a **production-grade friends + DM stack** and a **city-centric nearby event discovery** pipeline. It does **not** yet have interest-based people discovery, event-level distance ranking, “Worth the Trip,” or a documented cross-platform navigation contract.

| Area | Status | Headline |
| --- | --- | --- |
| Friends / Add Friend | Mature | Request/accept/decline/cancel/unfriend + blocks + web/Flutter parity |
| People discovery (“may like”) | Missing | Search is **public_id only**; no shared-interest graph |
| Geo discovery | Partial | Haversine on **city centroids**; radii 5–50 km; nationwide soft fallback exists |
| Option B (nearby + expansion + worth-the-trip) | Not implemented | Needs new discovery sections + API contract |
| Navigation / back | Fragile | GoRouter shell + many `context.go` resets; Flutter login loses intended destination |

Read-only DB probe (28 Oct 2026):

- Events: **557** total, **55** with lat/lng (**~10%**)  
- Published: **153**, of which **52** have coords (**~34%**)  
- Cities: **16/16** have coordinates  

**Implication:** Ranking by **event** coordinates today would exclude most events unless city fallback is explicit. Current discovery already uses **city** coords — that is the safer short-term SoT for Option B.

---

## 2. Existing friends system (CONFIRMED)

### Data

| Piece | Location |
| --- | --- |
| Migration | `database/migrations/2026_09_28_201000_create_friendships_and_notify_pref.php` |
| Model | `app/Models/Friendship.php` |
| Status enum | `app/Enums/FriendshipStatus.php` (`pending`, `accepted`, `declined`) |
| Notify pref | `users.notify_friend_events` |

### Domain service

`app/Services/Friends/FriendshipService.php`

- `findBetween`, `areFriends`, `stateFor` (includes **blocked**)  
- `request`, accept/decline/cancel/destroy  
- Integrates `UserBlockService`  
- Broadcasts / notifications: `UserFriendRequested`, `UserFriendAccepted`, friend notifications  

### Search

`app/Services/Friends/SearchUsersService.php`

- **Public ID only** (exact or prefix); **not** name search (privacy)  
- Excludes self + blocked  
- Active accounts with `public_id`  
- `MIN_QUERY_LENGTH = 3`  

### HTTP

**Web** (`routes/web.php`): `/friends` index, store, accept, decline, cancel, destroy — `WebFriendController`  
**API** (`routes/api.php`): `/api/friends`, `/api/friends/requests`, mutations — `ApiFriendController`  
User search API: `Api\Friends\UserSearchController`  
DMs: web + API DirectMessage controllers/services  

### Clients

| Platform | Paths |
| --- | --- |
| Web UI | `resources/views/friends/index.blade.php` |
| Flutter | `mobile/lib/features/friends/**` (page, remotes, repos, `FriendshipState`) |
| Parity doc | `docs/FLUTTER_WEB_PARITY.md` § Friends / gap #20 |

### Privacy / eligibility (CONFIRMED)

- Block either-way → `status: blocked`, no request/message; `UserBlockService::block()` also **deletes** any friendship row  
- `can_request` / `can_message` require `canParticipate()` on both sides  
- Precise user coordinates are **not** exposed — public cards via `PublicProfileService::toPublicArray()` (`id`, `public_id`, `name`, `initials`, `profile_image_path`, `role_label`, `city{id,name}`, stats, `profile_url`)  
- No member `discoverable` / privacy opt-out column on `users` today  
- Admin name/email suggest (`SearchSuggestController`) is **platform-admin only** — not member people discovery  

### Tests

`tests/Feature/FriendshipApiTest.php` exists (includes block ↔ friend interactions).

### Roadmap conflict (CONFIRMED)

`ROADMAP.md` § “Explicitly NOT in V1” lists **friend suggestions**. V2.1 people discovery is a **product-scope change** that must explicitly supersede that line before implementation (owner already commissioned this audit).

---

## 3. Friends discovery gaps (“People You May Like to Meet”)

### Reusable (CONFIRMED)

- Friendship state machine + API  
- Block exclusion (`UserBlockService::blockedUserIdsFor`)  
- Profile photo + display name + city name via `PublicProfileService::toPublicArray` / Flutter `FriendUser`  
- Friendship state + Add Friend (`FriendshipService::stateFor` + `POST /api/friends`)  
- Flutter `FriendshipState` / friends hub + public profile CTAs  
- Web Blade friends list + `profile/public.blade.php` friendship CTAs  

### Missing (CONFIRMED unless noted)

| Capability | Gap |
| --- | --- |
| Interest taxonomy / user interests | **No** `user_interests` table; event `Category` only. Marketing “same interests” copy is **not** backed by stored user interests. V2 deliberately omitted interest-tagger (`docs/design/funnity-v2/IMPLEMENTATION_REPORT.md`) |
| Shared-interest matching | **Missing** (optional v1: derive from co-attended / same event categories) |
| Suggestion endpoint | **Missing** (only public_id search; ROADMAP excluded suggestions from V1) |
| Homepage “People You May Like” section | **Missing** on marketing `/`, `/dashboard`, and Flutter home |
| Discovery eligibility flags | No opt-out flag; **ASSUMPTION:** reuse Active + `canParticipate` + not friend/pending/blocked |
| Compatibility scores | **Must not invent** — out of scope |

### Recommended architecture (proposal only)

1. **Single Source of Truth:** `PeopleDiscoveryService` (Laravel) returning opaque suggestion cards.  
2. Inputs: viewer id, limit, exclusions (self, friends, pending, blocked).  
3. Ranking v1 (honest): same **city** or overlapping **event categories attended/hosted** — only if data exists; otherwise city peers who can participate.  
4. Enrich with `toPublicArray` + `stateFor` (same shape as search).  
5. **Do not** ship fabricated compatibility %.  
6. API e.g. `GET /api/people/suggestions` (new) — document in `FLUTTER_WEB_PARITY.md` + update `ROADMAP.md` before build.  
7. Web dashboard (+ Flutter home) sections consume the same API; guests may login-wall (product choice).  

---

## 4. Existing geographic data (CONFIRMED)

### Storage

| Entity | Fields | Notes |
| --- | --- | --- |
| `cities` | required `latitude`/`longitude`, EN/AR names | All **16/16** have coords; **no country table** |
| `events` | required `city_id`; nullable `latitude`/`longitude`/`maps_url`/`venue` | Pins optional; discovery **ignores** event pins today |
| `users` | nullable `city_id`, `distance_km` | **No user lat/lng**; prefs only |
| Factory | `EventFactory` | Does **not** set lat/lng → many seeds/tests lack pins |

Read-only DB (28 Oct 2026): **55/557** events with coords (~10%); **52/153** published (~34%).

Create path: `CreateEventService` + `ResolveEventLocationService` — pin optional (maps URL / reverse geocode / explicit); half-pair or out-of-range → null both.

### Discovery implementation

`app/Services/Events/DiscoverNearbyEventsService.php`

1. Origin = filter/`user.city_id` → **city center** + `distance_km`.  
2. Load active cities; filter in PHP via `GeoDistance` Haversine; cache `nearby_cities:v*`.  
3. `whereIn('city_id', $cityIds)` — **not** event lat/lng, **not** SQL `ST_Distance`.  
4. Response `distance_km` = **origin city center → event.city center** (straight-line).  
5. Text search without explicit city → nationwide (skips geo).  
6. Other filters: `category_id`, `when`, `q`.  

Config: `config/location.php` → **`[5, 10, 25, 50]`**, default **25**. Missing vs Option B: **`100`** and **All**.

### Nationwide soft-fallback (parity gap)

| Surface | Behavior |
| --- | --- |
| Web home / dashboard / discover | If origin from **saved** city and empty → re-query nationwide; `nationwideFallback`. **Skipped** if user explicitly picked a city filter |
| API `GET /api/events` | **No** soft fallback — empty nearby stays empty |
| Flutter home | Client-side empty→nationwide (`home_nearby_controller.dart`) |
| Flutter discover | Defaults `allCities: true` — not post-nearby expansion |

**Not present:** stepwise radius ladder (5→10→25→50→100).

Tests: `DiscoverNearbyEventsTest`, `GeoDistanceTest`, `HomePageTest::test_dashboard_falls_back_to_nationwide_when_nearby_empty`.

### Flutter / web location UX

- Settings: city + distance (`LocationPreferenceController` web/API, Flutter `location_page.dart`).  
- `MeController` exposes user `city_id`/`distance_km` and **city center** lat/lng (not user GPS).  
- Cities catalog: `CityListController` + `distances_km`.  
- **No device geolocation for discovery (CONFIRMED):** no `navigator.geolocation` in discover path; Flutter has **no** `geolocator` / `permission_handler`; **no** Android `ACCESS_*_LOCATION` / iOS `NSLocation*` for discovery. Map pickers are **manual pin** for event create (Leaflet / `EventPlaceMapPicker`).  
- Privacy copy already states exact GPS not required for browsing.  
- No continuous background tracking.

### Indexes (CONFIRMED)

| Table | Relevant indexes |
| --- | --- |
| `events` | `(status, starts_at)`, `(city_id, status)`, `(category_id, status)` — **no lat/lng index** |
| `cities` | `(is_active, name_en)` — no lat index (unused: all cities loaded in PHP) |

Current design does not need event lat indexes; pin-radius Option B would.

### Performance notes (CONFIRMED / RISK)

- PHP city Haversine + cache fine at 16 cities; **RISK** if city table grows without SQL-side strategy.  
- No spatial / `POINT` column. Event-level Haversine cheap at current N; growth → bounding-box prefilter + index.

---

## 5. Smart discovery architecture (Option B) — proposal

### Product sections (homepage)

| Section | Behavior |
| --- | --- |
| **Nearby** | Order by distance from discovery origin; radii 5/10/25/50/**100**/**all** |
| **Smart expansion** | If empty for selected radius, return nearest eligible beyond radius (metadata: `expanded`, `effective_radius_km`) — never empty solely due to radius; prefer ladder over raw nationwide jump |
| **Worth the Trip** | Separate farther list **even when Nearby has results**; de-dupe; no fabricated popularity — real `distance_km` (+ existing event fields) only |

### Origin privacy decision (product gate)

| Approach | Notes |
| --- | --- |
| **A — City-only origin (matches today)** | Keep accepting `city_id` only; never persist/accept raw GPS in API. Lowest privacy/risk; aligns with current prefs + no OS permission work. |
| **B — Optional device pin (Option B brief)** | Explicit permission → ephemeral lat/lng for ranking only; **never store** as user profile; never expose to others. Requires new Flutter/web permission UX. |

**Recommendation:** ship Option B sections with **A first**; add device origin behind a follow-on flag if owner insists on GPS.

### Backend SoT (recommended)

Extend/`EventDiscoveryService` wrapping `DiscoverNearbyEventsService`:

- `nearby(anchor, radiusKm, filters)` — prefer **event pin** if `hasPlacePin()`, else city center; never invent coords  
- `expandIfEmpty(anchor, radii[])` — ladder then optional nationwide flag  
- `worthTheTrip(anchor, excludeIds, filters)` — next ring / farther cities, ordered by real km  

**Move soft-fallback into API** so Flutter home can drop client-only nationwide compensation.

**Eligibility:** existing published/upcoming + blocks; keep member discover rules unless product changes SEO/`search_indexable` coupling.

### API sketch (do not implement silently)

```http
GET /api/events/discover?city_id=&distance_km=25|100|all&category_id=&when=
```

Home bundle (preferred):

```json
{
  "meta": {
    "origin_city_id": 1,
    "requested_radius_km": 25,
    "effective_radius_km": 100,
    "expanded": true,
    "nationwide": false
  },
  "nearby": [],
  "worth_the_trip": []
}
```

Expose per-event `distance_km` (crow-flies); **never** return other users’ precise coordinates. City-center coords already public via `/api/cities` / me payload — do not add user GPS fields.

### Migrations (if needed)

| Change | Necessity |
| --- | --- |
| Add `100` (+ sentinel for “all”) to `config/location.php` | Config only |
| Country / governorate model | **Not required** for v1 Option B (city hubs suffice); needed only if product wants cross-country labeling |
| Optional `users.discovery_*` | Only if persistent manual pin ≠ profile city |
| Event lat indexes / geography | Only if querying pins in SQL at scale |
| Backfill event coords | **Ops** — do not invent; encourage create/edit pin completeness; factory should optionally set pins for tests |

---

## 6. Existing navigation architecture (CONFIRMED)

### Flutter

- **GoRouter** only (`mobile/lib/app/router.dart`) — **no** `pushReplacement` usage found; mix of `context.push` / `context.go` / `Navigator.pop` for dialogs  
- **StatefulShellRoute.indexedStack** — Home / Discover / Create / My Events / Profile (`main_shell.dart`); branches are single routes (no per-feature nested stacks)  
- Detail overlays use **`parentNavigatorKey: _rootKey`** (events, chats, friends, DMs, settings) — cover shell and **hide bottom nav**  
- Auth: central `redirect:` + `refreshListenable: _AuthRefresh`; unauth → `/login` **without** `returnTo`; success → `user.routeAfterAuth`  
- **`routerProvider` watches `authControllerProvider`** — GoRouter may be **rebuilt** on auth changes (stack wipe risk even with refreshListenable)  
- Deep links: AndroidManifest launcher-only; **no** App Links / `onMessageOpenedApp` / `getInitialMessage` handlers  
- Discover filters: Riverpod controller (not URL); survives tab switch via indexedStack; **cold start loses filters**  

### Laravel Web

- Full-page `<a href>` / form GET; dashboard `mobile-nav` remains visible on member detail pages (unlike Flutter overlays)  
- Discover filters: query string + live search `history.replaceState` (`resources/js/live-search.js`) — filter tweaks do **not** stack history entries  
- Auth: `redirect()->intended(...)` on login/Google happy path; **identity/email pending gates** often use plain `redirect()->to(...)` and drop intended  
- Modals: native `<dialog>` location/confirm — Escape/backdrop OK; **no** `popstate` / history push for Back-to-dismiss  

---

## 7. Confirmed navigation defects & risks

| ID | Finding | Evidence | Severity |
| --- | --- | --- | --- |
| NAV-F1 | Flutter login does **not** restore intended deep link / prior route | `router.dart` redirect → `/login`; post-auth → `routeAfterAuth` → `/home` | **P0** |
| NAV-F2 | Frequent `context.go('/discover'|'/home')` **clears** stack | `home_page.dart`, `dash_topbar.dart`, `main_shell.dart` | **P0** |
| NAV-F3 | Create publish `context.go('/events/:id')` orphans root detail — Back often cannot return to Create/Discover | `create_event_page.dart` | **P0**/P1 |
| NAV-F4 | Shell tab re-tap resets branch (`initialLocation: true`) | `MainShell._onBranchTap` | **P1** — document |
| NAV-F5 | Root overlays hide bottom nav — must Back before switching tabs | `router.dart` `parentNavigatorKey` | Document parity |
| NAV-F6 | **`/friends?tab=requests` ignored** — router only handles `search=1`; toasts/FCM emit `tab=requests` | `router.dart`, `friends_page.dart`, `notifications_page.dart`, `chat_toast_payload.dart` | **P0** |
| NAV-F7 | **FCM tray tap does not navigate** — foreground listen only; no `onMessageOpenedApp` / `getInitialMessage` | `push_notification_service.dart` | **P0** |
| NAV-F8 | **GoRouter rebuilt on auth watch** — stack wipe risk on login/logout/restore | `router.dart` `routerProvider` + `ref.watch(authControllerProvider)` | **P0** |
| NAV-F9 | Chat “View event” always `push`es → Event→Chat→Event duplicate | `event_chat_page.dart` | **P1** |
| NAV-F10 | No HTTPS/app-link intent filters | `AndroidManifest.xml` | **P1** |
| NAV-W1 | Absolute `APP_URL` links in local HTML can bounce to production | V2 QA | **P1** |
| NAV-W2 | Filter/scroll restoration after browser back | Query/`replaceState` good for last filter; scroll needs manual test | **P1** |
| NAV-W3 | **Intended dropped on identity/email gates** — pending docs/verify use plain redirects; identity submit → `defaultHomeRoute()` not `intended()` | `AuthenticatedSessionController`, `IdentityDocumentController`, `EnsureAccountIsActive` | **P0** |
| NAV-X1 | Owner “recurring back problems” | Explained by NAV-F1/F2/F3/F6/F8 + Flow A–H remainder | **P0** verify |

**Risks (manual test):** web dialog + browser Back (R1); Flutter cold-start filter loss (R4); guest tab → login → lands `/home` not tapped tab (R6). `/chats?tab=direct` works; friends `tab=requests` does not (inconsistent).

**Not claimed fixed:** nothing in this audit changes runtime navigation.

---

## 8. Root causes (with file references)

1. **No Flutter navigation contract** — free choice of `go` vs `push`.  
   Files: `home_page.dart`, `dash_topbar.dart`, `main_shell.dart`, `create_event_page.dart`, others.  

2. **Auth redirect is destination-destructive** — Flutter: no `returnTo`; Web: intended lost after identity/email gates.  
   Files: `mobile/lib/app/router.dart`, `AuthenticatedSessionController.php`, `IdentityDocumentController.php`.  

3. **GoRouter instance tied to auth watch** — rebuild can wipe stack independently of redirect logic.  
   File: `mobile/lib/app/router.dart` (`routerProvider`).  

4. **Deep-link / notification plumbing incomplete** — query params ignored (`tab=requests`); FCM open handlers missing; no app links.  
   Files: `friends_page.dart`, `push_notification_service.dart`, `AndroidManifest.xml`.  

5. **Geo discovery is city-set membership, not event-pin distance** — Option B approximate to city hubs.  
   File: `DiscoverNearbyEventsService.php` + sparse event coords.  

6. **People discovery productized as ID lookup** — cannot power “may like” without new matching data/API.  
   File: `SearchUsersService.php`.  

---

## 9. Web / Flutter parity gaps (V2.1)

| Capability | Web | Flutter | Gap |
| --- | --- | --- | --- |
| Friends hub | Yes | Yes | Low |
| Friends `?tab=requests` deep link | Web tabs via UI | **Broken** (NAV-F6) | Flutter |
| People suggestions | No | No | Both |
| Nearby + nationwide fallback | Soft-fallback on home/dashboard/discover | Home client fallback; discover defaults nationwide; **API no soft-fallback** | Move expansion to API SoT |
| Worth the Trip | No | No | Both |
| Device GPS discovery origin | None | None (no perms) | Optional follow-on |
| Country dimension | No model | No | City hubs only |
| Intended URL after login | Partial (lost on identity/email gates) | No | Both |
| Discover filter URL state | Query string | Riverpod only | Document / sync |
| Bottom nav on detail | Visible | Hidden (root overlay) | Document policy |
| FCM / app deep link open | N/A / browser URL | Missing handlers | Flutter |
| Device discovery origin | Limited | Limited | Both |

Update `docs/FLUTTER_WEB_PARITY.md` when implementing (mandatory per workspace rule).

---

## 10. Proposed navigation contract

### Verbs

| Verb | Use when |
| --- | --- |
| **push** | Drill-in: list→detail→chat/members/ticket/profile/settings; toasts/notifications into content |
| **replace** | Same-level swap without back to obsolete screen (login↔register; forgot→verify→reset; optional pop-to-detail from chat) |
| **go / reset** | Primary shell destinations (5 tabs); post-auth landing when no `returnTo`; logout; invalid-route fallback |
| **pop** | Dialogs/sheets; AppBar back when `canPop`; else documented fallback |

### Principles

1. **Never `go` to Discover/Home from a detail screen** when the user expects Back.  
2. **Shell tabs:** `goBranch`; re-tap = reset that tab only; system Back on shell root = **exit app** (OS default) — do not rotate tabs.  
3. **Root overlay:** Back pops to shell; bottom nav hidden until pop (document vs web always-visible chrome).  
4. **Deep link / FCM / cold start:** parse path → if unauth stash `returnTo` → after gates restore; if `canPop` false → `go` last shell tab or `/discover` (guest) / `/home` (auth). Shared `openAppPath(path)` helper.  
5. **Auth:** Flutter `returnTo` (query or in-memory allowlist); Web session `url.intended` through **all** gates including identity/email; after gates → `intended ?? defaultHome`.  
6. **Do not recreate GoRouter on auth watch** — `refreshListenable` + `ref.read` only.  
7. **Web:** normal links + query persistence; `intended()`; no fake global `history.back()`; document that filter `replaceState` does not stack undo steps.  
8. **Post-create:** prefer `go` shell tab then `push` detail (or replace from create) — avoid orphan root-only detail (`NAV-F3`).  

### Search / filters

| | Web | Flutter |
| --- | --- | --- |
| SoT | URL query | Prefer sync ↔ `/discover?...` **or** document Riverpod-as-SoT + cold-start loss |
| Live typing | `replaceState` OK | Debounced load; don’t `go` on every keystroke |
| Enter detail & back | Restore filtered list via URL | Keep Discover provider alive; avoid `go('/discover')` without params |  

---

## 11. Database changes (if necessary)

| Change | Priority | Notes |
| --- | --- | --- |
| Config distances include 100 + “all” | P1 | Not a migration |
| Optional discovery origin columns on users | P1 | Only if product wants persistent device/manual pin |
| User interests tables | P1 for people discovery | Required for shared-interest matching; else city-only suggestions |
| Composite index events `(status, starts_at, city_id)` | P2 | Perf |
| Spatial / bounding box | P2+ | When event-level geo scales |

**Do not** invent coordinate backfills.

---

## 12. API changes (if necessary)

| Endpoint | Purpose |
| --- | --- |
| `GET /api/events/discover` or home bundle | Nearby + expansion flag + worth_the_trip |
| `GET /api/people/suggestions` | People You May Like |
| Extend location preferences | Persist discovery origin if added |
| Document distance meaning | `distance_km` = great-circle |

Preserve existing `/api/events` for Discover page filters until clients migrate.

---

## 13. Implementation phases

| Phase | Focus | Depends on |
| --- | --- | --- |
| **0** | Stop GoRouter rebuild → Flutter/Web intended → `tab=requests` → FCM open → contract/`go` hygiene → Flow A–H | Audit |
| **1** | Option B discovery service + radii + homepage sections web/Flutter | Phase 0 preferred |
| **2** | People suggestions (city-first; interests if approved schema) | Friends SoT |
| **3** | Visual polish into V2 homepage chrome | V2 branch |
| **4** | Perf indexes + event-coord completeness campaign | Scale |

---

## 14. Regression test plan

### Laravel

- Feature: friends privacy (blocked excluded from suggestions)  
- Feature: discover nearby / empty-radius expansion / worth_the_trip de-dupe  
- Feature: distance_km validation (5…100/all)  
- Feature: auth `intended` still works  
- Feature: no user lat/lng leaked in people/event list JSON  

### Flutter

- Widget/route: login with `returnTo` restores event  
- Route stack: Home → Event → Profile → back chain  
- Shell: tab switch + system back  
- Discover filters survive push/pop event  
- Chat: Event → Chat → back ≠ Home  
- Deep link `/events/:id` cold start fallback  

### Web browser

- Back/forward through Flow A/C  
- Filter query persistence  
- Scroll restoration spot-check  
- RTL locale + back  
- Mobile Safari/Chrome back from event  

Tests must assert **stack/history behavior**, not only HTTP 200.

---

## 15. Risks and dependencies

| Risk | Mitigation |
| --- | --- |
| Sparse event coords (~34% published pins) | City fallback; UI honesty; no invented backfill |
| API lacks soft-fallback vs web/Flutter home | Move expansion into API SoT |
| Device GPS vs city-only origin | Ship city-only first; GPS as gated follow-on |
| No country model | City-hub expansion; defer country labeling |
| `go` refactor regressions | Codemod + Flow A–H checklist |
| Suggestion abuse | Throttle + participate gate + blocks |
| `ROADMAP.md` still forbids friend suggestions in V1 | Owner must explicitly supersede before P1-F* |
| Interest schema product delay | Ship city-peer suggestions first; optional category-derived “shared” labels |
| V2 branch WIP | Keep audits additive; don’t reset Git |
| APK/env Flutter SDK | Use Flutter 3.47+ side-by-side for Dart ^3.13.1 |

---

## Appendix A — Key file index

**Friends:** `FriendshipService.php`, `SearchUsersService.php`, `FriendController` (Web/Api), `mobile/lib/features/friends/**`, `FriendshipApiTest.php`  

**Geo:** `DiscoverNearbyEventsService.php`, `GeoDistance.php`, `config/location.php`, `HomeController.php`, `EventController.php`, `LocationPreferenceController.php`  

**Nav:** `mobile/lib/app/router.dart`, `main_shell.dart`, `dash_topbar.dart`, `home_page.dart`, `public_profile_page.dart`, `AuthenticatedSessionController.php`, `resources/js/live-search.js`  

**V2 visual (preserve):** `feature/funnity-v2-homepage`, `docs/design/funnity-v2/**`

---

## Appendix B — Git hygiene note

At audit time, branch `feature/funnity-v2-homepage` had uncommitted V2 screenshot/report edits. **Do not** `git reset --hard` or clean those away when starting V2.1 work. Prefer a new branch from this tip or stacked commits after owner approval.
