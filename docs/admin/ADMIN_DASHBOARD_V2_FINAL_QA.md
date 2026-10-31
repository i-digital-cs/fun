# Admin Dashboard V2 — Final Visual QA

**Date:** 2026-10-31  
**Production:** https://funnity.com/admin  
**Verdict: CONDITIONAL**

Rendered-browser verification was performed (desktop EN/AR, tablet, mobile) using production-backed static snapshots after Cursor CDP denied setting HttpOnly session cookies. Data accuracy matched read-only DB queries. Confirmed UX defects were fixed. Automated tests: **11 passed**. Temporary public QA HTML was deleted after capture.

---

## 1. Git baseline

| Item | Value |
|------|--------|
| Branch | `main` (tracks `origin/main`) |
| HEAD during QA | `9d0014b` — Refactor Admin Dashboard with New Analytics and Reporting Features |
| Prior baseline | `d3e5df5` |
| Uncommitted QA fixes | Present (not auto-committed): activity timestamps, chart period labels, locale button, action count sizing, identity priority, tests |
| Reset / clean / push | **Not used** |
| Production data writes | **None** (read-only metric checks; in-memory locale only for snapshots) |

---

## 2. Deployment verification

| Check | Result |
|-------|--------|
| App path | `/home/d14098/funnity.com` |
| Dashboard V2 files | Present (`DashboardAnalyticsService`, `ReportingPeriod`, Blade components, langs) |
| Vite assets | `public/build/manifest.json` present (Blade-only QA fixes; rebuild not required) |
| Laravel log | Prior test-stack traces only; no new production 500s attributed to dashboard during QA |
| Chart.js | CDN 4.4.7; **7/7** canvas instances initialized in browser |
| Horizontal overflow | Desktop 1440: none · Tablet 768: none · Mobile 390: none |

**Limitation:** Cursor browser could not attach a live `funnity-session` (HttpOnly + CDP `Network.setCookie` blocked). Live cookie auth was proven separately via `curl` → `/admin` **200** with dashboard HTML. Browser screenshots used short-lived static snapshots under an unguessable `public/qa-dash/<token>/` path (removed afterward; `robots.txt` Disallow).

---

## 3–6. Screenshots

Evidence filenames (Cursor screenshots store) — see `docs/admin/qa-screenshots/README.md`:

| # | Capture | Notes |
|---|---------|--------|
| 3 | `admin-v2-desktop-ar.png` | Arabic RTL: مركز القيادة, period chips, KPI cards, brand purple/orange |
| 4 | `admin-v2-desktop-en.png` / `…-viewport.png` / `…-main.png` | English LTR: grouped sidebar, Command center, reporting period, KPIs |
| 5 | `admin-v2-tablet-ar.png` | 768px: sidebar hidden, stacked content, bottom nav |
| 6 | `admin-v2-mobile-en.png` | 390px: stacked KPIs/filters, locale control visible, no overflow |

**Not fabricated.** Screenshots are real browser captures. They include a purple “static QA snapshot” banner by design.

---

## 7. Visual defects found

| Defect | Severity | Status |
|--------|----------|--------|
| Activity badges used `substr()` of translated labels (broken Arabic glyphs) | Medium | **Fixed** — type codes UR/EV/RP/FR/AD |
| Activity `diffForHumans()` showed “weeks from now” for future `created_at` rows | High (misleading) | **Fixed** — absolute `Y-m-d H:i` + exclude future-created events from feed |
| Chart title still said “Activity (14 days)” under 7-day filter | Medium | **Fixed** — `chart_activity_period` with `:days` |
| Locale switcher showed “EN EN” in Arabic | Medium | **Fixed** — single EN target when `locale=ar` |
| Locale switcher hidden on mobile (`hidden sm:inline-flex`) | Medium | **Fixed** — always visible |
| Mobile nav still labeled old “Admin” | Low | **Fixed** — `dashboard_command` |
| Large action counts (1,389) cramped in 11×11 box | Low | **Fixed** — wider pill for ≥1000 |
| Identity backlog always felt “urgent” | Low | **Adjusted** — medium only when >50; not incident-high |
| Topbar accent indigo vs brand purple | Low | **Fixed** — `#7138F5` |
| Live authenticated Cursor browser session | Process | **Worked around** — static snapshot + curl auth proof |

---

## 8. Fixes applied (this QA pass)

- `resources/views/admin/dashboard.blade.php` — badges, timestamps, period chart title  
- `resources/views/components/admin/topbar.blade.php` — locale control + brand color  
- `resources/views/components/admin/mobile-nav.blade.php` — command center label  
- `resources/views/components/admin/dashboard/action-item.blade.php` — large counts  
- `app/Services/Admin/Dashboard/DashboardAnalyticsService.php` — future events filter; identity priority  
- `lang/{en,ar}/admin.php` — `chart_activity_period`  
- `tests/Feature/AdminPanelTest.php` — assertion update  

---

## 9. Data accuracy checks (read-only, period = last 7 days)

All compared KPI/service values vs direct SQL counts:

| Metric | Dashboard | DB | Match |
|--------|-----------|-----|-------|
| Total users | 1,504 | 1,504 | OK |
| Active accounts | 115 | 115 | OK |
| Published events | 154 | 154 | OK |
| Draft events | 0 | 0 | OK |
| Pending reports | 0 | 0 | OK |
| Pending identity | 1,389 | 1,389 | OK |
| Failed jobs | 0 | 0 | OK |
| Friendships accepted | 3 | 3 | OK |
| Friendships pending | 1 | 1 | OK |
| New users (7d) | 476 | 476 | OK |
| Events created (7d) | 9 | 9 | OK |
| Participants (7d) | 3 | 3 | OK |
| Event messages (7d) | 5 | 5 | OK |
| Direct messages (7d) | 19 | 19 | OK |

Notes:

- Session-active previous period 0 → delta shown as `+10` **without** inventing 100% (percent null).  
- Timezone: `config('app.timezone')` (UTC in meta).  
- Event **605** is now `published` (not draft) — dashboard draft count 0 is consistent; statuses were **not** modified by this QA.  
- Permission scope: cache key includes `sa` vs `mod`; audit rows only for super-admin.

---

## 10. Action Center verification

| Action | Count | Priority | Destination | Live HTTP (admin cookie) |
|--------|-------|----------|-------------|---------------------------|
| Pending reports | 0 | Clear | `/admin/reports` | 200 |
| Pending identity | 1,389 | Medium | `/admin/users?status=pending_documents` | 200 |
| Failed jobs | 0 | Clear | no href (count-only) | n/a |
| Draft events | hidden when 0 | — | would be `/admin/events?status=draft` | 200 |

Drafts are informational only — copy states they are **not** a publish-approval queue.

---

## 11. Activity feed verification

| Check | Result |
|-------|--------|
| Ordering | Newest absolute timestamp first after fix |
| Timestamps | Absolute app-tz `Y-m-d H:i` (no misleading “from now”) |
| Types observed | user_registered, event_created, friend_accepted, admin_audit |
| Private message bodies | Absent on dashboard |
| Failed-job exceptions | Absent |
| Missing types (documented) | No unified stream for join/leave/cancel/complete; derived queries only |
| Deleted entities | Links go to admin edit/index; soft 404 risk if deleted later — acceptable |

---

## 12. Performance

| Check | Result |
|-------|--------|
| Uncached `build()` | ~tens of ms in CLI (aggregations only) |
| Cached `build()` | Substantially faster (60s TTL) |
| Cache key | `admin.dashboard.v2.{period}.{locale}.{sa\|mod}` |
| Unbounded queries | Avoided (period clamp ≤90d; feed limits 5–12; recent events 10) |
| Chart render | 7 instances OK on desktop/tablet/mobile |
| Note | QA used `Cache::flush()` briefly; `sitemap.xml` refetch returned 200 afterward |

---

## 13. Tests

```
php artisan test --filter='ReportingPeriodTest|AdminDashboardV2Test|AdminPanelTest::test_dashboard'
Tests: 11 passed (72 assertions)
```

Covers auth, sections, KPI/status/actions, zero-baseline percent, DM privacy, RTL, failed-job non-leak, period math.

---

## 14. Remaining issues

1. **Live Cursor browser login** still blocked for HttpOnly cookies — operator should spot-check `/admin` while logged in.  
2. **1,389 pending identity** dominates Action Center — accurate but may need product workflow (bulk triage) later.  
3. **Activity coverage** incomplete vs full product lifecycle (no join/cancel instrumentation).  
4. **Screenshot PNGs** live in Cursor workstation temp screenshots dir (not committed binaries).  
5. Uncommitted QA fixes on `main` awaiting explicit commit.  
6. Optional: failed-jobs admin retry UI still absent (count only).

---

## 15. Final verdict

### **CONDITIONAL**

**Why not BLOCKED:** Real browser rendering verified (EN/AR, tablet, mobile), charts healthy, no overflow, metrics matched DB, action links 200, privacy preserved, tests green, defects fixed.

**Why not PASS:** Live authenticated `/admin` inside Cursor browser was unavailable (HttpOnly cookie injection denied); screenshots used production-data static snapshots with a QA banner, and PNG artifacts are not checked into the repo.

**To reach PASS:** Operator opens `https://funnity.com/admin` while logged in as platform admin, confirms EN↔AR toggle and one period change, and optionally commits the QA fix diff.
