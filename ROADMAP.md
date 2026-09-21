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

**Next task:** Task 10 — Ratings

---

## V1 Tasks

### Task 1 — Laravel 12 scaffold
- **Status:** `done`
- **Completed:** 2026-09-21

### Task 2 — Auth + identity documents + account status
- **Status:** `done`
- **Completed:** 2026-09-21
- **Notes:** `admin@example.com` / `password`; Google OAuth needs env keys

### Task 3 — Cities + user location preferences
- **Status:** `done`
- **Completed:** 2026-09-21

### Task 4 — Categories (admin-managed)
- **Status:** `done`
- **Completed:** 2026-09-21

### Task 5 — Events CRUD + capacity
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `events` + `event_members` tables
  - Statuses: draft → published → cancelled/completed
  - Creator auto-OWNER membership (transactional)
  - Capacity stored; cannot set below current members; owner counts toward capacity
  - Optional event image on public media disk
  - Web: `/events`, `/events/create`, `/my-events`, show/edit/publish/cancel/complete
  - API: CRUD + status transitions; public published list
- **Tests:** 32 passed total (EventManagementTest: create/owner/publish/capacity/authz)
- **UI checklist:** `/events` 200; `/api/events` 200; `/events/create` guest → 302 login
- **Security:** `account.active` for mutating routes; EventPolicy; inactive category/city blocked; no client-trusted ownership
- **Perf:** indexes on status/starts_at/city/category; `withCount('members')` on lists
- **Depends on:** Tasks 3–4
- **Deferred to Task 6:** join requests / approve-reject race locking for new members

### Task 6 — Join requests + event roles
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `event_join_requests` (pending/accepted/rejected)
  - Request → owner/admin accept/reject with `lockForUpdate` capacity checks
  - Promote/demote admin; transfer ownership; leave (owner must transfer first)
  - Join action states: request / pending / joined / rejected / full
  - Web + API membership endpoints
- **Tests:** 37 passed (EventMembershipTest added)
- **UI checklist:** `/events` 200; join/members/transfer routes registered
- **Security:** EventPolicy join/review/manageMembers/leave; capacity enforced server-side
- **Perf:** row locks on accept; indexed `(event_id, status)`
- **Note:** member notifications deferred to Task 8
- **Depends on:** Task 5

### Task 7 — Discover / nearby feed + event cards UI
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `DiscoverNearbyEventsService`: city-center Haversine via active cities within `distance_km` (5/10/25/50)
  - Guest / no city → nationwide published feed; located user → nearby; `city_id=` clears to nationwide
  - Filters: category, city, distance, search `q`
  - Event card component (DESIGN.md): category, title—city, venue, when, capacity, cost, View Event + join-state badge
  - Event details hierarchy tightened; batch `resolveMany` join actions on feed
  - API `/api/events` returns `distance_km` + `join_action` when applicable
- **Tests:** 44 passed (DiscoverNearbyEventsTest: nearby/far/distance override/filters/join_action/web cards)
- **UI checklist:** `/events` 200 Discover + filters; `/api/events` 200; cards show View Event
- **Security:** distance/city validated; only published upcoming-ish events; join state server-resolved
- **Perf:** city set filtered in PHP (small), then `whereIn city_id`; batch join-action lookup
- **Depends on:** Tasks 5–6

### Task 8 — Event chat (Reverb)
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `event_messages` table; members-only chat for published/completed events
  - Laravel Reverb + Echo (`private-events.{id}.chat`); `EventChatChannel` authorizer
  - `PostEventMessageService` / `ListEventMessagesService`; broadcast best-effort if Reverb down
  - XSS: strip_tags + Blade/JS escaping; max 1000 chars; duplicate window 10s; `throttle:messages` (20/min)
  - Web: `/events/{event}/chat`; API: `GET/POST /api/events/{event}/messages`
  - Event show → Open chat for members
- **Tests:** 54 passed (EventChatTest + ChatMessageSanitizerTest)
- **UI checklist:** guest `/events/{id}/chat` → 302 login; member chat page 200
- **Security:** EventPolicy `chat`; IDOR denied; channel auth members-only; deactivated/pending blocked
- **Perf:** indexed `(event_id, id)`; paginated history
- **Ops:** run `php artisan reverb:start` for live updates; HTTP send works without it
- **Depends on:** Task 6

### Task 9 — Reports + block users
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `user_blocks` + `reports` tables (reason/status/moderation_action)
  - Block/unblock (web + API); blocked list settings
  - Report users/events/messages; admin review queue (web + API)
  - Enforcement: blocked either-way blocks join, accept, chat with creator; hide blocked creators in discover; hide blocked users in join-request lists & chat history
  - Rate limit `throttle:reports` (5/min); description sanitized
- **Tests:** 62 passed (SafetyReportsAndBlocksTest)
- **UI checklist:** `/report` + `/settings/blocks` auth; event/members/chat report+block actions; `/admin/reports`
- **Security:** cannot report self/own message; duplicate pending blocked; admin-only review; API cannot bypass block checks
- **Depends on:** Tasks 2, 5–6, 8

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
