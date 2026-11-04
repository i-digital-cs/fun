# Funnity Secure Media — Stage E1 Verification Report

**Date:** 2026-11-01  
**Environment:** production project `/home/d14098/funnity.com`, DB `d14098_fun`  
**Mode:** verification only — **no migration, no deletion, no DB writes, no Flutter changes**

---

## Executive verdict

| Area | Result |
|---|---|
| R2 configured for new uploads | **PASS** (`MEDIA_DISK=r2`, `MEDIA_PRIVATE_DISK=r2_private`) |
| Private media gateway auth | **PASS** (live probes + isolated tests) |
| Private R2 URL exposure in API | **PASS** (`media_url` is app gateway on `funnity.com`) |
| Web lightbox assets deployed | **PASS** (JS/CSS live on CDN path `/build/assets/…`) |
| Interactive lightbox click-test | **BLOCKED** — no authenticated browser session; owner must click-test |
| Production media migration | **NOT RUN** (awaiting owner approval) |
| Production deletion | **NOT RUN** |

**Stop here for Stage E. Request owner approval before any copy/rewrite/delete.**

---

## 1. Live lightbox verification

### Deployed assets (production HTTPS)

| Check | Result |
|---|---|
| `GET /build/assets/media-viewer-DQsITc89.js` | **200** (5449 bytes, deployed) |
| Built CSS contains `.fun-media-viewer*` | **PASS** |
| Built `dm-chat` / `event-chat` include `data-media-viewer` | **PASS** |
| Built chat bundles contain `target="_blank"` for media | **Not found** |

### Interactive UI (DM / event-chat / video / RTL / mobile)

| Check | Result |
|---|---|
| Open DM image in-page (no new tab) | **Not executed** — `/chats` redirected to login (no QA session) |
| Event-chat image lightbox | **Not executed** (same) |
| Event galleries | **N/A** — no separate gallery upload surface |
| Video attachments | **N/A in prod data** — `dm_video=0`, `em_video=0` (code path present) |
| Close / Escape / pause-on-close / RTL / mobile | **Code review PASS**; live click-test pending owner |

**Owner action:** log in → open an existing DM/event image → confirm overlay (not new tab) → Esc/close → AR locale → narrow viewport. Do not create new production messages solely for this test if avoidable; use the 4 existing DM images / 1 event-chat image.

Existing fixture IDs (for owner QA only; do not share broadly): DM image message present; event-chat image on event `577` / message `227`.

---

## 2. Private media authorization results

### Live production probes (anonymous)

| Request | Result |
|---|---|
| `GET /api/dms/media/{id}` + `Accept: application/json` | **401** `{"message":"Unauthenticated."}` |
| `GET /dms/media/{id}` (session) | **302** → `/login` |
| `GET /api/events/{event}/messages/{message}/media` JSON | **401** |
| API `media_url` host | `funnity.com` path `/api/dms/media/{id}` — **no R2 host** |
| Presigned / `temporaryUrl` usage in app | **None found** |
| `R2_PRIVATE_URL` | **empty** (private bucket not given a public CDN URL) |
| `r2_private` visibility | `private` |

### Isolated automated tests (fake storage; 23 passed)

`SecureMediaAccessTest` + `ChatV2ExtensionsTest` + `MediaConfigTest`:

- Authorized participant can view DM image + `private`/`no-store` cache semantics  
- Copied URL → unrelated user **403**  
- Logged-out API **401**  
- Web session gateway participant OK / outsider **403**  
- Event-chat membership gate  
- SVG/HTML / oversized / traversal-safe keys / missing object / cleared path  

### Deactivated users

Media routes sit inside `account.active` (web + API). Middleware `EnsureAccountIsActive` returns **403 JSON** or redirect to `account.deactivated`. Deactivated users cannot reach private media gateways while that middleware remains on those route groups.

### Identity documents

- DB: **18** documents on `r2_private`  
- Streaming: `PrivateMediaAccessService` + policies (`viewOwnIdentityDocument` / admin `viewIdentityDocuments`)  
- Responses: `Cache-Control: private, no-store`  
- Local leftover identity files under `media_private` for user `6` are **not** in DB (orphans) — see inventory; **not deleted**

---

## 3. R2 upload-path verification

Runtime resolution (production config):

| Upload category | Disk helper | Production disk |
|---|---|---|
| Profile photos | `MediaDisk::public()` | **r2** → `profiles/{userId}/{uuid}` |
| Event covers | `PublicMediaUploader` / `MediaDisk::public()` | **r2** → `events/{id}/cover_*.webp` |
| Event galleries | Not implemented as separate uploads | N/A |
| DM / event-chat image/voice/video/file | `ChatMediaService` / `MediaDisk::private()` | **r2_private** → `chat/direct|events/...` |
| Identity documents | `PrivateMediaUploader` / `MediaDisk::private()` | **r2_private** → `identity/{userId}/...` |
| Site branding (admin) | `MediaDisk::public()` for new uploads; **current logo still on local `media`** | see migration list |

No production test uploads were created.

---

## 4. Remaining local files by category

`php artisan media:inventory` + path cross-reference (read-only):

| Category | Count | Notes |
|---|---|---|
| Local `media` disk files | **67** | |
| **Referenced by production (must migrate to keep serving if disk stays local)** | **2** | Event **501** cover on disk `media`; site logo `branding/funity-mark.svg` (`site_settings.logo_disk=media`) |
| **Local copies of paths already referenced as R2** | **~49** | Mostly `events/manual/*` — DB points at `image_disk=r2` with same key; local file is leftover duplicate |
| **Orphan / unknown / alternate takes** | **~17** | Old avatars, `events/5/...`, `(2)/(3)/(4)` manual variants, unused branding variants |
| Local `media_private` | **3** | `identity/6/*` — **not in DB** (orphans after R2 identity migration) |
| Already on R2 (DB) | Event covers **53** `r2`; identity **18** `r2_private`; chat media paths under `chat/…` on private disk | |
| Profile photos | 1 R2 public URL, 5 Google; **0** local `/storage/media/` URLs | |

---

## 5. Security findings (non-destructive)

| Severity | Finding | Status |
|---|---|---|
| — | Private chat/identity served via authenticated gateways | OK |
| — | No app-issued R2 presigned URLs for private media | OK |
| — | Private bucket has no public URL env | OK |
| Low | API resources include `media_path` (internal object key) alongside gateway `media_url` | Authorized clients only; not a public R2 URL. Consider omitting from future payloads if Flutter unused |
| Low | Public event covers / profile photos remain public CDN by design | Expected |
| Info | One event + site logo still on local public disk | Migration candidates |
| Info | Local orphans/duplicates remain on disk | Safe to keep until Stage E2+ deletion approval |
| Info | Interactive lightbox not owner-click-verified yet | Process gap, not a storage vuln |

No credential values are included in this report.

---

## 6. Exact files requiring migration (Stage E2 — not executed)

**Must migrate before turning off local `media` disk for production reads:**

1. `events/501/cover_7b74a7dd-c885-4577-b46b-1e213e3afeeb.png` — DB `events.id=501`, `image_disk=media`  
2. `branding/funity-mark.svg` — `site_settings.logo_disk=media`, `logo_path=branding/funity-mark.svg`

**Do not migrate blindly (already R2-referenced or unreferenced):**

- ~49 local `events/manual/*` duplicates of R2 keys  
- Orphan identity locals under `media_private/identity/6/`  
- Orphan avatars / alternate manual PNGs / `events/5/...`

---

## 7. Migration plan with rollback (pending approval)

### Plan (E2 — owner gate)

1. Verified backup of DB + local `storage/app/public/media` + `storage/app/private/media`.  
2. Copy **only** the two must-migrate objects to R2 public bucket with checksum verification (do not trust ETag alone as MD5).  
3. Transactionally update `events.image_disk` / `site_settings.logo_disk` to `r2` **after** verified GET from R2.  
4. Re-run `media:inventory`; confirm event 501 + logo resolve via R2.  
5. Keep local originals until a **separate** deletion approval (E3).

### Rollback

1. Revert DB disk columns to `media`.  
2. Confirm local files still present (do not delete in E2).  
3. Clear any app caches if logo/event image URLs are cached.

### Explicitly out of scope until approved

- Bulk copy of orphans  
- Deletion of local duplicates  
- Any Flutter changes  

---

## 8. Tests performed

| Test | Result |
|---|---|
| `php artisan media:inventory` / `--json` | PASS (read-only) |
| `php artisan media:inventory --execute` | Refused (expected) |
| `php artisan test --filter='SecureMediaAccessTest\|ChatV2ExtensionsTest\|MediaConfigTest'` | **23 passed** |
| Live anonymous media gateway probes | 401 / 302 as expected |
| Production asset presence for media-viewer | 200 |
| Production DB write / object delete / bulk migrate | **Not performed** |

---

## Owner approval request

Please approve **Stage E2** only if desired:

- [ ] Interactive lightbox click-test completed on production (owner)  
- [ ] Migrate event `501` cover → R2 + update `image_disk`  
- [ ] Migrate site logo → R2 + update `logo_disk`  
- [ ] Keep all local originals until a later E3 deletion ticket  

**No further migration or deletion will proceed without explicit approval.**
