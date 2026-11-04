# Funnity Secure Media — Stage E3 Final Audit

**Date:** 2026-11-01 (UTC)  
**Project:** `/home/d14098/funnity.com`  
**Database inspected:** `d14098_fun` (read-only)  
**Mode:** READ-ONLY — no migrations, deletes, config changes, deployments, Flutter edits, or production fixtures

Supporting inventory dump (local paths only): `storage/app/migrations/e3_local_inventory.json`  
Classified listing: `storage/app/migrations/e3_local_inventory_by_class.txt`

---

## 1. Executive summary

Cloudflare R2 is the configured destination for **new** public and private member uploads (`MEDIA_DISK=r2`, `MEDIA_PRIVATE_DISK=r2_private`). After Stage E2, **all 54** database-referenced event covers and the site logo resolve from R2. Private chat and identity media are delivered only through authenticated Laravel gateways (no private R2 public URL, no presigned URLs in app code). Web lightbox was **owner-accepted** in Stage E2. Automated media security tests **23/23 passed** on PHPUnit sqlite `:memory:`.

**Final verdict: CONDITIONAL**

Core storage and private-access goals are met. Remaining conditionality is operational hygiene: local leftover files (safe to keep until a separate deletion approval), low-severity `media_path` exposure in API JSON, and PHPUnit `DB_*` env entries lacking `force="true"`.

---

## 2. Storage inventory (production inspection)

| Category | DB / disk state | Classification |
|---|---|---|
| Profile images | 6 users with `profile_image_path` (mix of R2 public URL + Google avatars; 0 local `/storage/media/` URLs) | Public / third-party |
| Event covers | **54 / 54 on `image_disk=r2`** | Public (product visibility rules) |
| Event galleries | Not implemented as separate uploads | N/A |
| Community images | No dedicated member upload surface / table | N/A |
| DM attachments | 5 image + 16 voice (`media_path` under `chat/…` on private disk) | **Private** |
| Event-chat attachments | 1 image + 3 voice | **Private** |
| Videos | 0 DM / 0 event-chat rows (`media_kind=video` supported in code) | Private when used |
| Identity documents | **18 on `r2_private`** | **Private** |
| Branding logo | `site_settings.logo_disk=r2`, path `branding/funity-mark.svg` | Public |

**Config (read-only):** public R2 bucket configured; private R2 bucket configured; `R2_PRIVATE_URL` empty; private visibility `private`.

---

## 3. Upload-path matrix

| Category | Endpoint / entry | Service | Disk helper → prod | DB reference | URL / delivery |
|---|---|---|---|---|---|
| Profile photo | Web/API profile update | `UpdateProfileService::storePhoto` | `MediaDisk::public()` → **r2** | `users.profile_image_path` (full public URL) | Direct public URL |
| Event cover (create/update) | Event store/update | `PublicMediaUploader::storeEventImage` | **r2** | `events.image_path` + `image_disk` | `Event::imageUrl()` via disk URL |
| Event cover (admin) | Admin moderate | `ModerateEventService` + uploader | **r2** | same | same |
| Site logo | Admin settings | `SiteSettingsService` | **r2** | `site_settings.logo_*` | Public disk URL |
| DM image/voice/video/file | `POST /dms/{id}`, `POST /api/dms/{id}/messages` | `ChatMediaService` | `MediaDisk::private()` → **r2_private** | `direct_messages.media_*` | Gateway `/dms/media/{id}` / `/api/dms/media/{id}` |
| Event-chat media | Web/API event message store | `ChatMediaService` | **r2_private** | `event_messages.media_*` | Gateway event media routes |
| Identity docs | Identity submit | `PrivateMediaUploader` | **r2_private** | `identity_documents.disk/path` | Owner/admin stream via `PrivateMediaAccessService` |
| Community / gallery | — | — | — | — | Not present |

**Local fallback:** public/private local disks (`media` / `media_private`) remain configured for non-production and legacy reads. New uploads follow `config('media.*')`, which is R2 in production. Stage E2 left local **copies** of migrated objects in place (not used by DB disk columns anymore).

**Verified by:** read-only production config + code path inspection.  
**Not verified by:** live upload (forbidden).

---

## 4. Public / private access matrix

| Asset | Auth required | Mechanism | Cache | Raw R2 private URL? |
|---|---|---|---|---|
| Event cover / logo / profile (public) | No (product rules) | Public R2 / CDN URL | Public CDN (by design) | N/A (public bucket) |
| DM / event-chat media | Yes — participant / `chat` policy | Laravel stream after authz | `private, no-store` (+ no-transform) | **No** — `media_url` is app gateway on `funnity.com` |
| Identity documents | Owner policy / admin policy | `PrivateMediaAccessService` | `private, no-store` | **No** |
| Anonymous private media | Denied | API **401** / web **302** login | — | — |
| Outsider with copied gateway URL | Denied | **403** (tests) | — | — |
| Cleared / missing path | 404 | Stream abort | — | — |

Authorization order for chat media (code): authenticate → resolve message → **participant / policy check** → assert `media_path` → stream from private disk (path must start with `chat/`).

**Presigned URL usage in `app/`:** none found.

---

## 5. Security findings

| ID | Severity | Finding | Evidence class |
|---|---|---|---|
| S1 | — | Private chat media not publicly addressable via R2 URL; gateway only | Production inspection + code |
| S2 | — | Anonymous API media **401**; web media **302** login (probed live) | Production HTTP probe |
| S3 | — | Outsider / logged-out / membership gates covered by automated tests | Automated tests |
| S4 | — | No app-issued temporary/presigned private URLs | Code search |
| S5 | Low | API resources include internal `media_path` alongside gateway `media_url` | Code inspection |
| S6 | Low | PHPUnit `DB_CONNECTION` / `DB_DATABASE` lack `force="true"` (tinker still uses prod — separate risk) | Config inspection |
| S7 | Info | 67 local public + 3 private orphan files remain; **0** still DB-referenced on local disks | Inventory |
| S8 | Info | Prior factory users 1634/1635 no longer present in DB at audit time (external cleanup; this audit did not write) | Production inspection |

No critical private-media exposure found in this pass.

---

## 6. Local-file inventory

**Totals:** 67 on `media` disk, 3 on `media_private`.

| Class | Count | Disposition recommendation |
|---|---|---|
| `duplicate_of_r2_referenced` | **51** | Safe for later cleanup after backup (includes E2 cover + logo locals + manual keys still pointed at by R2 DB paths) |
| `manual_variant_or_orphan` | **13** | Unreferenced alternate takes (`(2)/(3)/(4)` etc.) — recoverable archive or delete later |
| `unreferenced_legacy_avatar` | **2** | Orphan avatars — cleanup candidate |
| `orphan_event_cover_variant` | **1** | `events/5/...` while event 5 has null image — cleanup candidate |
| `unreferenced_identity_orphan` | **3** | `identity/6/*` not in DB — **sensitive**; quarantine/delete only with owner approval |
| `database_referenced_local` | **0** | None remaining after E2 |

Exact paths/sizes: `storage/app/migrations/e3_local_inventory_by_class.txt` (and JSON).  
**Do not delete without a separate E4 authorization.**

---

## 7. Frontend media experience (Web only)

| Item | Status | Evidence class |
|---|---|---|
| In-page image viewer | Deployed (`media-viewer.js`, chat Blade/`dm-chat`/`event-chat`) | Code + prior build |
| Modal close / Escape / return to conversation | **PASS** | **Previously accepted by owner** (Stage E2 gate) |
| No new tab for chat images | Implemented (`data-media-viewer`, no media `target="_blank"`) | Code review |
| Loading / error states | Present in viewer | Code review |
| RTL arrow awareness / responsive CSS | Present | Code review |
| Missing-image handling | Viewer error state; stream 404 for missing objects | Code + tests |
| Unauthenticated live re-test | **Not performed** (forbidden / owner already accepted) | — |
| Flutter UI | Out of scope | — |

---

## 8. Regression and compatibility

| Consumer | Compatibility | Notes |
|---|---|---|
| Laravel Web | OK | Session media routes + lightbox |
| Flutter / Sanctum API | OK | Gateway `media_url` paths unchanged; `media_kind=video` additive |
| Google Play testers | OK | API register Turnstile still off; media APIs unchanged |
| Chat auth | OK | Participant / event `chat` policy still gate streams |
| Events / communities | OK | Covers on R2; no community media feature to break |

**API contracts:** not changed in this audit.

---

## 9. Automated test results

**Preflight:** `phpunit.xml` sets `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:` (force not set). Production default remains `mysql` / `d14098_fun`.  
**Command:** `php artisan test --filter='SecureMediaAccessTest|ChatV2ExtensionsTest|MediaConfigTest'`  
**Result:** **23 passed** (133 assertions)  
**Production impact:** no tinker factories; max user id observed after tests unchanged by this suite’s sqlite isolation (suite does not target `d14098_fun`).

| Suite | Result | Evidence class |
|---|---|---|
| `MediaConfigTest` | PASS | Automated |
| `ChatV2ExtensionsTest` (voice authz, executable block, idempotency) | PASS | Automated |
| `SecureMediaAccessTest` (authz, outsider, logout, event membership, upload rejects, missing/deleted) | PASS | Automated |

---

## 10. Remaining risks

1. Local duplicate/orphan files still occupy disk (including 3 sensitive identity orphans).  
2. `media_path` in API payloads leaks object keys to authorized clients (not a public R2 URL).  
3. PHPUnit DB env without `force="true"` + any future CLI factory/tinker misuse (historical factory-user incident).  
4. Public media remains publicly fetchable by URL (by design for covers/profiles).  
5. Malware scanning / quarantine still not wired.  
6. Event gallery / community media not productized — no gap in R2 wiring, only product scope.

---

## 11. Recommended corrective actions (owner-gated; not executed)

1. **E4 cleanup (optional):** delete or archive classified local duplicates/orphans after backup — especially `media_private/identity/6/*`.  
2. Harden `phpunit.xml` with `force="true"` on `DB_*`.  
3. Consider omitting `media_path` from public API resources if Flutter unused.  
4. Guard `User::factory()` / forbid factory creates when `app()->isProduction()`.  
5. Optional malware scan pipeline for uploads.

---

## 12. Evidence legend

| Label | Meaning |
|---|---|
| Automated tests | PHPUnit on sqlite `:memory:` |
| Production inspection | Read-only DB/config/code/HTTP probes |
| Previously accepted by owner | Stage E2 lightbox gate |
| Not yet verified | Live upload; E4 deletion; malware scanner |

---

## Final verdict

### CONDITIONAL

**Pass conditions met:** R2 for new uploads; post-E2 DB references for event covers + logo on R2; private media gateway authz; no private R2 URL exposure; owner-accepted web lightbox; automated private-media tests green.

**Conditions remaining:** local leftover cleanup (separate approval); low-severity API `media_path` + PHPUnit force hardening; no malware scanning.

Owner review required before any deletion or further production changes.
