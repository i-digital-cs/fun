# Marketing Phase 1 — Production Deployment Record

**Date:** 2026-10-31  
**Target:** `https://funnity.com` / DB `d14098_fun`  
**Verdict:** Deployed foundation (execution still disabled)

---

## 1. Backup

| Field | Value |
|-------|-------|
| Location | `/home/d14098/funnity.com/storage/backups/pre_marketing_phase1_20261031_150510.sql` |
| Meta | `pre_marketing_phase1_20261031_150510.meta.json` |
| Size | 1,509,075 bytes |
| Permissions | `600` |
| Public HTTP exposure | No (`storage/backups` not under `public/`) |
| Integrity | Contains `CREATE TABLE users` and `CREATE TABLE events` |
| Pre counts | users=1512, events=558, jobs=2, failed=0, tables=33 |

---

## 2. Code deployment model

This host runs the application from the git working tree directly (cPanel). Phase 1 code was already present on disk as reviewed uncommitted files. No separate remote pull was required for this deployment.

Caches cleared (non-session-destructive):

- `php artisan view:clear`
- `php artisan route:clear`

**Not cleared:** database cache / sessions.

---

## 3. Migration actions

### Attempt 1 — FAILED

```text
php artisan migrate --path=database/migrations/2026_10_31_160000_create_marketing_foundation_tables.php --force
```

Error: MariaDB identifier too long  
`marketing_integration_credentials_platform_id_credential_type_index`

Partial tables created (empty): platforms, accounts, credentials. Migration **not** recorded.

### Remediation

1. Shortened index name to `mkt_cred_platform_type_idx` in migration file.  
2. Dropped only incomplete empty `marketing_*` tables.  
3. Confirmed users=1512 and jobs=2 unchanged.

### Attempt 2 — SUCCESS

Same scoped migrate command → **DONE** (batch 13).

### Seeder

```text
php artisan db:seed --class=Database\Seeders\MarketingPlatformSeeder --force
```

Run twice → still exactly 2 platforms (`meta`, `tiktok`).

---

## 4. Post-deploy database verification

| Check | Result |
|-------|--------|
| 5 marketing tables exist | YES |
| Platforms | meta, tiktok (enabled) |
| marketing_accounts | 0 |
| marketing_integration_credentials | 0 |
| users / events | 1512 / 558 (preserved) |
| jobs / failed_jobs | 2 / 0 (unchanged) |
| config read_only | true |
| config execution_enabled | false |
| config ai_explanations_enabled | false |

---

## 5. Status matrix

| Item | Status |
|------|--------|
| Implemented | Yes |
| Tested | Yes |
| Deployed (schema + seed + routes/views live) | **Yes** |
| Cron activated | **No** |
| Queue worker activated | **No** |
| Ad accounts connected | **No** |
| Ad spend | **$0** |
| Committed / pushed | **No** |

---

## 6. Notes

- First migration failure was recovered safely without touching non-marketing data.  
- QA budget policy id=1 created then disabled (see Production QA doc); audit logs retained.
