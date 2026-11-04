# Marketing Phase 1 — Rollback Plan

**Important:** MariaDB DDL is not fully transactional. Do not assume `migrate:rollback` alone restores every operational nuance.

---

## 1. Layers

| Layer | Method | When |
|-------|--------|------|
| A. Feature disable (fastest) | Set `MARKETING_ENABLED=false` in `.env` + `config:clear` if config cached | Soft incident; keep schema |
| B. Code rollback | Revert marketing routes/nav/middleware from working tree / future commit | UI/code defect |
| C. Schema rollback | Scoped migrate rollback **only if** no valuable marketing data must be kept | Early failure window |
| D. Full DB restore | Restore from `storage/backups/pre_marketing_phase1_20261031_150510.sql` | Catastrophic only |

---

## 2. Soft disable (preferred first response)

```bash
cd /home/d14098/funnity.com
# add/update: MARKETING_ENABLED=false
/usr/local/bin/php artisan config:clear
/usr/local/bin/php artisan view:clear
```

Middleware `EnsureMarketingAccess` aborts when module disabled.

Does **not** drop tables or audit history.

---

## 3. Schema rollback (empty / disposable marketing data only)

Only if marketing tables contain no credentials/accounts you need to keep:

```bash
cd /home/d14098/funnity.com
/usr/local/bin/php artisan migrate:rollback --path=database/migrations/2026_10_31_160000_create_marketing_foundation_tables.php --force
```

This drops the five `marketing_*` foundation tables defined in that migration.

**Do not** run after Phase 2+ credentials exist without an explicit data-preservation plan.

---

## 4. Full restore (last resort)

1. Put app in maintenance if needed.  
2. Restore SQL backup from `storage/backups/pre_marketing_phase1_20261031_150510.sql` using a private mysql client (credentials never logged).  
3. Verify users/events counts against `.meta.json`.  
4. Bring app back.  
5. Investigate root cause before re-deploy.

---

## 5. What rollback must never do

- `migrate:fresh` / `migrate:refresh` / `db:wipe`  
- Process/delete unrelated `jobs` rows  
- Drop non-marketing tables  
- Expose backup files via `public/`  

---

## 6. Current production note (2026-10-31)

- Backup exists and verified.  
- Marketing tables populated with platforms + disabled QA policy + audit rows.  
- Prefer soft disable over schema drop unless owner requests full marketing schema removal.
