# Marketing Queue & Cron Runbook (Phase 1)

**Status:** Operational guidance only — **nothing activated in production by this Phase 1 task.**  
**Date:** 2026-10-31

---

## 1. Current findings (VERIFIED)

| Item | Value |
|------|-------|
| Queue driver | `database` |
| Jobs table | `jobs` |
| Failed jobs | `failed_jobs` — **0** failed at audit |
| Pending jobs | **2** rows in `jobs` |
| Pending job class | `App\Jobs\NotifyFriendsOfPublishedEvent` (ids 3, 4; attempts=0) |
| Scheduler definitions | `routes/console.php` → `account:purge-deletions` daily |
| Funnity crontab entry | **Missing** |
| Permanent `queue:work` for Funnity | **Not observed** |
| Other apps on host | Have their own `schedule:run` / queue cron entries |
| Horizon | Not installed |
| Docker / supervisor for Funnity | Not user-managed |

---

## 2. Pending-job classification

| Job | Risk if drained | Notes |
|-----|-----------------|-------|
| `NotifyFriendsOfPublishedEvent` ×2 | Low–medium | Likely friend push/email notifications for published events. Safe to process when a worker is intentionally started. **Do not delete.** **Do not process as part of Phase 1.** |

These jobs are unrelated to marketing. Marketing Phase 1 introduces **no** new queued jobs.

---

## 3. Recommended scheduler configuration (owner approval required)

Add to the `d14098` crontab (do **not** apply without approval):

```cron
* * * * * cd /home/d14098/funnity.com && /usr/local/bin/php artisan schedule:run >> /home/d14098/funnity.com/storage/logs/scheduler.log 2>&1
```

Notes:

- Prefer `/usr/local/bin/php` (CLI) on this host — `/usr/bin/php` may be php-cgi.
- Existing schedule only runs `account:purge-deletions` daily until Phase 2+ marketing sync commands are added.

---

## 4. Recommended worker configuration (cPanel-compatible)

Because Docker/supervisor for this user is limited, prefer a **bounded, cron-driven** worker that cannot overlap:

```cron
* * * * * cd /home/d14098/funnity.com && /usr/local/bin/php artisan queue:work database --stop-when-empty --max-time=50 --sleep=1 --tries=3 --timeout=45 >> /home/d14098/funnity.com/storage/logs/queue.log 2>&1
```

### Overlap / locking

- `--stop-when-empty` + `--max-time=50` reduces overlap under 1-minute cron.
- Optionally wrap with `flock`:

```cron
* * * * * flock -n /home/d14098/funnity.com/storage/framework/queue.lock -c 'cd /home/d14098/funnity.com && /usr/local/bin/php artisan queue:work database --stop-when-empty --max-time=50 --tries=3 --timeout=45' >> /home/d14098/funnity.com/storage/logs/queue.log 2>&1
```

### Memory / retries

- PHP CLI `memory_limit` observed at 512M — adequate for notification jobs.
- `--tries=3` with failed_jobs table for inspection.
- Do **not** run unbounded `queue:work` without process supervision on this host.

---

## 5. Safe activation sequence (requires owner approval)

1. Confirm backups / maintenance window.  
2. Add scheduler cron only; wait one day; verify `scheduler.log` and that purge command behaves.  
3. Inspect pending jobs (`php artisan queue:monitor` / DB read).  
4. Add flock-wrapped `queue:work --stop-when-empty`.  
5. Confirm the two `NotifyFriendsOfPublishedEvent` jobs complete or move to `failed_jobs` with actionable errors.  
6. Only later (Phase 2+) register marketing sync schedules.

---

## 6. Monitoring

- `storage/logs/scheduler.log`
- `storage/logs/queue.log`
- `failed_jobs` count (already shown on admin dashboard health)
- `jobs` table depth

---

## 7. Rollback

1. Remove Funnity cron lines.  
2. Leave pending jobs untouched.  
3. No marketing queue jobs exist in Phase 1 to purge.

---

## 8. Commands requiring owner approval

| Action | Approved in Phase 1? |
|--------|----------------------|
| Add crontab scheduler | **No** |
| Add queue worker cron | **No** |
| `queue:work` manually draining jobs | **No** |
| `queue:flush` / delete jobs | **No** |
| Restart httpd/php-fpm/supervisor | **No** |
| Production `migrate` | **No** (see deployment doc) |

---

## 9. Marketing Phase impact

Phase 1 code does not depend on cron/queue being live. Admin UI and budget policies work synchronously. Phase 2 sync jobs **will** require the scheduler + worker above.
