# Funnity Secure Media — Stage E4 Hardening

**Date:** 2026-11-01 (UTC)  
**Project:** `/home/d14098/funnity.com`  
**Source of truth:** `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E3_FINAL_AUDIT.md`  
**Mode:** Narrow application-source fixes + isolated sqlite tests + documentation only  
**Deploy:** **Not performed** — stopped for owner review

---

## 1. Findings addressed

E3 residual findings classified and dispositioned:

| ID | Severity | Component | Actual risk | Correction in E4 | Regression impact |
|---|---|---|---|---|---|
| S5 | Low | API chat resources expose internal `media_path` with gateway `media_url` | Authorized clients learn private object keys (not public R2 URLs). Key alone is not enough without gateway authz. | **Deferred** — removing `media_path` would change Flutter/API contract; requires separate approval | None (unchanged) |
| S6 | Low | `phpunit.xml` `DB_*` lacked `force="true"` | Host `.env` (production MySQL) could override PHPUnit isolation if env loading order allows | Forced `APP_ENV`, `DB_*`, cache/mail/queue/session, media disks | Test-only; production runtime unchanged |
| S7 | Informational | 67 local public + 3 private orphan files (incl. sensitive `identity/6/*`) | Disk leftovers after R2 cutover; identity orphans are sensitive if local disk were ever exposed | **Deferred** — deletion/archive needs separate owner authorization | None (files untouched) |
| S8 | Informational | Prior factory users 1634/1635 incident class | Accidental `User::factory()->create()` via tinker on production pollutes `d14098_fun` | `UserFactory::create()` throws in `production` environment | Tests/non-prod unchanged; prod tinker factory create fails closed |
| — | Medium (defense-in-depth) | Identity document stream headers / active MIME | HTML/SVG/JS MIME could be served `inline` if a bad row existed; Cache-Control was `private, no-store` without `no-transform` / `nosniff` | Block active content MIME (404); use `media.private_cache_control`; add `X-Content-Type-Options: nosniff` | Authorized JPEG/PNG/WebP/PDF streams unchanged |
| — | Medium (regression coverage) | Event-chat media after membership revoke | Authz already re-checks `chat` policy; coverage gap for revoke-after-copy URL | Extended `SecureMediaAccessTest` to delete membership then assert **403** | None — asserts existing behavior |
| Upload gaps (E3 matrix) | Low → covered | `MediaUploadValidator` MIME/ext/size/dangerous types | Residual risk if category policy bypassed | Confirmed existing validator + added unit tests (SVG, exe, oversized profile) | Flutter uploads that already pass policy unchanged |

**Not Critical / High private-media exposures** were present in E3; none introduced or left unmitigated for gateway authz.

---

## 2. Exact source changes

| Path | Change |
|---|---|
| `phpunit.xml` | `force="true"` on `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `DB_URL=`, `CACHE_STORE`, `MAIL_MAILER`, `QUEUE_CONNECTION`, `SESSION_DRIVER` (media disks already forced) |
| `database/factories/UserFactory.php` | Override `create()` — throw `RuntimeException` when `app()->environment('production')` |
| `app/Services/Media/PrivateMediaAccessService.php` | Reject HTML/SVG/JS MIME; Cache-Control from `config('media.private_cache_control')`; `X-Content-Type-Options: nosniff`; keep `Content-Disposition: inline` |
| `tests/Feature/SecureMediaAccessTest.php` | Revoked event membership → 403; public event cover URL regression; existing authz/upload/missing coverage retained |
| `tests/Feature/IdentityDocumentsTest.php` | Owner stream asserts `private`+`no-store`+`nosniff`; anonymous JSON → 401 |
| `tests/Unit/MediaUploadValidatorTest.php` | **New** — accept chat image; reject SVG / exe / oversized profile |
| `tests/Unit/UserFactoryProductionGuardTest.php` | **New** — allow outside production; forbid in production env |

**Explicitly not changed:** Flutter source, API JSON shapes (`media_path` retained), R2 credentials/bucket policies, production DB rows, local/R2 object deletion, route contracts, deploy/restart.

---

## 3. Security rationale

1. **Private chat / identity remain gateway-only.** Stream controllers still authenticate, authorize (participant / `chat` policy / owner|admin), then stream from the private disk. No private R2 public URL and no app-issued presigned URLs.
2. **Revoked access is live-checked.** Event membership is re-evaluated on each media GET; deleting `event_members` immediately yields 403 for a previously working gateway URL.
3. **Private responses are non-cacheable at the browser/proxy layer.** Chat streams already used `private, no-store` (+ `no-transform`); identity now matches `media.private_cache_control` and adds `nosniff`.
4. **Active content is not delivered through the identity gateway** even if a corrupt MIME were stored — fail closed with 404 rather than `inline` HTML/SVG/JS.
5. **Upload policy stays server-side.** Category MIME allow-lists, blocked extensions/MIMEs, size caps, and path-traversal filename rejection remain in `MediaUploadValidator`; tests lock the dangerous cases.
6. **Test isolation and factory hygiene** reduce the chance of repeating the production factory-user incident and of PHPUnit accidentally targeting MySQL `d14098_fun`.
7. **`media_path` omission deferred** so Flutter clients that may rely on the field are not broken without an approved contract change.

---

## 4. Automated test results

**Isolation:** PHPUnit sqlite `:memory:` with forced env (no production DB / no tinker factories).

**Command:**

```bash
php artisan test --filter='SecureMediaAccessTest|ChatV2ExtensionsTest|MediaConfigTest|MediaUploadValidatorTest|UserFactoryProductionGuardTest|IdentityDocumentsTest'
```

**Result:** **40 passed** (202 assertions)

| Suite | Focus | Result |
|---|---|---|
| `MediaConfigTest` | Public/private disk helpers; R2 private ≠ public | PASS |
| `MediaUploadValidatorTest` | MIME/ext/size/dangerous types | PASS |
| `UserFactoryProductionGuardTest` | Production factory ban | PASS |
| `ChatV2ExtensionsTest` | Voice authz, executable block, Flutter-oriented chat APIs | PASS |
| `IdentityDocumentsTest` | Upload disk, owner/admin stream, outsider/anonymous denial, headers | PASS |
| `SecureMediaAccessTest` | Participant OK; anonymous/outsider deny; revoked membership; upload rejects; missing/cleared path; public cover URL | PASS |

---

## 5. Compatibility assessment

| Consumer | Assessment |
|---|---|
| Web session media gateways | Compatible — same routes/headers (stricter identity MIME only for disallowed types) |
| Flutter / Sanctum media APIs | Compatible — gateway `media_url` paths and upload validation unchanged; `media_path` still present |
| Public event covers / branding | Compatible — still public-disk URLs; regression test asserts non-gateway delivery |
| Identity owner/admin viewers | Compatible for allowed MIME (images/PDF) |
| Google Play / existing clients | No API contract changes |

---

## 6. Remaining risks

1. **S5:** `media_path` still returned to authorized API clients (Low).
2. **S7:** Local orphan files remain on disk, including 3 sensitive identity orphans under `media_private/identity/6/*` — cleanup still owner-gated.
3. **No malware / AV quarantine pipeline** for uploads (operational gap; not an authz bypass).
4. **Public media** (covers, profiles, logo) remains publicly fetchable by URL — by design.
5. **Factory guard** covers `User::factory()->create()`; other model factories are not globally blocked (narrow scope matching the documented incident).
6. **Deploy pending** — hardening is in source only until approved rollout.

---

## 7. Deployment requirements

Owner approval required before any of the following:

1. **Deploy application code** containing `PrivateMediaAccessService`, `UserFactory`, and `phpunit.xml` changes (phpunit is deploy-irrelevant for runtime; app changes need a normal release).
2. **Optional follow-up (separate ticket):** remove `media_path` from public API resources after Flutter confirmation.
3. **Optional follow-up (separate ticket):** backup then delete/quarantine classified local orphans (`storage/app/migrations/e3_local_inventory_by_class.txt`).
4. **Do not** change R2 credentials, bucket policies, or production media rows as part of this stage.

No migration is required for the E4 code changes in this report.

---

## 8. Final verdict

### CONDITIONAL

**Pass criteria met for E4 scope:** E3 residual Low/Info items that were authorized for code-level fix are hardened (PHPUnit force, factory production guard, identity stream headers/MIME); private chat authz regressions including revoked membership are green on isolated sqlite; upload validation covered; no API contract break; no production DB/R2/file mutation; no deploy performed.

**Conditions remaining:** owner deploy approval; optional `media_path` API trim; optional local orphan cleanup; malware scanning still absent.

---

**STOP — Owner review required. Do not deploy automatically.**
