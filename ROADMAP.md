# ROADMAP.md

# Community Activity Platform — Implementation Roadmap

This file is the **single session-continuity roadmap**.

Any new AI/agent session must:

1. Read `MASTER_CONTEXT.md`
2. Read `AGENT.md`
3. Read `DESIGN.md` (when UI is involved)
4. Read **this file** and continue from the first incomplete task
5. After finishing a task: update status here before ending the session

Status values: `todo` | `in_progress` | `done` | `blocked`

Workflow per task (mandatory): PLAN → ASK → CONFIRM → IMPLEMENT → TEST → UI TEST → SECURITY/PERF REVIEW → REPORT → update this roadmap

---

## Current focus

**Next task:** Task 5 — Events CRUD + capacity

---

## V1 Tasks

### Task 1 — Laravel 12 scaffold
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:** Laravel 12 + Sanctum + Tailwind 4 + Vite; service/controller folders; design tokens; local media disks + R2 placeholders; landing page; docs preserved
- **Tests:** HomePage, Sanctum API, MediaConfig
- **Notes:** Brand name still from `APP_NAME` until admin site settings

### Task 2 — Auth + identity documents + account status
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - AR/EN + RTL/LTR locale switch
  - Email/password register/login (Blade + Sanctum API)
  - Google OAuth routes via Socialite (needs GOOGLE_* env)
  - Identity docs upload → immediate `active`
  - Full deactivate/reactivate services + web admin users page + API (Task 11 will polish UI)
  - Platform roles: super_admin / moderator
  - Rate limits: auth, uploads
- **Tests:** Auth, identity, admin deactivate, locale, sanctum
- **Depends on:** Task 1
- **Notes:** Set `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` for Gmail login; seed user `admin@example.com` / `password`

### Task 3 — Cities + user location preferences
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `cities` table (name_en/ar, slug, lat/lng, is_active)
  - Egypt seed (15 cities including Obour, Cairo, …)
  - User `city_id` + `distance_km` (5/10/25/50)
  - Admin city CRUD + activate/deactivate (web + API)
  - User location settings page + API
  - Public `GET /api/cities` + GeoDistance helper for later discovery
- **Tests:** City management, location prefs, geo distance
- **Depends on:** Task 2

### Task 4 — Categories (admin-managed)
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `categories` table (name_en/ar, slug, sort_order, is_active)
  - Seed: Football, PlayStation, Basketball, Running, Gaming, Board Games, Cinema, Social, Outdoor, Tennis
  - Admin CRUD + activate/deactivate (web + API)
  - Public `GET /api/categories` (active only)
  - Shared `UniqueSlug` helper (also used by cities)
- **Tests:** 26 passed total (CategoryManagementTest added)
- **UI checklist:** `GET /api/categories` 200 (10 items); admin API list 200; web index covered by feature test
- **Security:** platform.admin + CategoryPolicy; inactive excluded from public list; no hard-coded category IDs
- **Perf:** indexed `(is_active, sort_order)`; ordered list + pagination on admin endpoints
- **Depends on:** Task 2

### Task 5 — Events CRUD + capacity
- **Status:** `todo`
- **Scope:**
  - Create/edit/publish/cancel/complete events
  - Event fields per MASTER_CONTEXT
  - Max capacity enforced server-side (transactional)
  - Creator auto-OWNER membership
  - Statuses: `draft` | `published` | `cancelled` | `completed`
- **Depends on:** Tasks 3–4

### Task 6 — Join requests + event roles
- **Status:** `todo`
- **Scope:**
  - Request → owner/admin accept/reject
  - Roles: OWNER / ADMIN / MEMBER
  - Promote member; transfer ownership; leave-after-transfer rules
  - Capacity race-condition tests
- **Depends on:** Task 5

### Task 7 — Discover / nearby feed + event cards UI
- **Status:** `todo`
- **Scope:**
  - Nearby discovery by city center + distance preference
  - Event cards/details per DESIGN.md
  - Join-state UX (pending / joined / rejected / full)
- **Depends on:** Tasks 5–6

### Task 8 — Event chat (Reverb)
- **Status:** `todo`
- **Scope:**
  - One public chat per event
  - Members only; channel auth server-side
  - Spam/flood/XSS protections + rate limits
- **Depends on:** Task 6

### Task 9 — Reports + block users
- **Status:** `todo`
- **Scope:**
  - Report users / events / messages
  - Block user (V1)
  - Moderation status fields
- **Depends on:** Tasks 2, 5–6, 8 (messages)

### Task 10 — Ratings
- **Status:** `todo`
- **Scope:**
  - Rate event creator after event end + accepted membership
  - 1–5 stars + optional comment
  - Creator cannot rate self
- **Depends on:** Tasks 5–6

### Task 11 — Admin panel
- **Status:** `todo`
- **Scope:**
  - Roles: SUPER_ADMIN / MODERATOR
  - Users, identity docs, deactivate, events, reports, moderation, categories, cities
  - Site settings (changeable display/brand name)
  - Audit activity where required
- **Depends on:** Tasks 2–10 baselines

### Task 12 — Cloudflare R2 media wiring
- **Status:** `todo`
- **Scope:**
  - Switch `MEDIA_DISK` / `MEDIA_PRIVATE_DISK` to R2
  - Keep business logic storage-agnostic
  - Validate private identity access still authorized
- **Depends on:** Task 2 media abstraction (already scaffolded)

### Task 13 — Hardening pass
- **Status:** `todo`
- **Scope:**
  - Security review (IDOR, authz, uploads, rate limits)
  - Performance (N+1, indexes, pagination)
  - Dead code / unused DB cleanup
  - Full regression test suite
- **Depends on:** Tasks 2–12

---

## Explicitly NOT in V1 (do not implement)

- Private messaging
- In-app payments / wallet / escrow
- Complex recommendations / social ranking
- Speculative tables/columns without an approved task

---

## Session handoff checklist

When ending a session after work:

- [ ] Mark finished task `done` with date + short summary
- [ ] Set next task to `todo` or `in_progress`
- [ ] Note blockers / open questions under that task
- [ ] Do not invent new scope outside this roadmap without updating MASTER_CONTEXT first

---

## Open questions / blockers

- Google OAuth requires real `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` in `.env` before Gmail login works in UI.
