# Activation checklist (hardened) — owner-gated

**Do not activate without completing each step in order.**  
`schedule:run` remains **DISABLED** for this activation.

Prepared artifacts: `docs/operations/queue-scheduler-prep-2026-11-03/`

---

## 1. Read-only production preflight

- [ ] `cd /home/d14098/funnity.com`
- [ ] Confirm branch/tree awareness (do not reset/clean)
- [ ] `/usr/local/bin/php -v` → PHP 8.2.x **cli**
- [ ] `/usr/local/bin/php -r 'echo PHP_SAPI;'` → `cli`
- [ ] Confirm `QUEUE_CONNECTION=database` and database `retry_after=90`
- [ ] Confirm `storage/logs` exists and is writable by the cron user
- [ ] Read `stale-jobs-quarantine.sql` and `cron-fallback-queue-worker.sh` as approved

## 2. Verify no existing worker

- [ ] `ps aux | grep -E 'funnity.com.*queue:work|queue:work.*funnity' | grep -v grep` → empty
- [ ] Confirm no Funnity queue line already in `crontab -l`

## 3. Backup the two pending job records

- [ ] Export full rows (read-only) to an operator-controlled file, e.g.:

```bash
/usr/local/bin/php -r '
require "vendor/autoload.php";
$app=require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows=Illuminate\Support\Facades\DB::table("jobs")->whereIn("id",[3,4])->orderBy("id")->get();
echo json_encode($rows, JSON_PRETTY_PRINT), PHP_EOL;
' > /home/d14098/funnity.com/storage/logs/jobs-3-4-backup-$(date -u +%Y%m%dT%H%M%SZ).json
```

- [ ] Confirm the JSON file contains exactly two objects (ids 3 and 4)
- [ ] Note: SQL procedure also writes `jobs_quarantine_backup_2026_11_04` during quarantine

## 4. Validate quarantine preconditions

Read-only SQL (do not UPDATE yet):

```sql
SELECT id, queue, attempts, reserved_at,
       FROM_UNIXTIME(available_at), FROM_UNIXTIME(created_at),
       JSON_UNQUOTE(JSON_EXTRACT(payload, '$.displayName')) AS display_name,
       JSON_UNQUOTE(JSON_EXTRACT(payload, '$.uuid')) AS job_uuid
FROM jobs
WHERE id IN (3, 4);
```

Expect:

- [ ] Exactly 2 rows
- [ ] Both `queue = default`
- [ ] Both `reserved_at IS NULL`, `attempts = 0`
- [ ] displayName / payload = `NotifyFriendsOfPublishedEvent`
- [ ] UUIDs match prep doc (`ccc78b0b-…` / `ba1b3d22-…`)

If any check fails → **STOP** (do not run quarantine procedure).

## 5. Quarantine exactly jobs #3 and #4

- [ ] Owner written approval for executing `stale-jobs-quarantine.sql`
- [ ] Run the SQL file via MariaDB client (creates backup table + CALL procedure)
- [ ] Procedure must succeed; on `ABORT:…` / SQLEXCEPTION → transaction rolled back — investigate

## 6. Verify neither remains on `default`

```sql
SELECT id, queue FROM jobs WHERE id IN (3, 4);
SELECT id, queue FROM jobs WHERE queue = 'default';
SELECT id, queue, backed_up_at FROM jobs_quarantine_backup_2026_11_04 WHERE id IN (3, 4);
```

- [ ] ids 3 and 4 → `hold_stale_2026_10_27`
- [ ] Neither id appears with `queue=default`
- [ ] Backup table has both rows

## 7. Verify no unexpected pending jobs will execute

- [ ] Inspect remaining `default` queue: `SELECT id, queue, JSON_UNQUOTE(JSON_EXTRACT(payload,'$.displayName')) FROM jobs WHERE queue='default';`
- [ ] If any rows remain on `default`, identify class/trigger and get approval before worker start
- [ ] Ideal Phase-1 start: `default` empty

## 8. Install exactly one approved worker cron

- [ ] `chmod +x docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh`
- [ ] Install **only** the Phase 1 line from `crontab.proposed.snippets.txt`
- [ ] Confirm `crontab -l` shows exactly one Funnity worker entry
- [ ] Confirm **no** `schedule:run` for funnity.com

## 9. Verify process locking and logs

- [ ] Within ~2 minutes: `storage/logs/queue-cron.log` shows start + exit (or skip log if overlap test)
- [ ] Overlap test (optional): while worker runs, second manual script invocation exits 0 and appends `queue-cron-skip.log` only
- [ ] `ps` never shows two concurrent `queue:work` for this project

## 10. Monitor processing and failures

- [ ] Watch `jobs` / `failed_jobs` counts (read-only)
- [ ] Watch `storage/logs/laravel.log` and `queue-cron.log` for errors
- [ ] Optional (separate approval): controlled new benign queued job — **not** OTP / not job #4 restore

## 11. Confirm both historical jobs remain untouched

```sql
SELECT id, queue, attempts, reserved_at FROM jobs WHERE id IN (3, 4);
```

- [ ] Still on `hold_stale_2026_10_27`
- [ ] `attempts` unchanged (still 0)
- [ ] `reserved_at` still NULL
- [ ] No new `FriendEventNotification` rows for event 604 attributable to these jobs

---

## Explicitly out of scope (remain disabled)

- [ ] ~~`schedule:run`~~ — **DO NOT INSTALL**
- [ ] ~~`account:purge-deletions` via scheduler~~ — separate future approval
- [ ] ~~Restore jobs 3/4 to `default`~~ — do not restore while worker active
- [ ] ~~Execute / retry / delete hold jobs~~ — separate decision

## Rollback (worker only)

1. Remove the Funnity worker line from crontab.
2. Confirm no residual `queue:work` PID for funnity.com.
3. Leave hold-queue jobs and `jobs_quarantine_backup_2026_11_04` intact unless owner directs otherwise.
