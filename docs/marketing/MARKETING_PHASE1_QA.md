# Marketing Phase 1 — QA Report

**Date:** 2026-10-31  
**Environment for automated tests:** PHPUnit with SQLite `:memory:` (`phpunit.xml`)  
**Production DB:** not used for destructive tests

---

## 1. Automated Marketing suite

Command:

```bash
php artisan test --filter=Marketing
```

**Result:** **22 passed** (75 assertions), **0 failed**  
(Includes incidental `HomePageTest` match on “Marketing” filter string.)

Dedicated marketing tests:

| Suite | Result |
|-------|--------|
| `Tests\Unit\Marketing\MarketingProviderCapabilityTest` | PASS (6) |
| `Tests\Feature\Marketing\MarketingAdminAccessTest` | PASS (5) |
| `Tests\Feature\Marketing\MarketingBudgetPolicyTest` | PASS (6) |
| `Tests\Feature\Marketing\MarketingFoundationSchemaTest` | PASS (4) |

---

## 2. Regression subset

Command:

```bash
php artisan test --filter='AdminPanelTest|AdminDashboardV2Test|AuthRegistrationTest'
```

**Result:** **22 passed**, **0 failed**

Covers admin dashboard access, site settings SuperAdmin gate, auth registration/login.

---

## 3. Checks covered by tests

- Guest redirect / member 403 / moderator 403 / SuperAdmin 200  
- Empty states without invented spend metrics  
- Arabic locale string render  
- Budget policy create + audit log  
- Invalid validation rejected  
- Non-global scope requires scope_id  
- Disable policy audited  
- CSRF token present in budget form HTML  
- Migration tables present; campaign tables absent  
- Seeder idempotent  
- Credential encryption at rest + hidden from `toArray()`  
- Unique `(platform_id, external_account_id)`  
- Provider unsupported ops throw  
- Config safe defaults  

---

## 4. Visual / browser QA

| Check | Status |
|-------|--------|
| Desktop screenshot in browser | **Not performed** in this session |
| Mobile viewport | **Not performed** |
| Live Arabic RTL page load against production | **Not performed** (routes not live until migrate/deploy) |
| Layout overflow manual check | Code reviewed for responsive grids; **not browser-verified** |

Do **not** treat visual QA as passed.

After production migration + deploy, owner/agent should open:

- `/admin/marketing` (EN + AR)
- `/admin/marketing/integrations`
- `/admin/marketing/budget-policies`

---

## 5. Safety assertions

| Item | Status |
|------|--------|
| Public homepage code unchanged | Yes (no edits) |
| Flutter unchanged | Yes |
| No Meta/TikTok HTTP clients | Yes |
| No production migrate | Yes |
| No cron activation | Yes |
| No pending jobs processed | Yes |
| No commit/push | Yes |

---

## 6. Unrelated warnings

PHPUnit metadata deprecation warnings from existing `CitySearchAliasCoverageTest` / `DiscoverMapApiTest` — pre-existing, not introduced by Phase 1.
