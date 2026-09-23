# Flutter Mobile — ROADMAP.md

# Fun Mobile Client — Implementation Roadmap

This file is the **session-continuity roadmap for the Flutter app** (`mobile/`).

Any agent/session working on mobile must:

1. Read `docs/FLUTTER_PRINCIPLES.md`
2. Read `docs/DESIGN.md` (UI must match Blade)
3. Read **this file** and continue from the first incomplete task
4. After finishing a task: update status here before ending the session

Status values: `todo` | `in_progress` | `done` | `blocked`

Workflow per task: PLAN → ASK (if ambiguous) → IMPLEMENT → ANALYZE/TEST → update this roadmap

**Design source of truth:** Laravel Blade UI + `docs/DESIGN.md`  
**API source of truth:** `docs/postman/` + production `https://fun.idigital-cs.com/api`  
**Admin panel:** out of scope for mobile V1

---

## Current focus

**Phase 5 — Perf / flavors / release / tests.**

---

## Progress snapshot

| Phase | Name | Status |
|-------|------|--------|
| F1 | Scaffold + auth + discover baseline | `done` |
| F2 | Create event + location + chat + membership basics | `done` |
| F3 | Identity + blocks/reports + ratings baseline | `done` |
| **P0** | **See all events (web Discover parity)** | `done` |
| **P1** | **Home / dashboard Blade parity** | `done` |
| **P2** | **Event show full Blade parity** | `done` |
| **P3** | **Safety & account screens** | `done` |
| **P4** | **Auth polish & optional realtime** | `done` |
| **P5** | **Perf / flavors / release / tests** | `todo` |

---

## Completed foundation

### Task F1 — Flutter scaffold + auth + discover
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Clean Architecture, Riverpod/Dio/go_router, DESIGN tokens, Sanctum auth, home hero + discover + event card + detail, EN/AR, secure tokens.

### Task F2 — Create event + location + chat + membership
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Create/edit/publish, my-events, join/leave, join requests, chat, location page + Home location card, catalog `distances_km`.

### Task F3 — Identity + blocks/reports + ratings baseline
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Identity upload gate, blocks list, report sheet, rating entry on detail when `can_rate`.

---

## Phase 0 — See all events (web Discover parity)

**Goal:** Match Blade `events/index` behavior so users are not stuck on “nearby only”.

Blade reference: `resources/views/events/index.blade.php`, `DiscoverEventsRequest`, `DiscoverNearbyEventsService`  
Rule: empty / null `city_id` → nationwide published events (no distance origin).

### Task P0.1 — Discover filter bar (All cities)
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Filter bar with All cities, category, distance chips, q; empty `city_id` → nationwide.

### Task P0.2 — “See all” / nationwide entry points
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Home See all / Find → `loadNationwide` + `/discover`; Clear → nationwide; NationwideNotice banner.

### Task P0.3 — Pagination + empty states
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Infinite scroll; empty CTAs (Create, change location, Show all); Home nearby uses separate controller.

---

## Phase 1 — Home / dashboard Blade parity

**Goal:** Member home matches `home/dashboard.blade.php` sections (mobile adaptation of dashboard, not desktop sidebar).

Blade reference: `resources/views/home/dashboard.blade.php`, `components/dashboard/*`

### Task P1.1 — Category grid / chips
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Into grid with emoji tiles; tap → Discover + category + All cities.

### Task P1.2 — Starting soon
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Nationwide discover + 48h client filter (limit 4); today/tomorrow labels.

### Task P1.3 — How it works + safety card + create banner
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** 3-step how-it-works; safety card → `/safety/guidelines`; bottom create CTA; identity gate when not active.

### Task P1.4 — Empty nearby UX
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Empty card with Create / Show all / Change location.

---

## Phase 2 — Event show full Blade parity

**Goal:** Match `events/show.blade.php` + members/requests/ratings/safety actions.

Blade reference: `events/show.blade.php`, `events/members.blade.php`, `events/requests.blade.php`

### Task P2.1 — Members management screen
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** `/events/:id/members`; promote/demote/transfer; report/block; role badges.

### Task P2.2 — Ratings complete
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** `GET ratings` list + creator section + your-rating hint; rate sheet retained.

### Task P2.3 — Join-state & owner tools polish
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Joined → members link; owner chat/members; edit when not cancelled/completed; block organizer.

### Task P2.4 — Report from event / message / user
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Event report + block; members report/block; chat long-press report message/user + block.

---

## Phase 3 — Safety & account screens

Blade reference: `safety/guidelines.blade.php`, `safety/report.blade.php`, `safety/blocks.blade.php`, `account/deactivated.blade.php`

### Task P3.1 — Safety guidelines page
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Full Blade sections (identity/report/block + disclaimer); CTAs → `/report`, `/blocks`, identity when pending.

### Task P3.2 — Blocks polish
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** Unblock confirm dialog; pagination load-more; empty state retained.

### Task P3.3 — Account deactivated
- **Status:** `done`
- **Completed:** 2026-09-23
- **Notes:** `/account/deactivated` gate in router; logout only; login redirects when `status == deactivated`.

---

## Phase 4 — Auth polish & optional realtime

### Task P4.1 — Google Sign-In (optional)
- **Status:** `done`
- **Completed:** 2026-09-23
- **API:** `POST /auth/google` (`access_token` + optional `locale`) via Socialite `userFromToken`
- **App:** `google_sign_in` on Login + Register → Sanctum token
- **Note:** Runtime needs Google Cloud OAuth client IDs / SHA-1 for Android & iOS; without them the button fails with `googleSignInFailed`

### Task P4.2 — Guest discover (optional product decision)
- **Status:** `done`
- **Completed:** 2026-09-23
- **Behavior:** Unauthenticated users land on Discover; can open `/events/:id` + safety guidelines; join/create/chat/other tabs redirect to login

### Task P4.3 — Realtime event chat (optional)
- **Status:** `done` (polling fallback)
- **Completed:** 2026-09-23
- **Tech:** 5s silent poll on `EventChatPage` (Reverb/Echo not wired in Flutter yet)

### Task P4.4 — Marketing guest home (optional)
- **Status:** `deferred`
- **Blade:** `home/index.blade.php` hero / trust — skipped for V1; guests use Discover, not a marketing home

---

## Phase 5 — Perf / flavors / release / tests

### Task P5.1 — Image / list performance
- **Status:** `todo`
- **Notes:** `cached_network_image` memCache bounds; `ListView.builder`; avoid rebuild storms

### Task P5.2 — Flavors / dart-define
- **Status:** `todo`
- **Notes:** Document `API_BASE_URL` + `FLAVOR`; never ship store build to localhost

### Task P5.3 — Tests
- **Status:** `todo`
- **Notes:** Unit for repositories/mappers; widget for join CTA states & discover filters; auth bootstrap

### Task P5.4 — Release checklist
- **Status:** `todo`
- **Notes:** Obfuscation build, analyze clean, EN/AR RTL pass, logout clears secure storage

---

## Blade → Flutter screen matrix

| Blade | Flutter target | Phase |
|-------|----------------|-------|
| `auth/login`, `auth/register` | Login / Register | F1 ✅ |
| `home/dashboard` | Home shell | F1 ✅ + P1 |
| `events/index` | Discover | F1 ✅ + **P0** |
| `events/show` | Event detail | F2/F3 ✅ + P2 |
| `events/create`, `edit`, `_form` | Create/Edit | F2 ✅ |
| `events/mine` | My Events | F2 ✅ |
| `events/chat` | Chat | F2 ✅ + P4.3 |
| `events/requests` | Join requests | F2 ✅ |
| `events/members` | Members | **P2.1** |
| `profile/location` + location modal | Location | F2 ✅ |
| `identity/create` | Identity | F3 ✅ |
| `safety/blocks` | Blocks | F3 ✅ + P3.2 |
| `safety/report` | Report | F3 sheet ✅ + P2.4 / P3 |
| `safety/guidelines` | Guidelines | **P3.1** |
| `account/deactivated` | Deactivated | **P3.3** |
| `home/index` marketing | Deferred (Discover for guests) | P4.4 deferred |
| Google OAuth buttons | Login + Register + API | P4.1 ✅ |
| Admin (`layouts/admin`) | **Not in mobile V1** | — |

---

## Explicitly NOT in mobile V1

- Admin panel (users, cities, categories, reports moderation, site settings, audit)
- Payments
- Desktop-only sidebar chrome as a permanent layout (use bottom nav + location card instead)

---

## Session handoff checklist

Before ending a mobile session:

- [ ] Update task status in this file
- [ ] `flutter analyze` clean
- [ ] New UI checked against `docs/DESIGN.md` + Blade counterpart
- [ ] New strings in `lib/l10n/app_en.arb` + `app_ar.arb`
- [ ] API fields match Postman / Laravel Form Requests
- [ ] Note known limitations under the task

---

## Open questions / blockers

- Configure Google Cloud OAuth client IDs + Android SHA-1 / iOS URL scheme so P4.1 works on devices
- Reverb / Echo channel auth if native Flutter realtime chat is needed later (polling is live)

---

*Living document — update when phases complete or scope changes.*
