# Funnity — E4 Security Release: Final Readiness

**Date:** 2026-11-01 (UTC)  
**Project:** `/home/d14098/funnity.com`  
**Production DB:** `d14098_fun`  
**Sources:** E4 Hardening · E4.1 Release Review · E4.2 Factory Guard  
**Mode:** Readiness only — **no deployment executed**

---

## Verdict

### PASS

The selective E4 security package is **ready for owner-approved production activation**.

- All four Eloquent factories extend `AppFactory` and persist only through the guarded `store()` path.
- Identity private-media header/MIME hardening is complete and tested.
- PHPUnit remains on forced sqlite `:memory:`; production `users.max(id)` unchanged (**1548 → 1548**) across the readiness suite.
- No Flutter, R2, migration, or API contract changes are required for this release.

**Activation rule:** deploy/activate **only** the manifest in §2. Do **not** promote the rest of the dirty working tree.

---

## 1. Factory guard coverage (verified)

| Check | Result |
|---|---|
| `UserFactory` extends `AppFactory` | PASS |
| `CategoryFactory` extends `AppFactory` | PASS |
| `CityFactory` extends `AppFactory` | PASS |
| `EventFactory` extends `AppFactory` | PASS |
| No other `database/factories/*` exist | PASS (4 models + base) |
| `create()` → `store()` guarded | PASS |
| `createMany()` / `createManyQuietly()` → `create()` → `store()` | PASS (framework path) |
| `createQuietly()` → `create()` → `store()` | PASS |
| Nested relationship factories (e.g. Event→User/Category/City) | PASS — each factory’s own `store()` is guarded |
| `afterCreating` callbacks | PASS — run **after** successful `store()`; cannot bypass the guard. Event’s `members()->create([...])` is a normal Eloquent write (intentionally not blocked) |
| `make()` non-persistent | PASS — does not call `store()` |
| PHPUnit sqlite factories | PASS |
| Registration / Google OAuth | PASS — `new User([...])`, not factories |
| Model event hooks | Not used (by design) |

**Bypass residual (process only):** a future factory that extends Illuminate `Factory` directly would skip the guard. Convention: always extend `AppFactory`.

**No production factory writes attempted.**

---

## 2. Exact deployment file manifest

### 2.1 Runtime (required)

| # | Path | Status vs `origin/main` | Purpose |
|---|---|---|---|
| 1 | `app/Support/Factories/FactoryPersistenceGuard.php` | **New (untracked)** | DB-target factory persistence guard |
| 2 | `database/factories/AppFactory.php` | **New (untracked)** | Base factory; overrides `store()` |
| 3 | `database/factories/UserFactory.php` | Modified | Extends `AppFactory` |
| 4 | `database/factories/CategoryFactory.php` | Modified | Extends `AppFactory` |
| 5 | `database/factories/CityFactory.php` | Modified | Extends `AppFactory` |
| 6 | `database/factories/EventFactory.php` | Modified | Extends `AppFactory` |
| 7 | `app/Services/Media/PrivateMediaAccessService.php` | Modified | Identity MIME fail-closed + `private, no-store, no-transform` + `nosniff` |

### 2.2 Runtime dependencies (already live — do not redeploy unless missing)

| Path | Why needed |
|---|---|
| `config/media.php` (`private_cache_control`, disks) | Already in production; `PrivateMediaAccessService` reads it |
| `composer.json` PSR-4 (`App\\`, `Database\\Factories\\`) | Already configured — **no composer install required** for these PHP files |
| Media stream controllers / routes | Unchanged in this package; already authorize then stream |

### 2.3 Non-runtime (recommended same commit / keep on host for CI)

| Path | Purpose |
|---|---|
| `phpunit.xml` | Forced `APP_ENV` / `DB_*` sqlite isolation |
| `tests/Unit/FactoryPersistenceGuardTest.php` | Guard coverage |
| `tests/Unit/UserFactoryProductionGuardTest.php` | Continuity |
| `tests/Unit/MediaUploadValidatorTest.php` | Upload policy |
| `tests/Feature/SecureMediaAccessTest.php` | Private media authz |
| `tests/Feature/IdentityDocumentsTest.php` | Identity stream headers (modified) |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_HARDENING.md` | Stage record |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_1_RELEASE_REVIEW.md` | Release review |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_2_FACTORY_GUARD.md` | Factory guard record |
| `docs/media/FUNNITY_E4_SECURITY_RELEASE_READY.md` | This document |

### 2.4 Explicitly excluded

All other dirty/untracked paths (auth/OTP, living map, marketing, Flutter l10n, chat UI, etc.) are **out of scope** for this release.

---

## 3. Production compatibility

| Item | Evidence |
|---|---|
| PHP | 8.2.35 (CLI) — compatible with Laravel 12 |
| Laravel | 12.69.2 — boots cleanly (`env=production`) |
| Autoload | `Database\Factories\AppFactory` and `App\Support\Factories\FactoryPersistenceGuard` resolve via existing Composer PSR-4 |
| Bootstrap | `bootstrap/app.php` + kernel bootstrap OK with current `.env` |
| Media disks | `MEDIA_DISK=r2`, `MEDIA_PRIVATE_DISK=r2_private` |
| Media routes | Web/API DM + event media gateways + identity document show routes present |
| Authorization | Unchanged participant / `chat` policy / owner-admin gates |
| Google OAuth | Unaffected (no factory usage) |
| Registration | Unaffected (no factory usage) |
| Flutter API contracts | Unchanged (`media_path` retained; gateway `media_url` unchanged) |
| Migrations | **None** |
| `.env` / R2 credentials / bucket policies | **No change required** |

**Host note:** This repository path is the production application root. Manifest files already exist on disk as modified/untracked WIP. Owner activation = approve them as the live set + clear caches/opcache as needed — **not** a bulk sync of the entire dirty tree.

---

## 4. Test results (readiness suite)

```bash
php artisan test --filter='FactoryPersistenceGuardTest|UserFactoryProductionGuardTest|SecureMediaAccessTest|MediaUploadValidatorTest|MediaConfigTest|IdentityDocumentsTest|ChatV2ExtensionsTest'
```

**Result: 51 passed (230 assertions)**  
**Production DB:** `users.max(id)` **1548 → 1548** (no writes)

| Suite | Focus |
|---|---|
| `FactoryPersistenceGuardTest` | All-factory guard, make(), fail-closed, auth services |
| `UserFactoryProductionGuardTest` | User path continuity |
| `SecureMediaAccessTest` | Authz, revoke, upload rejects, public covers |
| `IdentityDocumentsTest` | Identity stream auth + headers |
| `ChatV2ExtensionsTest` | Flutter-oriented chat/voice APIs |
| `MediaUploadValidatorTest` / `MediaConfigTest` | Upload + disk config |

---

## 5. Risks

| Risk | Severity | Mitigation |
|---|---|---|
| Accidental deploy of unrelated dirty tree | High (process) | Use §2 manifest only |
| Future factory not extending `AppFactory` | Medium | Code review convention |
| `ALLOW_FACTORY_PERSISTENCE=true` in prod `.env` | High if set | Keep unset |
| Deferred S5 `media_path` in API JSON | Low | Separate approval |
| Deferred S7 local orphans | Info | Separate cleanup ticket |
| Opcache serving pre-change `PrivateMediaAccessService` | Low | Clear caches after activation |
| Escape hatch / renamed DB | Covered | Protected name list + production-env fail-closed for non-sqlite |

---

## 6. Rollback

### 6.1 Files to back up before activation

Create a timestamped backup of the **current git HEAD (pre-E4)** versions and note new files for deletion on rollback:

```bash
cd /home/d14098/funnity.com
STAMP=$(date -u +%Y%m%dT%H%MZ)
BACKUP="storage/app/migrations/e4_security_release_backup_$STAMP"
mkdir -p "$BACKUP"

# Restore points from last committed versions
for f in \
  app/Services/Media/PrivateMediaAccessService.php \
  database/factories/UserFactory.php \
  database/factories/CategoryFactory.php \
  database/factories/CityFactory.php \
  database/factories/EventFactory.php \
  phpunit.xml
do
  mkdir -p "$BACKUP/$(dirname "$f")"
  git show "HEAD:$f" > "$BACKUP/$f"
done

# Record new files that should be removed on full rollback
printf '%s\n' \
  app/Support/Factories/FactoryPersistenceGuard.php \
  database/factories/AppFactory.php \
  > "$BACKUP/NEW_FILES_TO_REMOVE.txt"

echo "Backup at $BACKUP"
```

**Pre-E4 blob SHAs (git HEAD at readiness):**

| File | `git rev-parse HEAD:<path>` |
|---|---|
| `PrivateMediaAccessService.php` | `d9b7ee3af9f347d228aa38f9648cdbcced515e41` |
| `UserFactory.php` | `7eb3f0b833f397407e8d63cc8fb11924eb159047` |
| `CategoryFactory.php` | `3c11f7edcd5b1dcc6bc1ae1d15478bcf0028d503` |
| `CityFactory.php` | `9a6276604fa496d3d4274e50026f6c06bd408b76` |
| `EventFactory.php` | `9fc1d3a76c77b8b8f9bc420e3c31b9068a2876d0` |
| `phpunit.xml` | `e6f90d046212006798b21e42f337efb9bff022b0` |

Repo HEAD at readiness: `3e919763a46b18496ceae2693453b5bec7a34628`

### 6.2 Safe restoration

```bash
cd /home/d14098/funnity.com
BACKUP=storage/app/migrations/e4_security_release_backup_<STAMP>   # use actual stamp

# Restore prior committed content
cp "$BACKUP/app/Services/Media/PrivateMediaAccessService.php" app/Services/Media/PrivateMediaAccessService.php
cp "$BACKUP/database/factories/UserFactory.php" database/factories/UserFactory.php
cp "$BACKUP/database/factories/CategoryFactory.php" database/factories/CategoryFactory.php
cp "$BACKUP/database/factories/CityFactory.php" database/factories/CityFactory.php
cp "$BACKUP/database/factories/EventFactory.php" database/factories/EventFactory.php
cp "$BACKUP/phpunit.xml" phpunit.xml

# Remove E4.2 new files
rm -f app/Support/Factories/FactoryPersistenceGuard.php
rm -f database/factories/AppFactory.php
rmdir app/Support/Factories 2>/dev/null || true

php artisan optimize:clear
```

No DB rollback. No R2 rollback.

### 6.3 Cache implications

- Private identity responses: `Cache-Control: private, no-store, no-transform` (+ `nosniff`) — no CDN store of private docs.
- Public branding/covers: unchanged public caching.
- After activation or rollback: run `php artisan optimize:clear` (and web opcache reset if the host uses it) so PHP picks up file contents.

---

## 7. Deployment commands (owner-executed only)

**Do not run until explicit owner approval.**

### 7.1 Pre-flight

```bash
cd /home/d14098/funnity.com

# Confirm selective paths only (spot-check)
ls -la \
  app/Support/Factories/FactoryPersistenceGuard.php \
  database/factories/AppFactory.php \
  app/Services/Media/PrivateMediaAccessService.php

# Optional: create §6.1 backup first
```

### 7.2 Activate (files already on this host)

Because this tree is the production app root, activation is:

1. Owner approval that the §2.1 files are the intended live set.
2. Cache clear so FPM/CLI load current sources:

```bash
cd /home/d14098/funnity.com
php artisan optimize:clear
# If PHP-FPM opcache is sticky on this host, also restart/reload PHP-FPM per cPanel practice.
```

3. Optional: commit **only** the §2 manifest (runtime + tests/docs) in a dedicated commit — still do **not** add unrelated dirty files.

```bash
# Example selective commit (only after owner asks to commit)
git add \
  app/Support/Factories/FactoryPersistenceGuard.php \
  database/factories/AppFactory.php \
  database/factories/UserFactory.php \
  database/factories/CategoryFactory.php \
  database/factories/CityFactory.php \
  database/factories/EventFactory.php \
  app/Services/Media/PrivateMediaAccessService.php \
  phpunit.xml \
  tests/Unit/FactoryPersistenceGuardTest.php \
  tests/Unit/UserFactoryProductionGuardTest.php \
  tests/Unit/MediaUploadValidatorTest.php \
  tests/Feature/SecureMediaAccessTest.php \
  tests/Feature/IdentityDocumentsTest.php \
  docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_HARDENING.md \
  docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_1_RELEASE_REVIEW.md \
  docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_2_FACTORY_GUARD.md \
  docs/media/FUNNITY_E4_SECURITY_RELEASE_READY.md
# git commit ...  (only when owner requests)
```

**No** `composer install`, **no** migrate, **no** R2 changes, **no** Flutter publish.

### 7.3 Post-deployment smoke tests

1. Public logo SVG / R2 branding URL → 200.
2. Anonymous API private media URL → 401 (or web login redirect).
3. Authorized participant chat media → 200; `Cache-Control` has `private` + `no-store`; `X-Content-Type-Options: nosniff`.
4. Outsider → 403.
5. Identity owner stream → 200 + private headers; outsider → 403.
6. Register page / OAuth redirect load (no OTP / no account linking unless separately authorized).
7. Safe factory probe (optional): in tinker, confirm `User::factory()->create()` / `Category::factory()->create()` throw — **do not** persist.

---

## 8. Final recommendation

**Approve selective activation of the §2.1 runtime manifest** (plus optional §2.3 tests/docs/`phpunit.xml`).

Then: clear caches → run §7.3 smoke → close E4 security release.

Deferred (not blockers): API `media_path` trim, local orphan cleanup, malware scanning.

---

**STOP — Explicit owner deployment approval required before any activation, cache clear for go-live, or commit.**
