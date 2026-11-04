# Funnity Production Health & Functional Audit

**Date:** 2026-11-03  
**Scope:** `/home/d14098/funnity.com` (Laravel + web + Flutter `mobile/`)  
**Mode:** READ-ONLY audit — no fixes, no deployments, no production writes initiated by the auditor  
**Auditor role:** Senior Laravel Architect / Full-Stack QA / Security / Reliability  

---

## A. Executive Summary

**VERDICT: CONDITIONAL**  
**Production Health: Degraded**

The public site responds over HTTPS, core guest surfaces (home, events, SEO landings, sitemap/robots) work, and API discovery endpoints return data. However, **critical background processing is not running for Funnity**: there is **no `schedule:run` cron** and **no queue worker** for this app. Two `NotifyFriendsOfPublishedEvent` jobs have been pending since **2026-10-27**. Friend/publish-related async notifications are therefore unreliable.

Additionally, the production document root is a **dirty working tree** (~205 changed/untracked paths on `main` at `3e91976`), so runtime PHP/Blade/JS may not match the last commit. Communities are **not implemented** as a member product surface (web/API/Flutter). Web Arabic typography uses **IBM Plex Sans Arabic**, not the approved **Tajawal**. Security response headers (HSTS/CSP/X-Frame-Options/etc.) are largely absent. Gmail deliverability for transactional mail remains a known business risk (not re-tested; no mail sent).

Automated tests (isolated sqlite): **Unit 58/58 pass**; **Feature 414 pass / 6 fail**. Failures reflect captcha gating, slug redirect expectations, dashboard widget assertions, city distance list drift, and admin QA markup — not proof of full production E2E health.

---

## B. Environment Baseline

| Item | Verified value | Evidence |
|------|----------------|----------|
| Git branch | `main` | `git branch --show-current` |
| HEAD commit | `3e919763a46b18496ceae2693453b5bec7a34628` (2026-10-31) — *Admin V3.1 prototype* | `git log -1` |
| Working tree | Dirty: ~94 modified, ~113 untracked (205 short-status lines) | `git status --short` |
| Commit/push during audit | None | — |
| Laravel | 12.69.2 | `php artisan --version` / `about` |
| PHP | 8.2.35 | `php -v` |
| Composer | 2.9.4 | `composer --version` |
| APP_ENV | production | `.env` / `artisan about` |
| APP_DEBUG | false | `.env` / `about` |
| APP_URL | https://funnity.com | `.env` |
| Database | mysql (engine version not queried deeply) | `about` drivers |
| Queue | database | `about` / `.env` |
| Cache | database | `about` |
| Session | database | `about` |
| Mail default | smtp → `mail.funnity.com:465` | `.env` / config |
| Brevo mailer | Isolated `brevo` mailer present; **not** default | `config/mail.php`, `config('mail.default')=smtp` |
| Broadcasting | pusher (cluster mt1); Reverb env mostly empty/local | `.env` |
| Media | R2 public + private disks | `.env` |
| Config/route cache | Config NOT cached; Routes NOT cached; Views CACHED | `artisan about` |
| `public/storage` | Symlink OK → `storage/app/public` | `ls` / `about` |
| Disk | `/` 46% used (~160G free) | `df -h` |
| Flutter app | `mobile/` `fun_mobile` 1.0.2+3; Dart ^3.13.1; Flutter 3.47.8 available at `/home/d14098/flutter-3.47` | `pubspec.yaml`, analyze |
| Web build | Vite (`/build/manifest.json` 200; home module scripts present) | HTTP probes |
| Routes | 285 registered (~112 api, ~125 web, ~48 admin) | `route:list --json` |

---

## C. Production Health

### C.1 Application logs

- `storage/logs/laravel.log` size ~2.2KB at audit time.
- Recent **application** signal (before audit noise): `registration_captcha_rejected` warnings (2026-11-02) — Turnstile gate active.
- No recurring ERROR storm in the current log file.
- Audit-induced ERROR lines from invalid `route:list --columns` usage (2026-11-03) — **not** a production user defect.

### C.2 Queue & scheduler — CONFIRMED DEGRADED

| Check | Result |
|-------|--------|
| `failed_jobs` | 0 |
| `jobs` pending | **2** |
| Oldest pending | **2026-10-27T05:20:38Z** — `App\Jobs\NotifyFriendsOfPublishedEvent` (also second job same class later same day) |
| User crontab | **No** `funnity.com` `schedule:run` or `queue:work` entries (other projects do have schedulers) |
| `supervisorctl` / supervisord conf for Funnity | Not found |
| Live `queue:work` process for Funnity | Not observed |
| Scheduled tasks defined | `account:purge-deletions` daily (`routes/console.php`) — **will not run** without cron |

**Impact:** Friend-of-publish notifications and any other queued work stall. Account purge schedule is inert.

### C.3 Infrastructure / HTTP

| Check | Result |
|-------|--------|
| HTTPS home | 200 ~0.16s |
| `/up` | 200 |
| `/events` | 200 |
| `/login`, `/register` | 200 |
| `/dashboard`, `/admin`, `/friends`, `/chats`, `/notifications`, `/my-events` | 302 → `/login` (auth gate works for guests) |
| `/discover` | **404** (no web route; discover is API + home/Flutter) |
| `/communities` | **404** (feature absent) |
| SSL | Let's Encrypt; valid **2026-09-24 → 2026-12-23**; CN=`www.funnity.com` |
| `www` → apex | 301 → `https://funnity.com/` |
| `http` → `https` | 301 |
| Security headers on home | **Missing** HSTS, CSP, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy (only `server: Apache`, cache-control, cookies) |
| PHP-FPM | Master process present (cPanel) |
| Disk / symlink | Healthy |

### C.4 Configuration consistency

- Views cached while config/routes not cached → Blade compiled cache may lag source edits until view recompile; ops risk with dirty tree.
- `LOG_LEVEL=debug` in production `.env` — verbose logging posture.
- Broadcasting uses **Pusher cloud**, not local Reverb (`REVERB_APP_ID` empty; `REVERB_HOST=localhost`).

---

## D. Functional Audit

### D.1 Authentication

| Area | Status | Evidence |
|------|--------|----------|
| Guest browse home/events | WORKING — VERIFIED | HTTP 200 |
| Auth required for dashboard/friends/chats | WORKING — VERIFIED | 302 → login |
| Registration + Turnstile (web) | WORKING — VERIFIED (gate active) | `TURNSTILE_ENABLED_WEB=true`; log captcha rejects; register view includes widget |
| API registration without Turnstile | WORKING — VERIFIED (by design) | `TURNSTILE_ENABLED_API=false`; tests in `ApiRegistrationHardeningTest` |
| OTP / verify / resend / reset routes | PRESENT (code + routes) | `routes/web.php`, `routes/api.php` |
| OTP send / inbox placement | NOT TESTED — no email sent | Safety rule |
| Login/logout session | NOT TESTED (would create session as real user) | — |
| Open redirects via `returnTo` query | NOT TESTED deeply; no `returnTo` in JS/Blade grep; uses Laravel `intended()` after auth | Controllers use `redirect()->intended(...)` |
| Phone registration | NOT TESTED / not observed as primary path | Email-centric auth |

**Mail:** Default mailer remains **smtp / mail.funnity.com**. Isolated **brevo** mailer configured via `BREVO_MAIL_*` and is **not** `MAIL_MAILER`. Gmail Spam issue: **HISTORICAL — confirmed business issue; not re-sent in this audit**.

### D.2 Events

| Area | Status | Evidence |
|------|--------|----------|
| Public event index | WORKING — VERIFIED | `/events` 200 |
| Public event show (slug URL) | WORKING — VERIFIED | `/events/605/{slug}` 200; OG image on R2; JSON-LD; canonical |
| Bare `/events/{id}` without slug | CONFIRMED BROKEN vs SEO contract | Live **200** (not 301); `EventController::show` only redirects on *wrong* slug, not missing slug (`EventController.php` ~240–270). Feature test expects 301. Duplicate URL risk. |
| City SEO landing | WORKING — VERIFIED | `/events/in/cairo` 200 |
| Create/edit/join/cancel/tickets | PRESENT in routes + `EventPolicy` | NOT TESTED (writes) |
| Capacity / duplicate joins | Code/policies exist | NOT TESTED |
| DB scale (aggregate) | users=114; events=557 (153 published, 401 completed); event_members=2031 | Read-only counts |

**Note:** `EventController::show` / slug middleware may `saveQuietly()` a generated slug on GET if blank — side effect on read path (POTENTIAL ISSUE).

### D.3 Discover & geolocation

| Area | Status | Evidence |
|------|--------|----------|
| `GET /api/discovery/map` (correct north/south/east/west) | WORKING — VERIFIED | 200; markers/clusters/city_aggregates/meta |
| Wrong bbox query (`bbox=`) | Validation 422 | Expected |
| `GET /api/events/discover/home` | WORKING — VERIFIED | 200; `nearby` (8) + `worth_the_trip` + meta distance labels |
| `GET /api/events` | WORKING — VERIFIED | 200 paginated; `image_url` fields present on sample |
| Web `/discover` path | N/A / 404 | Discover UX is home + API + Flutter tab |
| Historical city filter / thumbnail / scroll bugs | NOT RE-CONFIRMED on authenticated web UI | Prior audits exist under `docs/audits/`; this pass did not E2E authenticated Discover UI |

### D.4 Friendships & people

| Area | Status | Evidence |
|------|--------|----------|
| Web/API friend routes | PRESENT + auth middleware | `route:list` |
| Friendships in DB | 5 rows | Aggregate count |
| Suggestions / accept / duplicate | NOT TESTED | Requires authenticated writes |
| Blocks | Routes present (`settings/blocks`) | NOT TESTED |

### D.5 Communities

| Area | Status | Evidence |
|------|--------|----------|
| Member community product | CONFIRMED ABSENT | No community routes in `route:list`; no Flutter `features/communities`; only `DemoCommunitySeeder` / incidental mentions |
| Business rule “any user can create community as owner” | NOT ENFORCEABLE — feature missing | — |

### D.6 Chat & realtime

| Area | Status | Evidence |
|------|--------|----------|
| Web/API chat & DM routes | PRESENT + sanctum/web auth | Routes |
| Pusher config | Keys present (not printed) | `.env` presence |
| Live authenticated chat E2E | NOT TESTED | No private message content read |
| Flutter realtime client | PRESENT in source | `chat_realtime_client.dart` |
| Queue-backed notification side effects | DEGRADED | Queue worker missing |

### D.7 Notifications & push

| Area | Status | Evidence |
|------|--------|----------|
| DB notifications | 44 rows; types include friend/join/reaction | Aggregate |
| Device tokens | 11 | Aggregate |
| FCM credentials | `FIREBASE_*` set | Presence only |
| Send push | NOT TESTED | — |
| Friend publish notify job | STALLED in queue | See C.2 |

### D.8 Tickets

| Area | Status | Evidence |
|------|--------|----------|
| Ticket routes (web/api) | PRESENT + auth | Routes |
| Generation / validation E2E | NOT TESTED | — |

---

## E. Web Audit

### E.1 Public

| Surface | Status | Evidence |
|---------|--------|----------|
| Home | WORKING — VERIFIED | 200; Vite assets; brand tokens `#7138F5`, `#FF762E`, `#F8F9FE` present in HTML; title/OG/canonical set; no `dark:` classes observed |
| Events list / show | WORKING — VERIFIED | Above |
| `/ar` RTL | WORKING — VERIFIED | `dir=rtl` `lang=ar` |
| Arabic font | CONFIRMED DEVIATION | Bunny Fonts **IBM Plex Sans Arabic** — **not Tajawal** (approved requirement) |
| Legal/safety | Routes present; robots allows `/safety` | Partial |
| Preview Mapbox route | Exists in routes; unsigned URL **404** (signed/auth gate) | Expected isolation |

### E.2 Authenticated shells (guest probes only)

Dashboard / friends / chats / notifications / my-events / settings redirect to login — **WORKING** gate.  
Authenticated layout, sticky nav, empty states, JS errors in-session — **NOT TESTED**.

### E.3 Broken/missing paths often expected by product language

- `/discover`, `/communities`, `/profile`, `/settings`, `/create-event` → **404** (actual routes: `/dashboard`, `/settings/profile`, `/events/create`, etc.)

### E.4 Admin

`/admin` → login redirect for guests. Admin markup test expecting `admin-dash-toolbar` **fails** in suite — UI contract drift (**POTENTIAL** / test debt). Authenticated admin E2E **NOT TESTED**.

---

## F. Flutter Audit

### F.1 Source inventory

- Path: `mobile/` (`fun_mobile` 1.0.2+3).
- Prod API default: `https://funnity.com/api`.
- Features: auth, discover, events, friends/DMs, chat, notifications/FCM, profile — structured Riverpod.
- **No communities feature module.**
- Tajawal bundled in Flutter fonts (mobile meets Arabic font requirement; web does not).

### F.2 Config gaps

| Finding | Status |
|---------|--------|
| `mobile/android/key.properties` absent | Expected in this checkout; release signing fail-closed |
| Android `allowBackup=false` | WORKING — VERIFIED |
| iOS `GoogleService-Info.plist` absent vs Android `google-services.json` present | POTENTIAL ISSUE (iOS push) |
| Bundle ID mismatch Android `com.funnity.app` vs iOS `com.funity.funMobile` | POTENTIAL ISSUE |
| Cleartext traffic disabled | Good for prod; local HTTP fragile |

### F.3 Static analysis

Command: `flutter analyze --no-pub` in `mobile/`  

**Result:** 16 issues (0 errors): 3 warnings (unused import; unnecessary null / dead code in `location_page.dart`), rest info-level.  

**Flutter tests:** 48 test files present — **NOT RUN** in this pass (analyze only).

### F.4 Live device/emulator E2E

**NOT TESTED.** Do not claim chat/auth flows verified end-to-end on device.

---

## G. Security Findings

### Confirmed / strongly evidenced

| ID | Severity | Finding |
|----|----------|---------|
| SEC-01 | P2 | Missing common security headers (HSTS, CSP, frame/content-type options) on HTTPS responses |
| SEC-02 | P3 | `LOG_LEVEL=debug` in production |
| SEC-03 | P1 (ops) | Production serves from dirty git tree — unreproducible / unreviewed code may be live |
| SEC-04 | P3 | SSL CN on `www` while apex is canonical (works via redirects; monitor renewal) |

### Mitigations observed

- `APP_DEBUG=false`
- Turnstile on web registration
- Auth middleware on sensitive web/API routes; admin behind auth
- Session cookie `secure` + `httponly` + `samesite=lax`
- Private chat media controllers call `$this->authorize('chat', $event)`
- EventPolicy gates view/update/join/manage
- Flutter secure token storage; backup disabled on Android

### Potential (code review; not exploited)

| ID | Severity | Finding |
|----|----------|---------|
| SEC-05 | P2 | Bare event ID URLs serve 200 — duplicate content / weaker canonical enforcement |
| SEC-06 | P3 | GET show path may persist slug if blank (`saveQuietly`) |
| SEC-07 | P2 | IDOR across all APIs — **NOT fully audited**; spot-check only |
| SEC-08 | P3 | Laravel `intended()` open-redirect hardening relies on framework defaults — no custom allowlist observed |

**Aggressive pentest:** not performed (per rules).

---

## H. Performance & SEO

### Performance

- Home TTFB ~0.16s in probe; event show ~0.40s — acceptable in spot checks.
- Queue backlog ages jobs → delayed fan-out work.
- Config/routes not cached in production — minor CPU overhead.
- N+1 across all endpoints — **NOT systematically measured**.
- R2 cover images used on event show — good vs local disk.

### SEO

| Check | Status |
|-------|--------|
| Home title/description/OG/canonical | WORKING — VERIFIED |
| Event show OG + JSON-LD + canonical | WORKING — VERIFIED |
| `robots.txt` | WORKING — VERIFIED; Disallow private paths; Sitemap declared |
| `sitemap.xml` | WORKING — VERIFIED; ~135 `<loc>` |
| Bare event ID vs slug URL | CONFIRMED issue (200 both) |
| `/ar` alternate | WORKING — VERIFIED |

---

## I. Issues Register

| ID | Component | Severity | Status | Evidence | User impact | Recommended fix | Complexity | Verification |
|----|-----------|----------|--------|----------|-------------|-----------------|------------|--------------|
| OPS-01 | Queue/Scheduler | P0 | CONFIRMED BROKEN | No funnity cron; 2 jobs since 2026-10-27 | Missed friend/publish notifications; purge never runs | Add `* * * * * schedule:run` + supervised `queue:work` for funnity.com; drain backlog | M | `supervisorctl`/`ps` + job count → 0 |
| OPS-02 | Deploy hygiene | P1 | CONFIRMED BROKEN | 205 dirty paths on production tree | Unknown/unreviewed behavior in prod | Freeze deploy process; commit/review or deploy from clean artifact | L | Clean CI artifact = runtime |
| FEAT-01 | Communities | P1 | CONFIRMED BROKEN (absent) | No routes/Flutter feature | Promised product surface missing | Implement or explicitly descope in product docs | L | Routes + E2E create/join |
| SEO-01 | Event URLs | P2 | CONFIRMED BROKEN | `/events/{id}` 200; test expects 301 | Duplicate URLs | 301 bare ID → canonical slug show | S | curl -I expect 301 |
| WEB-01 | Arabic typography | P2 | CONFIRMED BROKEN vs design | IBM Plex Arabic on `/ar`, not Tajawal | Brand/design non-compliance on web | Load Tajawal for `dir=rtl` | S | View-source font CSS |
| SEC-01 | Headers | P2 | CONFIRMED BROKEN | curl -sSI home | Weaker browser hardening | Add HSTS/CSP/XFO/nosniff/Referrer-Policy carefully | M | Header scan |
| MAIL-01 | Deliverability | P1 | HISTORICAL — NO LONGER REPRODUCED *here* (not retested) | Prior Gmail Spam confirmation; no send this audit | OTP/reset may land in Spam | Complete Brevo experiment with owner approval; DNS auth review separately | M | Gmail headers + Brevo logs |
| TEST-01 | AuthRegistrationTest | P3 | CONFIRMED BROKEN (suite) | Captcha fails test redirect | CI signal noise | Bypass Turnstile in `testing` or fake verifier | S | phpunit green |
| TEST-02 | EventSlugRoutingTest | P2 | CONFIRMED BROKEN (code+test) | Same as SEO-01 | — | Fix redirect then test | S | phpunit |
| TEST-03 | Dashboard weather/now-playing tests | P3 | POTENTIAL ISSUE | Assert missing; components still in views | May be conditional empty state | Fix fixtures or assertions | S | phpunit |
| TEST-04 | CityManagement distances_km | P3 | CONFIRMED BROKEN (contract drift) | Expected `[5,10,25,50]` got incl. 100,0 | API contract change vs test | Align test or API docs | S | phpunit |
| TEST-05 | AdminDashboardV21QaRenderTest | P3 | CONFIRMED BROKEN (markup drift) | Missing `admin-dash-toolbar` | Admin QA harness stale | Update selectors | S | phpunit |
| FLT-01 | iOS Firebase plist | P2 | POTENTIAL ISSUE | File absent | iOS push may fail | Add `GoogleService-Info.plist` securely | S | Device push |
| FLT-02 | Bundle ID mismatch | P3 | POTENTIAL ISSUE | Android vs iOS IDs | Store/analytics confusion | Align identifiers deliberately | M | Store consoles |
| FLT-03 | Analyze warnings | P4 | WORKING with nits | `flutter analyze` 16 issues | Low | Clean unused import / dead code | S | analyze clean |
| REALTIME-01 | Chat E2E | P1 | NOT TESTED | — | Unknown live reliability | Authenticated staging E2E | L | Device + web |
| AUTH-E2E-01 | Full auth lifecycle | P1 | NOT TESTED | No OTP/email sent | Unknown | Staging E2E | L | Staging |
| PERF-01 | N+1 / indexes | P3 | NOT TESTED | — | Possible slow pages | Query log sampling | M | Telescope/slow log |

---

## J. Recommended Fix Order

1. **P0 OPS-01** — Install Funnity scheduler cron + queue worker; drain `NotifyFriendsOfPublishedEvent` backlog.  
2. **P1 OPS-02** — Stop shipping from an uncommitted production tree; establish clean release path.  
3. **P1 MAIL-01** — Finish approved Brevo Gmail probe (secret already configurable); decide mail strategy without flipping default until Inbox proven.  
4. **P1 FEAT-01** — Product decision: implement Communities or formally remove from roadmap/marketing.  
5. **P1 REALTIME/AUTH E2E** — Staging authenticated chat + OTP flows.  
6. **P2 SEO-01 / SEC-01 / WEB-01** — Slug redirects, security headers, Tajawal on web RTL.  
7. **P2 FLT-01** — iOS Firebase config.  
8. **P3** — Test suite drift, log level, Flutter analyze nits.

---

## K. Test Evidence

### Commands executed (non-exhaustive)

- `git branch/status/log`, `php artisan about`, `php artisan schedule:list`, `php artisan route:list --json`
- Read-only DB aggregates (`jobs`, `failed_jobs`, counts) — **no row mutations intended**
- `curl` GET/HEAD to public HTTPS endpoints; SSL check
- `crontab -l` (read); process listing
- `php artisan test --testsuite=Unit` → **58 passed (135 assertions)**
- `php artisan test --testsuite=Feature` → **414 passed, 6 failed (1812 assertions)** — sqlite `:memory:` forced via `phpunit.xml`
- `flutter analyze --no-pub` → **16 issues (0 errors)**

### Feature failures

1. `AdminDashboardV21QaRenderTest::dump_dashboard_html_for_visual_qa`  
2. `AuthRegistrationTest::user_can_register_and_must_verify_email` (Turnstile)  
3. `CityManagementTest::active_cities_are_listed_publicly` (`distances_km`)  
4. `EventNowPlayingTest::dashboard_shows_now_playing…`  
5. `EventSlugRoutingTest::bare_event_id_redirects_to_slug_url`  
6. `WeatherApiTest::dashboard_shows_weather_card…`

**Passing unit/feature tests ≠ production E2E proof.**

---

## L. Untested Areas

- Any authenticated browser session flows (dashboard widgets, join, create event, friends accept, chat send, tickets).  
- OTP issuance, email content, Gmail Inbox/Spam (no mail sent).  
- Push notification delivery.  
- Payment flows (if any).  
- Write APIs / destructive methods.  
- Full IDOR matrix.  
- Flutter `flutter test` and device/emulator runs.  
- Load/performance profiling and index analysis.  
- Reverb local websocket (production uses Pusher).  
- Admin authenticated operations.  
- Migration status drift vs dirty tree (not run `migrate:status` against prod changes).

---

## M. Production Safety Confirmation

**Changed during this audit:**

- Created documentation file only: `docs/audits/FUNNITY_PRODUCTION_AUDIT_2026-11-03.md`

**Did not:**

- Modify `.env`, DNS, code behavior (except adding this doc), database rows, caches (`config:clear` / `route:clear` / `view:clear` not run), queue workers, PHP-FPM/Nginx/Apache/Reverb, dependencies, commits, pushes, OTP/email/push sends, or create users/events.

**Ongoing email/Brevo work:** left intact; default mailer unchanged; no live SMTP test executed in this audit.

**Note:** Feature PHPUnit run uses isolated sqlite and may write ephemeral test DB only — not production MySQL application data. Live probes were GET/HEAD only. Event show code paths in production may self-heal empty slugs on GET (application behavior, not auditor-initiated writes).

---

*End of audit report.*
