# Funnity AI Marketing Manager — System Audit (Phase 0)

**Status:** READ-ONLY audit  
**Date:** 2026-10-31  
**Environment inspected:** production host `d14098.usc1.stableserver.net`, app path `/home/d14098/funnity.com`  
**Evidence labels:** VERIFIED · ASSUMED · REQUIRES CONFIRMATION · BLOCKED

---

## 1. Executive summary

Funnity runs on **Laravel 12.69.2 / PHP 8.2.35** with **MariaDB 11.4.13**, Blade + Vite + Tailwind admin UI, Sanctum mobile APIs, and database-backed queue/cache/session. There is **no existing advertising, UTM, pixel, or marketing-attribution schema**. Platform admin access exists (`platform_role` + `EnsurePlatformAdmin`) with an `admin_audit_logs` pattern that marketing approvals can extend.

**Infrastructure verdict for hosting the module:** use **existing Laravel services only**, with mandatory addition of a **Funnity-specific scheduler cron** and a **queue worker** (currently absent for this app). Do **not** install Ollama as a production dependency on this host for the marketing engine.

---

## 2. Backend (VERIFIED)

| Item | Value | Confidence |
|------|-------|------------|
| Framework | Laravel `v12.69.2` (`composer.lock`) | VERIFIED |
| PHP requirement | `^8.2` | VERIFIED |
| PHP CLI | `8.2.35` NTS + OPcache + ionCube | VERIFIED |
| PHP `memory_limit` (CLI) | `512M` | VERIFIED |
| App URL / env | `funnity.com`, `APP_ENV=production`, debug OFF | VERIFIED |
| Auth (web) | Session (`database` driver) | VERIFIED |
| Auth (API) | Laravel Sanctum (`personal_access_tokens`) | VERIFIED |
| Social login | Google OAuth (Socialite), Apple | VERIFIED |
| Queue | `database` (`jobs`, `job_batches`, `failed_jobs`) | VERIFIED |
| Cache | `database` | VERIFIED |
| Broadcasting | Pusher / Reverb config present | VERIFIED |
| Mail | SMTP | VERIFIED |
| Object storage | AWS S3 + Cloudflare R2 env keys present | VERIFIED |
| Notable packages | Sanctum, Socialite, Reverb, Google API Client, Flysystem S3 | VERIFIED |

### Architecture patterns observed

- Controllers under `app/Http/Controllers/{Web,Api}/…`
- Domain services under `app/Services/{Admin,Auth,Events,…}`
- Eloquent models under `app/Models`
- Policies under `app/Policies`
- Enums under `app/Enums` (e.g. `PlatformRole`)
- **No `app/Repositories` directory** — current style is Controller → Service → Model
- Admin audit helper: `App\Services\Admin\AdminAuditLogger`

**Implication for marketing module:** introduce a **marketing-scoped Repository layer** for provider sync and performance snapshots without rewriting the whole app. Keep Controller → Service → Repository → Model for marketing bounded context only.

### Authorization (VERIFIED)

- Middleware: `platform.admin` → `EnsurePlatformAdmin`
- Roles: `PlatformRole::SuperAdmin`, `PlatformRole::Moderator`
- SuperAdmin-only today: site settings, audit log viewing
- Admin user count in DB: **1** (VERIFIED via query)
- No granular RBAC package (Spatie etc.) — role enum + policies

**Marketing permission recommendation:** add SuperAdmin-only capabilities first (`canManageMarketing`, `canApproveMarketingActions`, `canConnectAdAccounts`). Do not grant Moderators write/execute rights by default.

### Existing integrations (VERIFIED)

Present: Google OAuth, Apple Sign-In, Firebase push credentials (env), Weather API config, map/Photon place search, Reverb/Pusher realtime, R2/S3 media.

**Absent:** Meta Marketing API, Meta Pixel/CAPI, TikTok Marketing API, TikTok Pixel/Events API, UTM capture tables, ad spend tables, Telegram marketing notifier.

---

## 3. Frontend / Admin UI (VERIFIED)

| Item | Value |
|------|-------|
| Admin stack | Blade views + Alpine/vanilla via Vite bundle |
| CSS | Tailwind CSS v4 via `@tailwindcss/vite` |
| Layout | `resources/views/layouts/admin.blade.php` (sidebar + topbar + mobile nav) |
| Shell pattern | Same dashboard shell family as member dashboard |
| Locale | EN/AR with `dir` rtl/ltr |
| Typography (current) | IBM Plex Sans + IBM Plex Sans Arabic (Bunny Fonts) |
| Brand primary | Purple `#7138F5` (`DESIGN.md`) |
| Build | `vite` + `laravel-vite-plugin` |
| SPA / React admin | Not used |

Admin routes live under `/admin/*` with `platform.admin` middleware (`routes/web.php`).

**Integration approach:** add Marketing nav section and Blade pages under `resources/views/admin/marketing/*` using `layouts/admin`. Do not touch public marketing home `/` or Flutter for this module (admin-only). Optional JSON endpoints under `/api/admin/marketing/*` for charts if needed — still admin-gated.

**Typography note:** Phase brief asked for Tajawal; **current platform standard is IBM Plex Arabic** (`DESIGN.md`). Recommendation: **keep IBM Plex** for consistency unless product owner explicitly overrides brand typography.

---

## 4. Database (VERIFIED — read-only)

| Item | Value |
|------|-------|
| Engine | MariaDB **11.4.13** (MySQL-compatible driver `mysql`) |
| Database | `d14098_fun` |
| Host | `127.0.0.1:3306` |
| Tables | **33** |
| Approx size | **4.02 MB** |

### Domain tables relevant to outcomes

| Table | Rows (approx) | Marketing relevance |
|-------|---------------|---------------------|
| `users` | 1,510 | Registrations / activation |
| `events` | 558 | Event creation KPI |
| `event_members` | 2,032 | Joins / participation |
| `event_join_requests` | 120 | Funnel mid-stage |
| `identity_documents` | 21 | Onboarding / trust gate |
| `friendships` | 4 | Activation proxy |
| `admin_audit_logs` | 7 | Pattern for marketing audit |
| `site_settings` | 1 | App branding/version only |
| `jobs` / `failed_jobs` | present | Queue infrastructure |

### Not present (VERIFIED by schema + migration scan)

- Communities / community membership tables
- UTM / click IDs (`gclid`, `fbclid`, `ttclid`)
- Marketing accounts, campaigns, ads, snapshots
- Conversion / attribution tables
- Consent / cookie preference store beyond legal pages
- Advertising credentials store

`users` columns include: identity, `platform_role`, locale, city, sex, deactivation — **no acquisition source fields**.

---

## 5. Server / hosting (VERIFIED)

| Item | Value | Notes |
|------|-------|-------|
| Host | `d14098.usc1.stableserver.net` | StableServer / cPanel-style |
| OS | Rocky/RHEL 9 family (`el9_7`), kernel 5.14 | VERIFIED |
| CPU | **8** logical CPUs | VERIFIED |
| RAM | **15 GiB** total; ~**9.8 GiB** available at audit | VERIFIED |
| Swap | 2 GiB (~648 MiB used) | VERIFIED |
| Disk | `/` **307G**, ~**163G** free (45% used) | VERIFIED |
| Web | Apache `httpd` + php-fpm (cPanel) | VERIFIED |
| Docker | **Not available** to this user (`which docker` empty, no docker.sock) | VERIFIED |
| Redis CLI | Present (`/usr/bin/redis-cli`) | Connectivity/auth **REQUIRES CONFIRMATION** |
| Supervisor | System `supervisord` running as root | User cannot assume Funnity programs are managed there |
| Cron | User crontab **exists** | **No Funnity entries** (VERIFIED) |
| Node.js | v20.20.2 / npm 10.8.2 | VERIFIED |
| Composer | 2.9.4 | VERIFIED |
| Ollama binary | `/home/d14098/ollama/bin/ollama` v0.13.4 | **Daemon not running**; `ollama list` failed to connect |
| Outbound HTTPS | Google 200; `graph.facebook.com` 400 (reachable); TikTok Business API 301; OpenAI 421 | Network path exists; status codes are endpoint-normal |

### Process / execution notes

- PHP `disable_functions`: empty (VERIFIED)
- `open_basedir`: empty (VERIFIED)
- `allow_url_fopen`: On
- User process limits high (`ulimit` open files 200000)
- Other apps on same account run Laravel schedulers via cron (souqnest, concordia, etc.) — **shared multi-app host**

### Funnity operational gaps (VERIFIED)

1. **No crontab entry** for `funnity.com` `schedule:run`
2. **No long-running `queue:work` / Horizon** observed for Funnity
3. Scheduler defines `account:purge-deletions` daily in `routes/console.php` but will not run without cron
4. `jobs` table had **2** pending jobs at audit — indicates queue is used but worker reliability is **REQUIRES CONFIRMATION**

---

## 6. Security posture relevant to marketing (high level)

| Topic | Finding |
|-------|---------|
| Secrets in docs | Not reproduced; `.env` keys exist for Google/AWS/R2/Reverb/mail |
| Encryption key | `APP_KEY` present (`encrypt_ok=yes`) — can encrypt tokens at rest |
| CSRF | Web forms use `@csrf` / CSRF token meta in admin layout |
| Admin surface | Session auth + role middleware; `noindex` SEO on admin |
| Existing audit | `admin_audit_logs` with actor/action/subject/meta JSON |

Full threat model: see `MARKETING_SECURITY.md`.

---

## 7. Flutter / public web impact

- Marketing Manager is **admin-only**.
- Per workspace parity rule, **no Flutter member surface required** unless member APIs change.
- Phase 0 proposes **no changes** to `/` marketing home or Flutter.

---

## 8. Confidence & limitations

| Area | Limitation |
|------|------------|
| Redis usability | CLI present; auth/DB assignment not probed to avoid side effects |
| Exact cPanel plan / egress quotas | Not visible from user shell |
| Meta/TikTok production app status | No credentials or apps in repo — **BLOCKED until account setup** |
| Advertising spend history | None in DB |
| Ollama models | Cannot list; service down |
| Root supervisor programs | Not inspected (would require elevated access) |

---

## 9. Deployment recommendation (from evidence)

| Option | Fit |
|--------|-----|
| **1. Existing Laravel services only** | **RECOMMENDED** |
| 2. Laravel + lightweight external worker | Optional later if sync volume grows |
| 3. Separate service on same server | Not needed; Docker unavailable; adds ops cost |
| 4. Separate VPS | Future option if AI/local LLM or heavy reporting is required |

**Required host ops before Phase 2+ sync jobs:**

1. Add Funnity `* * * * * php artisan schedule:run`
2. Add durable queue worker (`queue:work --sleep=1 --tries=3` via cron `--stop-when-empty` **or** user-level process manager)
3. Confirm outbound HTTPS to Meta/TikTok remains allowed after account creation

---

## 10. Audit artifacts

- Commands used: `php artisan about`, `db:show`, `db:table`, `free`, `nproc`, `df`, `crontab -l`, process listing, HTTPS HEAD probes, filesystem inspection
- **No** migrations run, **no** packages installed, **no** services restarted, **no** credentials printed
