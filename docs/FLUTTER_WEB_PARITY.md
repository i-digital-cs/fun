# Flutter ↔ Web (Blade) Feature Parity Guide

**Purpose:** Close the gap between the Laravel Blade web app and the Flutter client.  
Implement Flutter screens so they match **structure, content, CTAs, and states** of the Blade pages below — responsive for phone/tablet, same design system as [`DESIGN.md`](../DESIGN.md).

**Also read:** [`DESIGN.md`](../DESIGN.md) · [`docs/FLUTTER_BEST_PRACTICES.md`](FLUTTER_BEST_PRACTICES.md) · Postman [`docs/postman/`](postman/) · API `routes/api.php`

**Rule:** Pixel-perfect copy of desktop layout is not required. **Same information hierarchy, same actions, same join states, same brand tokens** are required.

---

## 0. How to use this doc

For each screen:

1. Open the **Blade path** in the browser (reference).
2. Build / adjust the Flutter screen to match the **Must implement** list.
3. Wire the listed **API** endpoints.
4. Check the **Parity checklist** at the bottom before calling the screen done.

**Out of scope for member Flutter app:** `/admin/*` (platform admin stays web-only unless a separate admin app is requested).

---

## 1. Shells & navigation (match before individual pages)

### 1.1 Guest / public shell

| Web | Blade |
|-----|--------|
| Marketing home | `layouts/home.blade.php` + `x-community.*` |
| Auth & public flows | `layouts/app.blade.php` (ambient + glass navbar + footer) |

**Flutter must:**

- Aurora / ambient background + glass-style cards on login/register/safety/public discover.
- Brand colors: indigo primary, orange→amber CTAs, emerald trust, canvas `#f5f7fb`.
- EN / AR + `dir` / `TextDirection` sync.

### 1.2 Authenticated member shell

| Web | Blade |
|-----|--------|
| Dashboard layout | `layouts/dashboard.blade.php` |
| Sidebar | `components/dashboard/sidebar.blade.php` |
| Topbar | `components/dashboard/topbar.blade.php` |
| Mobile nav | `components/dashboard/mobile-nav.blade.php` |

**Flutter must (responsive):**

| Breakpoint | Chrome |
|------------|--------|
| Phone (&lt; ~850px) | Bottom nav 5 tabs: **Home · Discover · Create · My Events · Profile** |
| Wide | Left sidebar (same items + Blocked users + location card) |

**Create** tab: opens create-event (or identity gate if user cannot participate) — not a permanent empty tab.

**Topbar (home/discover):** search field, locale toggle, profile entry (notifications optional if API not ready).

---

## 2. Screen inventory — member / public

### 2.1 Marketing home `/`

| | |
|--|--|
| **Route** | `home` → `/` |
| **Blade** | `home/index.blade.php` |
| **Guest?** | Yes (also shows extra blocks when authed) |
| **API** | `GET /api/events`, `GET /api/categories` (+ auth memberships if logged in) |

**Sections (order):**

1. Hero (`x-community.hero`) — brand, CTA into discover/create  
2. Participations (`x-community.participations`) — **auth + canParticipate only**  
3. Discover strip (`x-community.discover`) — rich event cards  
4. Trust (`x-community.trust`)

**Flutter note:** Phone apps often skip a separate marketing home and open **Login** or **Member Home (`/dashboard`)**. If you keep a guest home, mirror these four sections. Prefer matching **dashboard** for logged-in users (next screen).

---

### 2.2 Member dashboard home `/dashboard`

| | |
|--|--|
| **Route** | `dashboard` → `/dashboard` |
| **Blade** | `home/dashboard.blade.php` |
| **Auth** | Required |
| **API** | `GET /api/events` (nearby filters), `GET /api/categories`, `GET /api/cities`, `GET /api/user` |

**Sections (order) — implement all:**

1. **Greeting** — “Good morning/afternoon/evening, {firstName} 👋” + subtitle + orange **Create event**  
2. **Hero** — dark photo/gradient block, titles “Find people. / Do what you love.”, **Find events** (indigo) → Discover, trust chips  
3. **What are you into?** — category grid (emoji + name + count) → Discover filtered by category  
4. **Happening near you** — compact cards (`x-dashboard.event-card`) + safety card (indigo gradient)  
5. **How {brand} works** — 3 steps  
6. **CTA banner** — “Can’t find your activity?” + orange create  

**Card style here:** compact dashboard card (indigo soft “View event”), **not** the full Discover rich card.

---

### 2.3 Discover `/events`

| | |
|--|--|
| **Route** | `events.index` → `/events` |
| **Blade** | `events/index.blade.php` |
| **Guest?** | Yes (Create CTA only if authed + canParticipate) |
| **API** | `GET /api/events?q&category_id&city_id&distance_km` |

**Must implement:**

- Title + subtitle  
- Filter panel: search, category, city, distance, Apply / Clear  
- Prompt to set location if `city_id` missing (link to location settings)  
- Grid/list of **rich** cards: `components/events/card.blade.php`  
- Empty / nationwide-fallback messaging when applicable  
- Orange Create when allowed  

**Rich card must show:** cover, category chip, Free/price chip, urgency chip, schedule, title, venue, members progress, join-state banner, full-width orange **View event**.

---

### 2.4 Event detail `/events/{id}`

| | |
|--|--|
| **Route** | `events.show` |
| **Blade** | `events/show.blade.php` |
| **API** | `GET /api/events/{id}`, join/leave, ratings endpoints |

**Layout hierarchy (same as web):**

1. Cover + category chip + city + title + **status badge**  
2. Date/time chip + venue chip  
3. Description  
4. Requirements box  
5. Cost / free + members count chips  
6. **Primary actions** (join state — see §3)  
7. Owner tools (edit / publish / cancel / complete / requests / members)  
8. Report event + block host  
9. Organizer rating summary  
10. Rate form (when `can_rate`)  
11. Ratings list  

---

### 2.5 Create event `/events/create`

| | |
|--|--|
| **Route** | `events.create` / `events.store` |
| **Blade** | `events/create.blade.php` + `events/_form.blade.php` |
| **Gate** | `canParticipate()` else redirect identity |
| **API** | `POST /api/events` (multipart if image) |

**Form fields (all):**

| Field | Required |
|-------|----------|
| title | yes |
| description | no |
| category_id | yes |
| city_id | yes |
| venue | no |
| starts_at | yes |
| ends_at | no |
| max_participants | yes |
| requirements | no |
| expected_cost | no |
| cost_per_person | no |
| image (jpeg/png/webp) | no |

CTA: orange create/save. After create → show or edit draft + publish.

---

### 2.6 Edit event `/events/{id}/edit`

| | |
|--|--|
| **Blade** | `events/edit.blade.php` + `_form` |
| **API** | `PUT /api/events/{id}` |
| **Authz** | Owner / can update only |

Same fields as create. Keep existing image if not replaced.

---

### 2.7 My events `/my-events`

| | |
|--|--|
| **Blade** | `events/mine.blade.php` |
| **API** | `GET /api/my-events` |

**Must:** list owned/membership events — title, date, members, city, **status pill**; empty state + Create CTA; tap → detail.

---

### 2.8 Join requests `/events/{id}/requests`

| | |
|--|--|
| **Blade** | `events/requests.blade.php` |
| **API** | `GET .../requests`, `POST .../accept`, `POST .../reject` |
| **Authz** | Owner / can review |

List pending requesters with Accept / Reject.

---

### 2.9 Members `/events/{id}/members`

| | |
|--|--|
| **Blade** | `events/members.blade.php` |
| **API** | `GET .../members`, promote, demote, transfer |

Show roles (owner/admin/member). Owner tools: promote, demote, transfer ownership. Member can leave from detail (not owner).

---

### 2.10 Chat `/events/{id}/chat`

| | |
|--|--|
| **Blade** | `events/chat.blade.php` |
| **API** | `GET /api/events/{id}/messages`, `POST .../messages` |
| **Access** | Joined members only |

**Must:** message list (author, time, body), composer, report message link, link back to event.  
Web uses realtime; Flutter may poll until websockets are wired — document polling interval in app.

---

### 2.11 Login `/login`

| | |
|--|--|
| **Blade** | `auth/login.blade.php` |
| **API** | `POST /api/login` → token |

Glass card, email + password, **indigo→violet** primary CTA, link to register.  
(Google OAuth on web — optional for Flutter; if skipped, note in UI.)

---

### 2.12 Register `/register`

| | |
|--|--|
| **Blade** | `auth/register.blade.php` |
| **API** | `POST /api/register` |

Name, email, password, confirmation, locale. **Orange→amber** CTA.

---

### 2.13 Identity `/identity`

| | |
|--|--|
| **Blade** | `identity/create.blade.php` (status + view + upload) |
| **API** | `GET /api/identity`, `GET /api/identity/documents/{id}`, `POST /api/identity` |

**Cycle (Option 1 — immediate activate + admin resubmit):**

1. New user (`pending_documents`, no files) → upload form only  
2. After upload → `active`; show thumbnails of ID front / back / selfie (owner-only stream)  
3. Active users cannot re-upload until admin asks  
4. Admin **Request documents again** → `pending_documents`; show previous images + banner + replace form  
5. Re-upload all three → `active` again  

Gate create/join/chat while `needs_identity_documents`. `GET /api/user` exposes `needs_identity_documents`, `has_identity_documents`, `identity_resubmit_required`.

---

### 2.14 Account deactivated `/account/deactivated`

| | |
|--|--|
| **Blade** | `account/deactivated.blade.php` |

Block app usage; show message + logout. No member APIs.

---

### 2.15 Location settings `/settings/location`

| | |
|--|--|
| **Blade** | `profile/location.blade.php` (+ location modal on web) |
| **API** | `PUT /api/settings/location` |

City select + distance_km (5 / 10 / 25 / 50). Orange save. Feeds Discover/Home filters.

---

### 2.16 Safety guidelines `/safety`

| | |
|--|--|
| **Blade** | `safety/guidelines.blade.php` |
| **API** | Static copy (l10n) |

Readable guidelines page; linked from dashboard safety card.

---

### 2.17 Report `/report`

| | |
|--|--|
| **Blade** | `safety/report.blade.php` |
| **API** | `POST /api/reports` |

Params: `reportable_type` (`user` \| `event` \| `message`), `reportable_id`, reason, details.

---

### 2.18 Blocks `/settings/blocks`

| | |
|--|--|
| **Blade** | `safety/blocks.blade.php` |
| **API** | `GET /api/blocks`, `POST/DELETE /api/users/{id}/block` |

List blocked users + unblock. Block host from event detail.

---

## 3. Join action states (must match web exactly)

From `App\Enums\EventJoinAction` — drive buttons on cards + event detail:

| Value | UI |
|-------|-----|
| `request_to_join` | Orange **Request to join** |
| `request_pending` | Muted “Request pending” (no primary join) |
| `joined` | Emerald “Joined” + Chat + Members (+ Leave if not owner) |
| `request_rejected` | Amber rejected + allow request again |
| `event_full` | Muted “Event full” |
| `not_available` | Hide join / guest → login |

Backend is authority; never invent local join logic that contradicts `join_action` from API.

---

## 4. Event lifecycle actions (owner)

| Status | Actions on detail |
|--------|-------------------|
| `draft` | Edit, **Publish**, Cancel |
| `published` | Edit, Cancel, **Complete**, join requests, manage members |
| `cancelled` / `completed` | View mostly; ratings when completed + eligible |

API: `POST .../publish|cancel|complete`.

---

## 5. Shared UI components to port

| Web component | Flutter equivalent |
|---------------|-------------------|
| `x-events.card` | Rich discover/list card |
| `x-dashboard.event-card` | Compact home nearby card |
| `x-community.ambient` | AmbientBackground |
| `x-dashboard.mobile-nav` | 5-tab bottom bar |
| `x-dashboard.sidebar` | Wide-layout drawer/sidebar |
| Orange gradient CTA | Primary conversion button |
| Indigo→violet CTA | Secondary brand (login, chat) |
| Form fields | rounded-xl, indigo focus |

Copy strings from `lang/en/*.php` and `lang/ar/*.php` into Flutter l10n (ARB).

---

## 6. Account gates (parity with middleware)

| User status | Flutter behavior |
|-------------|------------------|
| Guest | Login/register; public discover/detail read-only join → login |
| `pending_documents` | Allow browse; block create/join/chat → Identity |
| `active` + canParticipate | Full member flows |
| `deactivated` | Deactivated screen only |

Mirror web: `account.active` + `canParticipate()` for create/join/report/rate.

---

## 7. Admin screens (web-only — do not block member app)

Documented for awareness; **not required** in member Flutter:

- `/admin` dashboard, users, identity moderation, events, calendar, chat moderation, reports, cities, categories, settings, audit  

Use web admin at `https://fun.idigital-cs.com/admin`.

---

## 8. Suggested Flutter route map

| Flutter route | Matches web |
|---------------|-------------|
| `/login` | `/login` |
| `/register` | `/register` |
| `/home` | `/dashboard` |
| `/discover` | `/events` |
| `/events/create` | `/events/create` |
| `/events/:id` | `/events/{id}` |
| `/events/:id/edit` | `/events/{id}/edit` |
| `/events/:id/chat` | `/events/{id}/chat` |
| `/events/:id/requests` | `/events/{id}/requests` |
| `/events/:id/members` | `/events/{id}/members` |
| `/my-events` | `/my-events` |
| `/identity` | `/identity` |
| `/settings/location` | `/settings/location` |
| `/blocks` | `/settings/blocks` |
| `/report` | `/report` |
| `/safety` | `/safety` |
| `/deactivated` | `/account/deactivated` |

---

## 9. Parity checklist (per screen)

Copy this into PRs:

- [ ] Same primary sections / fields as Blade  
- [ ] Same CTAs and join/lifecycle states  
- [ ] EN + AR strings + RTL layout  
- [ ] Design tokens (indigo / orange CTA / radii / canvas)  
- [ ] API wired; errors mapped to user-readable messages  
- [ ] Account gates respected  
- [ ] Empty / loading / error states  
- [ ] Compared side-by-side with web on phone width  

---

## 10. Known gap themes (prioritize)

When rebuilding Flutter on Windows, close these first:

1. **Shell** — 5-tab nav + home = `/dashboard` (not discover-only)  
2. **Discover filters** — full filter panel like web (city + distance + clear)  
3. **Event detail** — full action matrix + ratings blocks + report/block  
4. **Rich vs compact cards** — use correct card per surface  
5. **Form parity** — `expected_cost`, image upload, all create fields  
6. **Members** — promote / demote / transfer  
7. **Chat** — report message; near-realtime updates  
8. **Marketing home** — optional guest landing  

---

## 11. Reference URLs (local QA)

Base: `https://fun.idigital-cs.com`

| Page | URL |
|------|-----|
| Marketing | `/` |
| Dashboard | `/dashboard` |
| Discover | `/events` |
| Login | `/login` |
| Create | `/events/create` |
| My events | `/my-events` |
| Location | `/settings/location` |
| Identity | `/identity` |
| Safety | `/safety` |
| Blocks | `/settings/blocks` |

API base: `https://fun.idigital-cs.com/api`

---

*Last updated: 2026-09-23 — sourced from `routes/web.php`, `routes/api.php`, and Blade views under `resources/views/`.*
