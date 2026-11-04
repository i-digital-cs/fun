# Queue + Scheduler + FCM Recovery Plan

**Date:** 2026-10-31 (UTC)  
**Domain:** https://funnity.com  
**Mode:** Investigation + isolated code fixes + tests  
**Production runtime changes:** **NONE** (no workers started, no cron installed, no tokens deleted, no jobs retried)  
**OTP / SMTP:** Out of scope (hosting provider)

---

## Environment verification

| Check | Result |
|-------|--------|
| Path | `/home/d14098/funnity.com` |
| `APP_ENV` | `production` |
| PHP CLI | `/usr/local/bin/php` 8.2.35 |
| Laravel | 12.69.2 |
| Queue connection | `database` (`QUEUE_CONNECTION=database`) |
| Queue table | `jobs` / failed `failed_jobs` |
| Config cache | Not present |

## Git baseline

| Item | Value |
|------|--------|
| Branch | `main` |
| HEAD | `3e919763a46b18496ceae2693453b5bec7a34628` |
| Working tree | Dirty (Marketing / Admin WIP + this recovery code/docs) — preserved |
| Flutter / Admin V3.1 / `.env` mail | Not modified for activation |

---

## Queue architecture

| Item | Finding |
|------|---------|
| Driver | **database** |
| Default queue name | `default` |
| `after_commit` | `false` |
| `retry_after` | 90s |
| Queued jobs | `NotifyFriendsOfPublishedEvent` (`ShouldQueue`) |
| Queued notifications | `AccountDeletionRequestedNotification` (`ShouldQueue`) |
| Sync notifications | Friend/DM/chat/join notifications use `FcmChannel` **inline** (not queued) |
| Dispatch site | `TransitionEventStatusService` → `NotifyFriendsOfPublishedEvent::dispatch` |

### Worker findings

| Mechanism | Funnity status |
|-----------|----------------|
| `crontab` `queue:work` | **Missing** for funnity.com |
| `crontab` `schedule:run` | **Missing** for funnity.com |
| Supervisor program | **Not found** for funnity |
| systemd unit | **Not found** |
| Live `queue:work` / Horizon processes | **None** observed |
| Other sites on same account | Have their own cron workers (souqnest, concordia, etc.) — unrelated |

**QUEUE STATUS: STOPPED** (jobs accumulate; no consumer).

---

## Pending jobs

**Count: 2** | **Failed jobs: 0**

| Job ID | Class | Queue | Attempts | Reserved | Created (UTC) | Event | Publisher | Event status | Starts | Ends | Prior friend_event notifs | Classification |
|--------|-------|-------|----------|----------|---------------|-------|-----------|--------------|--------|------|---------------------------|----------------|
| 3 | `NotifyFriendsOfPublishedEvent` | default | 0 | no | 2026-10-27 05:20:38 | **603** | 321 | published | 2026-10-27 08:20 | **2026-10-28 08:20** (ended) | 0 | **OBSOLETE** — ended; code now skips ended events |
| 4 | `NotifyFriendsOfPublishedEvent` | default | 0 | no | 2026-10-27 19:32:43 | **604** | 102 | published | 2026-10-30 22:31 | null | 0 | **MANUAL REVIEW** — still published / not ended; late fan-out may surprise friends |

**Side effects if executed today (pre-fix code):** database + FCM `FriendEventNotification` to accepted friends with `notify_friend_events=true`.  
**After code fix (not yet deployed to runtime until owner deploys):** job 3 no-ops; job 4 may still notify (idempotent if DB notif exists).

**Do not auto-retry or delete without owner approval.**

---

## Failed jobs

None (`failed_jobs` count = 0).

---

## Scheduler findings

| Item | Finding |
|------|---------|
| Registered | `account:purge-deletions` **daily** at `0 0 * * *` (`routes/console.php`) |
| Timezone | App UTC |
| Side effects | Anonymizes accounts past deletion grace (`PurgeAccountDeletionsCommand`) |
| Cron driver | **Missing** for funnity → schedule **never runs** |
| Overlap | Daily once; still needs `schedule:run` every minute to fire |

**SCHEDULER STATUS: MISSING** (definitions exist; executor absent).

**Risk if activated:** overdue purges may run on next midnight after cron is installed — review grace periods before enabling.

---

## FCM findings

| Item | Finding |
|------|---------|
| Service | `App\Services\Notifications\FcmPushService` |
| Channel | `FcmChannel` → `sendToUser` (all user tokens) |
| Registration | `NotificationInboxService::registerDeviceToken` → `updateOrCreate(['token'])` |
| Logout | `forgetDeviceToken` deletes user+token |
| Multi-device | Supported (unique by token string) |
| Live tokens (read) | 14 rows / 7 users; user 1 has 6 tokens; no duplicate token strings |
| Log evidence | `UNREGISTERED` / HTTP 404 bursts 2026-10-31 15:48–15:51 UTC |
| Prior defect | **Any HTTP 400/404 deleted the token**, including ambiguous 400s |
| Sync vs queue | Most FCM sends are **synchronous** with the HTTP request; not blocked by missing queue worker |

**FCM STATUS: DEGRADED** (stale tokens + coarse invalidation). Not a total outage.

---

## Confirmed defects

1. **No Funnity queue worker** → pending friend-publish jobs stuck since 2026-10-27.  
2. **No Funnity `schedule:run` cron** → `account:purge-deletions` never executes.  
3. **FCM invalidation too broad** (pre-fix) → risk of deleting tokens on non-UNREGISTERED 400s.  
4. **Stale friend-notify jobs** could notify about ended events (job 3) if a worker started without guards.  
5. **No duplicate guard** on friend-event notifications (pre-fix).

---

## Code changes (isolated, not activated)

| File | Change |
|------|--------|
| `app/Services/Notifications/FcmPushService.php` | Structured error classification; invalidate only permanent invalid codes / bare 404; preserve auth/quota/5xx; fingerprint logging; delete only if token row `updated_at <= capture time` |
| `app/Jobs/NotifyFriendsOfPublishedEvent.php` | Skip ended events; skip if DB notification already exists for `event_id` |
| `tests/Unit/FcmPushServiceClassificationTest.php` | Classification matrix |
| `tests/Feature/FcmPushServiceTokenLifecycleTest.php` | Invalidate scope, refresh race, register/logout, multi-device |
| `tests/Feature/NotifyFriendsOfPublishedEventJobTest.php` | Ended / duplicate / unpublished |
| `tests/Feature/SchedulerRegistrationTest.php` | Daily purge registration |

**Not changed:** `.env`, cron, Supervisor, Flutter, Admin V3.1, SMTP, production DB rows.

---

## Test results

```text
php artisan test --filter='FcmPushServiceClassificationTest|FcmPushServiceTokenLifecycleTest|NotifyFriendsOfPublishedEventJobTest|SchedulerRegistrationTest'
Tests: 22 passed (34 assertions)
```

No real FCM network sends in tests (classification + DB lifecycle; Http stubs unused for openssl path).

---

## Production activation plan (DO NOT EXECUTE until approved)

### Step 0 — Deploy code (owner)

Deploy the FCM/job fixes to production PHP files (normal release path). Confirm `php artisan test` filter above green in CI or staging.

### Step 1 — Classify pending jobs (owner approval)

| Job | Action |
|-----|--------|
| ID 3 (event 603 ended) | **Safe to process** after deploy (will no-op) **or** delete from `jobs` after written approval |
| ID 4 (event 604) | **Manual review**: either allow one late notify, or delete job if product prefers silence |

Commands (**approval required** — not run):

```bash
# Inspect only
cd /home/d14098/funnity.com && /usr/local/bin/php artisan tinker --execute='echo DB::table("jobs")->count();'

# Optional delete obsolete job 3 AFTER written approval:
# DELETE FROM jobs WHERE id = 3;
```

### Step 2 — Activate queue (shared-hosting safe)

Prefer **cron bounded worker** (matches other sites on this account; no Supervisor for funnity):

```cron
* * * * * cd /home/d14098/funnity.com && /usr/local/bin/php artisan queue:work --stop-when-empty --max-time=55 --sleep=1 --tries=3 >> storage/logs/queue.log 2>&1
```

Overlap mitigation: `--stop-when-empty` + short `--max-time`; database driver reserves jobs. Optional later: `flock`.

**Do not** run `queue:retry all` or `queue:flush`.

### Step 3 — Activate scheduler

```cron
* * * * * cd /home/d14098/funnity.com && /usr/local/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

Review deletion grace before first midnight purge.

### Step 4 — Monitor

- `storage/logs/queue.log` / `scheduler.log`  
- `laravel.log` for `fcm_send_failed` with new `classification` / `token_fp` fields  
- `jobs` / `failed_jobs` counts  
- Confirm new publish creates then drains a job  

### Step 5 — Rollback

- Remove cron lines.  
- Revert FCM/job commits.  
- Do not re-insert deleted FCM tokens.  
- Leave remaining `jobs` rows untouched unless classified.

---

## Flutter handoff (optional)

No Flutter code change required for this recovery. If UNREGISTERED storms continue after cleanup:

- Ensure token refresh re-POSTs `/api/device-tokens`.  
- Ensure logout DELETEs the exact token.  
- Document in a separate Flutter handoff only if client bugs are confirmed.

---

## Remaining risks

- Job 4 late notifications if worker starts before product decision.  
- First scheduler night may purge due deletions.  
- FCM still degraded until stale tokens natural-expire via UNREGISTERED.  
- Cron overlap under extreme load (mitigated by stop-when-empty).  
- OTP email still independent / provider-owned.

---

## Required owner approvals

1. Deploy FCM classification + friend-notify job guards.  
2. Decision on **job 4** (notify vs discard).  
3. Decision on **job 3** (process no-op vs delete).  
4. Install **queue** cron line.  
5. Install **schedule:run** cron line.  
6. Acknowledge first `account:purge-deletions` may run after enablement.

---

## Verdict snapshot

| Field | Value |
|-------|--------|
| OVERALL | **CONDITIONAL** |
| QUEUE | **STOPPED** |
| SCHEDULER | **MISSING** |
| FCM | **DEGRADED** (code fix ready, not live until deploy) |
| PENDING JOBS | **2** (1 obsolete, 1 review) |
| PRODUCTION RUNTIME CHANGES | **NONE** |
| NEXT SAFE ACTION | Owner decides job 4 fate, then approve deploy + cron lines above |
