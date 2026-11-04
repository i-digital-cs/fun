# Funnity Secure Media — Stage E4.2 Centralized Factory Guard

**Date:** 2026-11-01 (UTC)  
**Project:** `/home/d14098/funnity.com`  
**Context:** E4.1 remaining gap — `CategoryFactory` / `CityFactory` (and any future factories) were still writable via production Tinker  
**Mode:** Narrow source changes + isolated sqlite tests only — **no deploy**, no production writes

---

## Final verdict

### PASS

Centralized factory persistence protection is implemented for **all** project Eloquent factories via a supported Laravel extension point (`Factory::store()` override on a project base class). Protection keys off the **target database / driver** (plus production-env fail-closed for non-sqlite), not exclusively `APP_ENV`. PHPUnit sqlite factories, `make()`, registration, and Google OAuth remain unaffected. Production `d14098_fun` counters unchanged across the test run.

---

## 1. Implementation approach

### Laravel extension point

Laravel’s `Illuminate\Database\Eloquent\Factories\Factory` persists through a single protected method:

- `create()` / `createMany()` / `createQuietly()` → **`store(Collection $results)`** → `$model->save()`

There is **no** first-party global “before factory create” callback that covers every factory without subclassing. Supported, non-invasive options evaluated:

| Option | Chosen? | Notes |
|---|---|---|
| Project base factory overriding `store()` | **Yes** | Covers all factories that extend it; no framework patches; no model events |
| Per-factory `create()` overrides | No | Easy to miss new factories (E4.1 gap) |
| Global Eloquent `creating` / `saving` hooks | **No** | Forbidden by requirements; would risk legitimate app writes |
| Composer classmap alias of Illuminate Factory | No | Framework-level / fragile |

### Guard rules (`FactoryPersistenceGuard`)

1. Optional escape hatch: `ALLOW_FACTORY_PERSISTENCE=true` (must not be set in production).
2. Resolve connection config; **fail closed** if connection/driver/database cannot be established.
3. **Allow** `sqlite` (including `:memory:`) — preserves PHPUnit isolation even if `APP_ENV` is mis-set.
4. **Block** when database name is in the protected list (`d14098_fun` hardcoded + optional `FACTORY_PROTECTED_DATABASES` CSV).
5. **Block** when `app()->environment('production')` and driver is non-sqlite (covers renamed production DBs not yet listed).
6. Does **not** run on `make()` (no `store()` call).
7. Does **not** wrap normal `new Model` / `Model::query()->create()` application paths.

### Wiring

All factories extend `Database\Factories\AppFactory`, which calls the guard inside `store()` before `parent::store()`.

---

## 2. Files changed

| Path | Role |
|---|---|
| `app/Support/Factories/FactoryPersistenceGuard.php` | **New** — centralized allow/deny logic |
| `database/factories/AppFactory.php` | **New** — base factory; overrides `store()` |
| `database/factories/UserFactory.php` | Extends `AppFactory`; removed E4-only `create()` override |
| `database/factories/CategoryFactory.php` | Extends `AppFactory` |
| `database/factories/CityFactory.php` | Extends `AppFactory` |
| `database/factories/EventFactory.php` | Extends `AppFactory` |
| `tests/Unit/FactoryPersistenceGuardTest.php` | **New** — coverage matrix |
| `tests/Unit/UserFactoryProductionGuardTest.php` | Updated for DB-target guard semantics |
| `docs/media/FUNNITY_SECURE_MEDIA_STAGE_E4_2_FACTORY_GUARD.md` | This deliverable |

**Not changed:** Flutter, R2, production DB, media stream services, unrelated working-tree files.

---

## 3. Test evidence

**Command:**

```bash
php artisan test --filter='FactoryPersistenceGuardTest|UserFactoryProductionGuardTest'
```

**Result:** **13 passed** (32 assertions)

| Case | Result |
|---|---|
| User / Category / City `create()` on sqlite `:memory:` | PASS |
| Factory `make()` non-persistent | PASS |
| Guard blocks `d14098_fun` by name | PASS |
| User/Category/City `->connection('mysql')->create()` blocked before INSERT | PASS |
| Production env + renamed non-sqlite DB blocked | PASS |
| Production env + sqlite still allowed | PASS |
| Missing DB name / missing connection fail closed | PASS |
| RegisterUserService / GoogleAuthService do not call `::factory(` | PASS |

**Production unchanged (read-only counters):**

| Metric | Before | After |
|---|---|---|
| `users.max(id)` | 1548 | 1548 |
| `categories` count | 11 | 11 |
| `cities` count | 16 | 16 |

**Live production config probe (no writes):**  
`FactoryPersistenceGuard::assertPersistenceAllowed('mysql')` → blocked against `d14098_fun`.  
`assertPersistenceAllowed('sqlite')` → allowed.

**No real factory writes against production were performed.**

Also re-checked: `SecureMediaAccessTest|FactoryPersistenceGuardTest|UserFactoryProductionGuardTest` still green with sqlite factories.

---

## 4. Compatibility assessment

| Surface | Impact |
|---|---|
| Web/API registration | Unaffected (`new User([...])`) |
| Google OAuth | Unaffected (`new User([...])`) |
| PHPUnit factories | Unaffected (sqlite allowed) |
| Tinker `User::factory()->create()` on prod | Blocked |
| Tinker `Category::factory()->create()` / `City::factory()->create()` on prod | Blocked |
| Tinker `Event::factory()->create()` on prod | Blocked (nested factories also go through `store()`) |
| `Model::factory()->make()` | Allowed |
| Legitimate Eloquent saves | Unaffected (no model event hooks) |
| Future factories | **Must extend `AppFactory`** — document as convention |

**Residual process gap:** a brand-new factory that extends Illuminate’s `Factory` directly (skipping `AppFactory`) would bypass the guard. Mitigation: code review + this base-class convention (only four factories exist today, all migrated).

---

## 5. Deployment plan

Ship with the Stage E4 selective release (still owner-gated; **not deployed in E4.2**):

**Runtime required:**

1. `app/Support/Factories/FactoryPersistenceGuard.php`
2. `database/factories/AppFactory.php`
3. `database/factories/UserFactory.php`
4. `database/factories/CategoryFactory.php`
5. `database/factories/CityFactory.php`
6. `database/factories/EventFactory.php`

**Optional/CI:**

7. `tests/Unit/FactoryPersistenceGuardTest.php`
8. `tests/Unit/UserFactoryProductionGuardTest.php`
9. This doc (+ prior E4 / E4.1 docs as needed)

Also include prior E4 runtime files if not yet deployed (`PrivateMediaAccessService`, `phpunit.xml`).

**Do not** deploy unrelated dirty working-tree changes.  
**No** migrations, R2, or `.env` changes required.  
Optional env knobs (default unset): `FACTORY_PROTECTED_DATABASES`, `ALLOW_FACTORY_PERSISTENCE` (keep false/absent in production).

---

## 6. Rollback plan

1. Revert the six runtime factory/guard files listed above (or restore prior `UserFactory` create-only guard if a partial rollback is preferred — **not recommended**, restores Category/City gap).
2. No DB rollback.
3. Re-cache config only if a future env knob was added and cached (not required for current code).

---

## 7. Remaining notes

- Escape hatch `ALLOW_FACTORY_PERSISTENCE` exists for emergency tooling; must stay unset in production.
- Identity/media E4 hardening is unchanged by this stage; deploy together when owner approves selective E4+E4.2 file set.
- Still deferred from earlier stages: API `media_path` trim (S5), local orphan cleanup (S7), malware scanning.

---

**STOP — Owner review required. Do not deploy automatically.**
