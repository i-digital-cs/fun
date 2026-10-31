# Admin Dashboard V2 — Audit & Design Specification

**Date:** 2026-10-31  
**Production path:** `/home/d14098/funnity.com`  
**Git baseline at audit:** `main` @ `d3e5df5` (clean working tree)  
**Scope:** Stage 1 audit only → Stage 2 implements from AVAILABLE / DERIVABLE metrics

---

## 1. Existing dashboard architecture

| Layer | Current implementation |
|-------|------------------------|
| Route | `GET /admin` → `admin.dashboard` (`platform.admin`) |
| Controller | `App\Http\Controllers\Web\Admin\DashboardController` (invokable) |
| Service | `App\Services\Admin\AdminDashboardMetricsService` |
| View | `resources/views/admin/dashboard.blade.php` |
| Charts | Chart.js 4.4.7 CDN |
| Drill-down | `GET /admin/activity/{metric}` → `ActivityController` |
| Shell | `layouts/admin.blade.php` + `x-admin.sidebar` / topbar / mobile-nav |
| Auth | `EnsurePlatformAdmin` — any non-null `platform_role` (`super_admin` \| `moderator`) |
| Repositories | None (services + Eloquent) |

### Current UI gaps

- Flat “today vs yesterday” cards only — no selectable reporting period.
- Lifetime totals unlabeled as snapshots vs period.
- No action center tying counts to moderation queues.
- No operational recent-events table or activity feed.
- No geographic section.
- No system-health signals (`failed_jobs` exists but unused).
- Visual hierarchy is dense but not operational (many equal-weight cards).
- Sidebar is a flat list (no grouping).

### Publishing policy (must respect)

- **Create Event → published** when `publish_intent=true`.
- **Save Draft → draft** when intent false/omitted.
- **Do not** invent pending-approval for public events.
- Drafts are host/admin-private; not a publish queue.

---

## 2. Existing metrics (today)

| Metric | Definition today | Source |
|--------|------------------|--------|
| active_members | Distinct `sessions.user_id` with `last_activity` in day window | `sessions` |
| registrations | `users.created_at` in window | `users` |
| events_created | `events.created_at` in window | `events` |
| join_requests | `event_join_requests.created_at` | `event_join_requests` |
| joins_accepted | Accepted + `reviewed_at`/`updated_at` in window | `event_join_requests` |
| event_messages | Count only (body shown on drill-down) | `event_messages` |
| direct_messages | Count only (body on drill-down — privacy risk on detail page) | `direct_messages` |
| friend_requests | All friendship rows created in window | `friendships` |
| friend_accepts | `status=accepted` + `updated_at` in window | `friendships` |
| Lifetime: active_users | `users.status=active` | snapshot |
| Lifetime: pending_users | `pending_documents` | snapshot |
| Lifetime: published_events | `events.status=published` | snapshot |
| Lifetime: pending_reports | `reports.status=pending` | snapshot |
| Charts | 14-day fixed series + status/category doughnuts | aggregates |

---

## 3. Data domains (schema summary)

**Present:** users, events (+ status enum draft/published/cancelled/completed), event_members (+ ticket_code), event_join_requests, friendships, reports, user_blocks, event_messages, direct_messages, admin_audit_logs, identity_documents, cities, categories, sessions, jobs/failed_jobs, notifications, device_tokens, event_ratings, event_favorites.

**Absent / N/A:** communities tables, payment tables, dedicated tickets table, session analytics warehouse, GPS IP geolocation for users.

---

## 4. Metric inventory

Legend: **A** = AVAILABLE · **D** = DERIVABLE · **I** = REQUIRES INSTRUMENTATION · **N** = NOT APPLICABLE

### Executive KPIs

| Name | Definition | Sources | Date field | Class | Permissions |
|------|------------|---------|------------|-------|-------------|
| Total users | All user rows | `users` | — (lifetime) | A | platform.admin |
| New users | Registrations in period | `users.created_at` | created_at | A | platform.admin |
| Active accounts | `status=active` (not session) | `users.status` | — | A | platform.admin |
| Session-active users | Distinct sessions in period | `sessions` | last_activity | A | platform.admin |
| Published events | Current published count | `events.status` | — | A | platform.admin |
| Events created (period) | New event rows | `events.created_at` | created_at | A | platform.admin |
| Event participants (period) | New `event_members` rows | `event_members.created_at` | created_at | A | platform.admin |
| Active communities | — | — | — | **N** | — |
| Pending reports | Open moderation queue | `reports.status=pending` | — | A | platform.admin |

### Action center

| Item | Definition | Class | Destination |
|------|------------|-------|-------------|
| Pending reports | `ReportStatus::Pending` | A | `admin.reports.index` |
| Pending identity users | `AccountStatus::PendingDocuments` | A | `admin.users.index?status=pending_documents` |
| Failed queue jobs | `failed_jobs` count | A | Document only / count (no admin UI yet) |
| Event approval queue | N/A — direct publish | **N** | — |
| Community reports | No communities | **N** | — |

### Activity feed (derived, not audit warehouse)

| Type | Source query | Class | Privacy |
|------|--------------|-------|---------|
| User registered | Latest users | D | name + public_id only on admin UI |
| Event created | Latest events | D | title, status, organizer name |
| Report submitted | Latest reports | D | reason/status, no private chat body |
| Friendship accepted | Latest accepted | D | names only |
| Admin audit | `admin_audit_logs` | A | Super-admin section only |
| Full historical activity stream | No unified table | **I** | — |

### Events performance

| Metric | Class | Notes |
|--------|-------|-------|
| Status breakdown | A | groupBy status |
| Created in period | A | |
| Participation trend | D | members + join accepts per day |
| Top categories | A | withCount / join |
| Top cities by events | A | groupBy city_id |
| Attendance / check-in rate | **I** | ticket_code exists but no check-in events |
| Payments / revenue | **N** | |

### Social engagement

| Metric | Class | Notes |
|--------|-------|-------|
| Friend requests sent (period) | A | friendships.created_at |
| Friend accepts (period) | A | status accepted + updated_at |
| Active friendships (lifetime) | A | status accepted |
| Pending friend requests (lifetime) | A | status pending |
| DM / event chat **volume** | A | counts only |
| DM / chat **content** on dashboard | — | **Do not show** |
| Community membership | **N** | |

### User growth & retention

| Metric | Class | Notes |
|--------|-------|-------|
| Registrations over time | A | |
| Session-active users | A | define explicitly vs account status |
| Users who created events (period) | D | distinct events.created_by |
| Users who joined events (period) | D | distinct event_members.user_id |
| Classic retention cohorts | **I** | no reliable session history beyond current `sessions` table |
| Returning users (multi-day) | **I** | sessions table is ephemeral |

### Geographic

| Metric | Class | Notes |
|--------|-------|-------|
| Events by city | A | events.city_id |
| Participants by city | D | join members → events.city_id |
| Communities by city | **N** | |
| IP/GPS inference | **N** / forbidden | |

### System health

| Metric | Class | Notes |
|--------|-------|-------|
| Failed jobs count | A | `failed_jobs` — show count, never exception payload |
| Notification failures | **I** | no failure table |
| Scheduled job health | **I** | no heartbeat store |
| App error rate | **I** | do not scan `laravel.log` per request |

---

## 5. Proposed dashboard sections (Stage 2)

| Section | Content from available data |
|---------|------------------------------|
| A Executive | Period selector + KPIs with previous-period delta |
| B Action center | Pending reports, pending identity, failed jobs |
| C Activity | Derived recent rows (users/events/reports/friends) |
| D Events | Status cards, charts, top categories/cities, recent events table |
| E Social | Friend + chat volume KPIs + trend |
| F Users | Registration trend, creators/joiners, account status |
| G Geo | Top cities by events / participants |
| H Health | Failed jobs count + empty-state if zero; gap note for logs |

---

## 6. UI component structure

- `admin/dashboard.blade.php` — composition root
- `components/admin/dashboard/*` — kpi-card, section, action-item, period-filter, activity-row, metric-hint
- Sidebar: grouped nav (Overview / Users & Social / Events / Moderation / Catalog / System)
- Chart.js retained
- EN/AR strings in `lang/{en,ar}/admin.php`
- Brand: primary `#7138F5`, accent `#FF762E`, bg `#F8F9FE`, light only

---

## 7. Backend architecture (Stage 2)

```
DashboardController
  → DashboardAnalyticsService::build(ReportingPeriod, User)
       → ReportingPeriod (presets + custom)
       → AdminDashboardMetricsService (reuse periodCounts / activity keys)
       → SQL aggregates (no N+1; bounded limits)
```

- Cache key: `admin.dashboard.v2.{periodHash}.{locale}.{roleFlag}` TTL ~60s
- No new tables / migrations in Stage 2
- Timezone: `config('app.timezone')` throughout
- Zero previous-period → percent `null` (show absolute delta only)

---

## 8. Security considerations

- Keep `platform.admin` middleware.
- Super-admin-only: settings, audit feed entries.
- Dashboard: counts of DMs/messages only — no bodies.
- Activity drill-down for DMs already shows bodies to admins — out of scope to remove; dashboard itself must not echo content.
- No OTP, tokens, password hashes.
- CSRF on period filter GET (read-only query string).
- XSS: Blade escaping; chart data via `@json`.

---

## 9. Query performance risks

| Risk | Mitigation |
|------|------------|
| Many COUNT queries | Single service build; short cache; avoid per-card queries in Blade |
| `DATE(created_at)` groupBy | Bound to period window (max 90 days preset; custom clamped) |
| sessions distinct | Already indexed pattern; period-bounded |
| Recent tables | `limit(8–12)` + selective columns |
| failed_jobs | `count(*)` only |

**No schema changes proposed for Stage 2.** Optional future indexes (not executing): `(events.created_at)`, `(event_members.created_at)`, `(reports.status)`.

---

## 10. Deferred / requires product decision

1. Unified activity/audit stream instrumentation.
2. Community metrics (product not shipped).
3. Ticket check-in / attendance rate.
4. Retention cohorts / returning-user definition beyond sessions.
5. Notification delivery failure dashboard.
6. Dedicated admin UI for failed jobs retry.
7. Privacy hardening of DM activity drill-down bodies (separate task).

---

## 11. Stage 2 go / no-go

**GO** — A useful, accurate command center can be built from existing tables without migrations or policy changes.

**STOP before:** any migration, inventing community/approval workflows, or claiming retention/attendance without instrumentation.
