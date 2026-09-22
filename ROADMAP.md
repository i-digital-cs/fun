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

**Flutter mobile client — production build phases in progress / largely complete.** Continue polish and device QA.

---

## Flutter mobile

### Task F1 — Flutter scaffold + auth + discover
- **Status:** `done`
- **Completed:** 2026-09-22

### Task F2 — Create event + location settings + chat
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Clean Architecture folders, secure tokens, l10n, location, create/edit/publish, membership, chat, lifecycle.

### Task F3 — Identity upload + blocks/reports + ratings
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Account gates, identity upload, blocks, reports, safety guidelines, ratings.

### Task F4 — Perf / flavors / release / tests
- **Status:** `done`
- **Completed:** 2026-09-22
- **Notes:** Image memCache bounds, catalog TTL cache, dart-define flavors, widget+unit tests, obfuscation build command documented.

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
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - `event_ratings` (unique per event+rater); rates creator (`rated_user_id`)
  - Allowed when event `completed` OR `ends_at` past; member only; no self-rate; block-aware
  - 1–5 stars + optional sanitized comment; upsert update allowed
  - Web form on event show; organizer average; recent ratings list
  - API: `POST/GET …/ratings`, `GET …/ratings/me`; show includes `can_rate` / `creator_rating`
- **Tests:** 71 passed (EventRatingTest)
- **UI checklist:** completed-event member sees rate form; creator does not
- **Security:** EventPolicy `rate`; throttle:ratings; XSS-safe comments
- **Depends on:** Tasks 5–6

### Task 11 — Admin panel
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - Admin dashboard `/admin` with stats + shared admin nav
  - Site settings: configurable `display_name` (super_admin only); `site_display_name()` used in layout/home
  - Identity docs review + private download (authorized)
  - Event moderation: list/cancel/complete; chat message delete
  - Audit log (`admin_audit_logs`) for deactivate, settings, reports, events, chat deletes
  - Explicit `PlatformRole` capability methods; settings/audit super_admin-only
  - API: `/api/admin/settings`, `/api/admin/events` cancel/complete
- **Tests:** 79 passed (AdminPanelTest)
- **UI checklist:** `/admin` 200 for admin; moderator blocked from settings/audit
- **Security:** platform.admin middleware; identity docs gated by UserPolicy; private disk streaming
- **Depends on:** Tasks 2–10 baselines

### Task 12 — Cloudflare R2 media wiring
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - Installed `league/flysystem-aws-s3-v3`; fully configured `r2` + `r2_private` disks
  - `MediaDisk` helper validates allowed public/private disk names
  - Uploaders resolve disks via config only (local or R2 by env switch)
  - Events store `image_disk` with path for storage-agnostic URLs
  - `PrivateMediaAccessService` streams identity docs after auth (works local + R2)
  - `.env.example` documents production R2 switch steps
- **Tests:** 84 passed (MediaConfigTest + identity R2 disk name / admin download auth)
- **Ops:** keep local defaults; set `MEDIA_DISK=r2` + `MEDIA_PRIVATE_DISK=r2_private` with R2 creds for production
- **Depends on:** Task 2 media abstraction

### Task 13 — Hardening pass
- **Status:** `done`
- **Completed:** 2026-09-21
- **Summary:**
  - Security: members API no longer returns emails; join/block throttles; User/Event privileged columns off fillable; location settings require active account; R2 private bucket no longer falls back to public bucket
  - Perf: `Event::isFull` / `acceptedMembersCount` / membership helpers use `members_count` or loaded relations; members lists paginated; nearby city IDs cached (version-bumped on city admin changes); event show skips loading full members for capacity
  - Cleanup: redundant identity_documents index removed from migration; `profile_image_path` exposed on `/api/user`
- **Tests:** 90 passed (HardeningPassTest + full regression)
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
