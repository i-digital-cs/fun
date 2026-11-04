# Funnity queue & scheduler prep (INACTIVE)

**Status:** Hardened templates — **NOT activated**.  
**Dates:** Prepared 2026-11-03 · Safety hardening 2026-11-04  
**Parent plan:** `docs/operations/FUNNITY_QUEUE_SCHEDULER_REPAIR_PLAN_2026-11-03.md`

Do not install crontab or execute quarantine SQL until the owner completes `ACTIVATION_CHECKLIST.md`.

| File | Purpose |
|------|---------|
| `ACTIVATION_CHECKLIST.md` | Ordered 11-step activation (schedule stays off) |
| `cron-fallback-queue-worker.sh` | Hardened cron worker (flock-first, default-only) |
| `crontab.proposed.snippets.txt` | Proposed cron lines (commented) |
| `stale-jobs-quarantine.sql` | Enforced-precondition quarantine procedure |
| `laravel-worker.conf.example` | Supervisor example (not available on this host) |

## Invariants

- PHP CLI: `/usr/local/bin/php` → `/opt/alt/php82/usr/bin/php` (8.2.x **cli**). Never `/usr/bin/php` for Artisan.
- Queue connection: `database`; consume queue: `default` only.
- Job `--timeout=60` < DB `retry_after=90`.
- `--max-time=55` is a **soft** stop; in-flight jobs are not SIGKILL’d.
- Stale jobs #3/#4 must be on `hold_stale_2026_10_27` before any worker cron.

## Final worker command (embedded in script)

```bash
/usr/local/bin/php /home/d14098/funnity.com/artisan queue:work database \
  --queue=default \
  --stop-when-empty \
  --sleep=3 \
  --tries=3 \
  --timeout=60 \
  --max-time=55 \
  --memory=128
```
