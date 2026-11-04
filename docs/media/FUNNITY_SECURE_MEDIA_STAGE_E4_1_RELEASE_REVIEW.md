# Funnity Secure Media — Stage E4.1 Production Release Verification

**Date:** 2026-11-01 (UTC)  
**Project:** `/home/d14098/funnity.com`  
**Source of truth:** `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_HARDENING.md`  
**Mode:** READ-ONLY verification — no deploy, no production DB writes, no file deletion, no R2/Flutter changes

---

## Verdict

### CONDITIONAL

E4 runtime changes are **safe to deploy** if released as a **narrow, selective file set**. Conditions:

1. Do **not** deploy the full dirty working tree (large unrelated WIP).
2. Accept remaining **non-User factory** gap (documented below).
3. Run the post-deploy smoke plan before closing the release.
4. Deferred E3 items (API `media_path`, local orphans, malware scan) remain out of this release.

---

## 1. Factory safety

| Check | Result | Evidence |
|---|---|---|
| Production `User::factory()->create()` blocked | **PASS** | `UserFactory::create()` throws when `app()->environment('production')`. Host runtime is `production`. Unit test flips env and expects `RuntimeException`. |
| `createQuietly` / `createMany` | **PASS** (via parent path) | Framework `createQuietly` / `createMany` call `$this->create()` — same guard. |
| Registration | **PASS** | `RegisterUserService` uses `new User([...])` + save — not factories. |
| Google OAuth | **PASS** | `GoogleAuthService::findOrCreateFromGoogle` uses `new User([...])` — not factories. |
| Isolated PHPUnit factories | **PASS** | Factory create succeeds under `APP_ENV=testing`; suite green. |
| Other factories | **GAP (documented)** | Only `UserFactory` is guarded. Repo also has `EventFactory`, `CategoryFactory`, `CityFactory`. `Event::factory()->create()` nests `User::factory()` and therefore **fails closed** in production. `Category::factory()` / `City::factory()` alone can still persist if someone runs them in production tinker. Not observed in E4 scope; recommend a later shared `ProductionFactoryGuard` if desired. |

**No production factory writes attempted in this review.**

---

## 2. Private media

| Check | Result | Notes |
|---|---|---|
| Authorized participant access | **PASS** | `SecureMediaAccessTest` + chat voice authz tests |
| Anonymous denial | **PASS** | API media **401**; identity JSON anonymous **401**; web session gateways redirect/login as before |
| Unauthorized / outsider | **PASS** | **403** on copied gateway URLs |
| Revoked access | **PASS** | Event membership delete → subsequent media GET **403** |
| Security headers | **PASS** | Chat + identity: `Cache-Control` contains `private` + `no-store` (+ `no-transform` via config); `X-Content-Type-Options: nosniff`. Identity also blocks HTML/SVG/JS MIME → **404** |
| Public SVG branding | **PASS / unaffected** | Site logo is public R2 URL (`logo_disk=r2`, `branding/funity-mark.svg`) with asset fallback `public/branding/funity-mark.svg`. Identity MIME SVG block does **not** apply to public branding. |
| Flutter compatibility | **PASS** | No Flutter edits; API resources still expose `media_path` + gateway `media_url`; upload categories unchanged |

Identity MIME hardening only affects identity gateway responses, not chat image/video/voice streams (already restricted at upload) and not public branding.

---

## 3. Test isolation

| Item | Value |
|---|---|
| Host / production default | `mysql` / `d14098_fun` (`app_env=production`) |
| PHPUnit (`phpunit.xml`) | `DB_CONNECTION=sqlite` **force=true**, `DB_DATABASE=:memory:` **force=true**, `APP_ENV=testing` **force=true** |
| Isolation proof | Read-only `users.max(id)` on MySQL **before and after** E4 media/factory test run: **1548 → 1548** on `d14098_fun` |
| Re-run command used | `php artisan test --filter='SecureMediaAccessTest\|UserFactoryProductionGuardTest\|MediaUploadValidatorTest'` → **17 passed** |

**Automated tests did not target `d14098_fun`.**

Full E4 media suite (from Stage E4 report): **40 passed** (202 assertions) including Identity + ChatV2 + MediaConfig.

---

## 4. Release safety

### Exact files approved for deployment (E4 only)

**Runtime (required for production behavior):**

| File | Why |
|---|---|
| `app/Services/Media/PrivateMediaAccessService.php` | Identity stream MIME fail-closed + cache/`nosniff` headers |
| `database/factories/UserFactory.php` | Production factory persistence guard |

**Non-runtime (recommended to ship with the same commit for CI/parity; optional for app servers):**

| File | Why |
|---|---|
| `phpunit.xml` | Forced sqlite/testing isolation |
| `tests/Feature/SecureMediaAccessTest.php` | Authz / revoke / upload / public cover regressions |
| `tests/Feature/IdentityDocumentsTest.php` | Header + anonymous assertions |
| `tests/Unit/MediaUploadValidatorTest.php` | Upload policy unit coverage |
| `tests/Unit/UserFactoryProductionGuardTest.php` | Factory guard coverage |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_HARDENING.md` | Stage record |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_1_RELEASE_REVIEW.md` | This review |

### Existing uncommitted work (do not overwrite / do not blindly deploy)

Working tree on `main` contains **many unrelated modified and untracked files** (auth/OTP, living map, marketing, Flutter l10n, chat UI, etc.). E4.1 does **not** approve those for this release.

**Deploy instruction:** ship only the approved E4 file list above (cherry-pick / selective commit / selective rsync). Leave all other dirty paths untouched.

### Deployment dependencies

| Dependency | Status |
|---|---|
| DB migrations | **None** for E4 |
| `.env` / R2 credentials / bucket policies | **No change** |
| `config/media.php` | Already live in production (`MEDIA_DISK=r2`, `MEDIA_PRIVATE_DISK=r2_private`, `private_cache_control` present). E4 service reads existing config. |
| Composer / npm | **Not required** for E4 runtime files |
| `php artisan config:cache` | Safe/recommended after deploy if production uses config cache; new code reads `config('media.private_cache_control')` which is already in cached config if `media.php` was previously cached |
| Queue / horizon restart | **Not required** |
| Flutter publish | **Not required / not in scope** |

### Cache implications

- Private identity/chat responses use **`private, no-store`** (+ `no-transform`) — browsers/proxies must not store private attachments.
- No change to public CDN caching for event covers / logo.
- Logo SVG remains publicly cacheable by design.
- No `Cache::forget` required for E4 (factory + stream headers are request-path only). Site logo cache key `site.logo_url` is unrelated and untouched.

### Production configuration compatibility

| Setting (read-only) | Observed |
|---|---|
| `APP_ENV` | `production` |
| DB | `mysql` / `d14098_fun` |
| `MEDIA_DISK` | `r2` |
| `MEDIA_PRIVATE_DISK` | `r2_private` |
| Logo | `r2` / `branding/funity-mark.svg` |

Compatible with E4 code paths.

### Rollback strategy

1. Restore previous versions of:
   - `app/Services/Media/PrivateMediaAccessService.php`
   - `database/factories/UserFactory.php`
2. Optionally restore `phpunit.xml` and E4 test/doc files.
3. Re-cache config if used (`php artisan config:cache`).
4. No DB rollback / no R2 rollback required.

Impact of rollback: lose identity MIME fail-closed + `nosniff`/`no-transform` alignment; lose production User factory guard. Private chat authz remains as pre-E4 (already gateway-gated).

---

## 5. Remaining risks

1. **Selective deploy risk** — shipping the whole dirty tree would release unrelated WIP.
2. **Non-User factories** still writable in production tinker (`Category`, `City`).
3. **S5** — `media_path` still in API JSON (Low; intentional deferral).
4. **S7** — local orphan files remain (cleanup still owner-gated).
5. **No malware scanner**.
6. **Post-deploy smoke not yet executed** (this stage is pre-deploy review only).

---

## 6. Production smoke-test plan (after owner-approved deploy)

Read-only / non-destructive checks only. Do **not** send OTP, create factory users, or upload to production unless separately authorized.

1. **Public branding SVG:** open logo URL (R2 public) and/or `https://funnity.com/branding/funity-mark.svg` — expect 200 `image/svg+xml`.
2. **Anonymous private media:** `GET` a known DM/event media gateway path without session/token — expect **401** (API) or login redirect (web).
3. **Authorized private media:** as a real participant session/token, open own chat image/voice — expect **200**, `Cache-Control` includes `private` and `no-store`, `X-Content-Type-Options: nosniff`.
4. **Outsider:** same media URL as a different logged-in user — expect **403**.
5. **Identity (owner):** owner views own identity document stream — **200** + same private cache/`nosniff` headers; outsider **403**.
6. **Registration:** load web register page / API validation-only probe — confirm form/endpoints respond (do not complete OTP unless authorized).
7. **Google OAuth:** hit redirect route only — expect Google redirect (do not complete account linking in this smoke unless authorized).
8. **Factory guard (optional, safe):** in production `php artisan tinker`, evaluate that `User::factory()->create()` throws — **do not** attempt other persistence; abort immediately on throw.

---

## 7. Final recommendation

**Approve selective production deploy of the E4 file list in §4.**  
Do not deploy unrelated working-tree changes.  
After deploy, execute §6 smoke plan, then close E4.1.

**STOP — Owner approval required before any deployment.**
