# Marketing Phase 1 — Production Deployment Instructions

**Authorization status:** Code implemented in repository.  
**Production deploy:** **NOT performed.** Requires separate owner approval.

---

## 1. Pre-flight

1. Review git diff for Phase 1 files only.  
2. Confirm no advertising credentials in the diff.  
3. Backup MariaDB (`d14098_fun`) before migrating.  
4. Confirm maintenance window if desired (migration is additive/non-destructive).

---

## 2. Deploy code

Owner-approved process (example):

```bash
cd /home/d14098/funnity.com
# pull/merge approved commit once committed
composer install --no-dev --optimize-autoloader   # only if needed
php artisan view:clear
php artisan route:clear
php artisan config:clear
```

Do **not** cache config until marketing env flags are set intentionally.

---

## 3. Environment flags (optional)

Add to production `.env` only if overriding defaults:

```env
MARKETING_ENABLED=true
MARKETING_READ_ONLY=true
MARKETING_EXECUTION_ENABLED=false
MARKETING_AI_ENABLED=false
MARKETING_DEFAULT_CURRENCY=USD
```

Defaults in `config/marketing.php` already match these values.

**Do not** add Meta/TikTok tokens to `.env` in Phase 1.

---

## 4. Migrate + seed (owner approval required)

```bash
cd /home/d14098/funnity.com
/usr/local/bin/php artisan migrate --path=database/migrations/2026_10_31_160000_create_marketing_foundation_tables.php --force
/usr/local/bin/php artisan db:seed --class=Database\\Seeders\\MarketingPlatformSeeder --force
```

Verify:

```bash
/usr/local/bin/php artisan db:table marketing_platforms
```

Expect 2 rows: `meta`, `tiktok`.

---

## 5. Smoke test (after migrate)

1. Login as SuperAdmin → open `/admin/marketing`  
2. Confirm empty-state copy (no invented KPIs)  
3. Open Integrations → both Not Connected, Connect disabled  
4. Create a global budget policy → confirm audit row in `marketing_audit_logs`  
5. Login as Moderator → expect 403 on `/admin/marketing`

---

## 6. What NOT to do in this deploy

- Do not activate cron/queue yet (separate approval — see runbook)  
- Do not connect ad accounts  
- Do not enable `MARKETING_EXECUTION_ENABLED`  
- Do not spend ad budget  
- Do not restart unrelated services unless required by host policy  

---

## 7. Rollback

```bash
/usr/local/bin/php artisan migrate:rollback --path=database/migrations/2026_10_31_160000_create_marketing_foundation_tables.php --force
```

Then remove/disable marketing routes by reverting the deploy commit if needed.

Rollback drops only the five `marketing_*` foundation tables from that migration.

---

## 8. Status matrix

| Item | Status |
|------|--------|
| Implemented in code | **Yes** |
| Tested (SQLite) | **Yes** |
| Deployed to production | **No** |
| Migrations activated on production | **No** |
| Cron activated | **No** |
| Queue worker activated | **No** |
| Ad accounts connected | **No** |
| Ad spend | **$0** |
