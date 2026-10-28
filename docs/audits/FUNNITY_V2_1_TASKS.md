# Funnity V2.1 — Prioritized Implementation Tasks

**Source audit:** `docs/audits/FUNNITY_V2_1_DISCOVERY_NAVIGATION_AUDIT.md`  
**Branch policy:** Preserve `feature/funnity-v2-homepage` V2 visual work. Prefer stacked branch e.g. `feature/funnity-v2-1-discovery-nav` after owner approval.  
**Do not implement until owner approves this plan.**

Priority key:

- **P0** — Critical navigation defects  
- **P1** — Smart geographic discovery / Friends discovery  
- **P2** — Visual integration and polish  

---

## P0 — Critical navigation

### P0-00 — Stop recreating GoRouter on auth watch (NAV-F8)

| | |
| --- | --- |
| **Scope** | `routerProvider` must not `ref.watch(authControllerProvider)` in a way that rebuilds `GoRouter`; use `refreshListenable` + `ref.read` only. |
| **Affected files** | `mobile/lib/app/router.dart` |
| **Dependencies** | None — do first |
| **Acceptance** | Login/logout/token restore does not wipe an in-progress drill-down stack unless redirect policy requires it. |
| **Tests** | Widget/route test: push event → trigger auth refresh → still on event (or intentional redirect only) |

### P0-01 — Flutter intended-route after auth (NAV-F1)

| | |
| --- | --- |
| **Scope** | Stash allowlisted `returnTo` before login/gates; after success navigate there (not only `routeAfterAuth` → `/home`). Guest tab taps that push login must restore intended tab/path. |
| **Affected files** | `router.dart`, `login_page.dart`, `register_page.dart`, `auth_controller.dart`, `main_shell.dart`, `event_detail_page.dart` |
| **Dependencies** | P0-00 |
| **Acceptance** | Flow D: Guest → Event → Login → same event. Malicious URLs rejected. |
| **Tests** | Flutter redirect + returnTo tests; manual Flow D |

### P0-02 — Navigation contract + ban stack-clearing `go` on drill-downs (NAV-F2/F3)

| | |
| --- | --- |
| **Scope** | Publish contract; replace incorrect `go` with `push`/param restore; post-create: shell `go` then `push` detail (or replace from create) — no orphan root-only detail. |
| **Affected files** | `home_page.dart`, `dash_topbar.dart`, `main_shell.dart`, `create_event_page.dart`, `favorites_page.dart`, others |
| **Dependencies** | P0-00, P0-01 |
| **Acceptance** | Flows A/C/E; create publish Back returns to meaningful shell screen. |
| **Tests** | Flutter nav tests Flows A, C, E; create publish back |

### P0-03 — Honor `/friends?tab=requests` (NAV-F6)

| | |
| --- | --- |
| **Scope** | Parse `tab=requests` (and keep `search=1`); open Requests tab on FriendsPage; align toast/notification/FCM payloads. |
| **Affected files** | `router.dart`, `friends_page.dart`, callers emitting `?tab=requests` |
| **Dependencies** | None (can parallel P0-01) |
| **Acceptance** | Flow B: notification → Friends Requests tab; Back predictable. |
| **Tests** | Widget/route test query → Requests tab selected |

### P0-04 — FCM open → `openAppPath` (NAV-F7)

| | |
| --- | --- |
| **Scope** | Handle `getInitialMessage` + `onMessageOpenedApp`; route via shared allowlisted `openAppPath` (push if shell visible, else go+push). |
| **Affected files** | `push_notification_service.dart`, router helpers |
| **Dependencies** | P0-01 (auth return), P0-03 (friends tab) |
| **Acceptance** | Flow G: cold/background FCM open lands on chat/DM/friends target (or login+returnTo). |
| **Tests** | Unit/integration with fake RemoteMessage; manual tray open |

### P0-05 — Web intended through identity/email gates (NAV-W3)

| | |
| --- | --- |
| **Scope** | Preserve session intended across pending verify/identity redirects; after identity submit use `redirect()->intended(defaultHome)` not only `defaultHomeRoute()`. Spot-check discover filter/back. |
| **Affected files** | `AuthenticatedSessionController.php`, `IdentityDocumentController.php`, `EnsureAccountIsActive.php`, Google auth if needed |
| **Dependencies** | None |
| **Acceptance** | Flow D web with identity gate still returns to original event; Flow C query preserved; no global `history.back()` hack. |
| **Tests** | Feature tests intended through gates; browser checklist |

### P0-06 — Manual + automated Flow A–H matrix

| | |
| --- | --- |
| **Scope** | Execute Flows A–H on Flutter + Web; log results; fix only evidenced failures (incl. NAV-F9 chat View event, dialog Back R1). |
| **Affected files** | Per findings |
| **Dependencies** | P0-00 … P0-05 |
| **Acceptance** | Results table in PR; no “fixed because unit test green” without scenario. |
| **Tests** | Scenario checklist + expanded automated coverage |

---

## P1 — Smart geographic discovery

### P1-G1 — Extend radius config (100 km + All)

| | |
| --- | --- |
| **Scope** | Update `config/location.php` distances; validate requests; update web + Flutter location UI chips. |
| **Affected files** | `config/location.php`, `DiscoverEventsRequest.php`, location settings Blade/Flutter, lang files |
| **Dependencies** | None |
| **Acceptance** | API accepts 100 and All (define sentinel e.g. `0` or omit geo filter); invalid rejected. |
| **Tests** | Feature validation tests |

### P1-G2 — `EventDiscoveryService` Option B core

| | |
| --- | --- |
| **Scope** | Nearby + radius ladder expansion + Worth the Trip (de-duped). **v1 origin = city_id** (saved/manual); do not accept/store user GPS unless owner approves follow-on. Event distance: pin if `hasPlacePin()` else city center; never invent. Crow-flies km only. API owns expansion meta (`expanded`, `effective_radius_km`, `nationwide`). |
| **Affected files** | New/extend `app/Services/Events/*`, `DiscoverNearbyEventsService.php`, API events/discover controller, resources; reuse `GeoDistance` |
| **Dependencies** | P1-G1; product choice city-only vs device origin |
| **Acceptance** | Empty selected radius still returns nearest beyond; Worth the Trip ≠ Nearby IDs; no user GPS fields; API soft-fallback parity with web (clients stop inventing nationwide). |
| **Tests** | Feature: empty radius expansion, de-dupe, distance validation; extend `DiscoverNearbyEventsTest` |

### P1-G3 — Homepage / Discover API bundle + clients

| | |
| --- | --- |
| **Scope** | Home bundle endpoint; wire web home/dashboard + Flutter home/discover sections; remove Flutter `home_nearby_controller` client-only nationwide once API owns it; radius chips 100/All. |
| **Affected files** | `HomeController`, `DashboardController`, API, Flutter home/discover controllers, `FLUTTER_WEB_PARITY.md` §2.16 |
| **Dependencies** | P1-G2 |
| **Acceptance** | Web + Flutter show Nearby (+ expansion notice) and Worth the Trip; no fabricated scores; parity doc updated. |
| **Tests** | API feature test; Flutter repository/controller tests |

### P1-G4 — Geo performance & honesty

| | |
| --- | --- |
| **Scope** | UI crow-flies / approximate copy; ops note on ~34% published pins; index only if pin-radius SQL ships; optionally improve `EventFactory` coords for tests. |
| **Affected files** | Lang strings, optional migration, factory, audit follow-up |
| **Dependencies** | P1-G2 |
| **Acceptance** | Explainability in UI; no N+1 on home bundle; no invented backfill of prod coords. |
| **Tests** | Query count assertion optional |

---

## P1 — Friends discovery

### P1-F1 — People suggestions service (v1 without fabricated scores)

| | |
| --- | --- |
| **Scope** | `PeopleDiscoveryService`: candidates = active, participatable, not self/friend/pending/blocked; rank by same `city_id` (and optionally shared event categories if cheap). Return cards via `PublicProfileService::toPublicArray` + `FriendshipService::stateFor` / `can_request`. |
| **Affected files** | New service, API controller, routes; reuse `PublicProfileService`, `FriendshipService`, `UserBlockService`; update `ROADMAP.md` (currently lists friend suggestions as NOT in V1) |
| **Dependencies** | Existing friends SoT; **owner confirmation that V2.1 supersedes ROADMAP V1 exclusion** |
| **Acceptance** | Excludes blocked/friends/self/pending; no lat/lng; throttled; same card shape as search/friends list. |
| **Tests** | Feature privacy tests (blocked excluded; no coords in JSON) |

### P1-F2 — Homepage “People You May Like to Meet” (web + Flutter)

| | |
| --- | --- |
| **Scope** | Section on homepage (auth); Add Friend CTA; empty/error states; link to `/friends`. |
| **Affected files** | Home Blade/Flutter widgets, lang, `FLUTTER_WEB_PARITY.md` |
| **Dependencies** | P1-F1, prefer after P0 nav |
| **Acceptance** | Flow B works with correct Back; V2 visual tokens. |
| **Tests** | Feature + Flutter widget; Flow B manual |

### P1-F3 — Interests schema (optional gate)

| | |
| --- | --- |
| **Scope** | Only if product approves: user interests storage + shared-interest field on suggestions. Otherwise defer; do not invent scores. |
| **Affected files** | Migrations, models, settings UI, discovery service |
| **Dependencies** | Product decision |
| **Acceptance** | Shared interests listed only when real overlap exists. |
| **Tests** | Feature tests for overlap |

---

## P2 — Visual integration & polish

### P2-01 — V2 homepage composition for new sections

| | |
| --- | --- |
| **Scope** | Style People + Worth the Trip + Nearby expansion using V2 tokens/cards; light-only; RTL. |
| **Affected files** | Community Blade components, Flutter home widgets, `DESIGN.md` / V2 report if needed |
| **Dependencies** | P1-G3, P1-F2, existing V2 branch |
| **Acceptance** | Matches V2 direction; no new product chrome beyond approved sections. |
| **Tests** | Visual screenshot checklist (desktop/mobile) |

### P2-02 — Discover filters UX for new radii + expansion messaging

| | |
| --- | --- |
| **Scope** | Chips for 100/All; banner when results were expanded beyond selected radius. |
| **Affected files** | Discover Blade/Flutter |
| **Dependencies** | P1-G1, P1-G2 |
| **Acceptance** | User understands why non-local events appear. |
| **Tests** | Widget/feature |

### P2-03 — Navigation polish (modals / predictive back)

| | |
| --- | --- |
| **Scope** | Ensure sheets/dialogs use `PopScope`; Android predictive back where supported. |
| **Affected files** | Flutter dialogs/sheets across events/friends |
| **Dependencies** | P0-02 |
| **Acceptance** | Flow H: back dismisses overlay first. |
| **Tests** | Widget tests with `tester.pageBack` where applicable |

---

## Dependency graph (summary)

```text
P0-00 ─► P0-01 ─► P0-02 ─┐
P0-03 ────────────────────┼─► P0-04 ─► P0-06
P0-05 ────────────────────┘

P1-G1 ─► P1-G2 ─► P1-G3 ─► P2-01 / P2-02
                    └─► P1-G4

P1-F1 ─► P1-F2 ─► P2-01
P1-F3 (optional) ─► P1-F1 ranking upgrade
```

Also update §13 phase 0 in the audit doc when implementing: order is **P0-00 → intended (F+W) → friends tab → FCM → go hygiene → Flow matrix**.

---

## Out of scope (explicit)

- Production deploy / destructive migrations / coordinate invention  
- Dark mode  
- Fabricated popularity or compatibility percentages  
- Continuous background location tracking  
- Replacing GoRouter unless Phase 0 proves irreparable (not indicated)  
- Overwriting V2 visual commits  

---

## Definition of done for V2.1

1. P0 navigation Flows A–H signed off  
2. Option B discovery live on web + Flutter with tests  
3. People suggestions live with privacy tests  
4. `FLUTTER_WEB_PARITY.md` updated  
5. No silent API breaks; changelog for new endpoints  
6. Owner visual approval before production deploy  
