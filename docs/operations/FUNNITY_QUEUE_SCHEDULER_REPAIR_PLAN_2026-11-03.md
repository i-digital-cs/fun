# Funnity — Production Queue & Scheduler Repair Plan

**Date:** 2026-11-03  
**Priority:** P0 — OPS-01  
**Mode:** Investigation + preparation only — **activation NOT performed**  
**Project:** `/home/d14098/funnity.com`  
**Related audit:** `docs/audits/FUNNITY_PRODUCTION_AUDIT_2026-11-03.md`

---

## A. Root cause

**Confirmed:** Funnity has never (or no longer) registered process automation under this cPanel account.

1. **No Funnity crontab entries** for `schedule:run` or `queue:work` (user crontab contains souqnest, concordia, software-builder-platform, generalsys — **not** funnity.com).
2. **No live Funnity queue worker process** (`ps` showed none).
3. **Supervisor is not available** on this account (`supervisorctl` / `supervisord` missing). Souqnest keeps Supervisor *config files* but runs a **cron fallback** worker in practice.
4. **systemd user services unavailable** (`systemctl` not present in PATH for this shell).
5. Application correctly uses `QUEUE_CONNECTION=database` and dispatches `NotifyFriendsOfPublishedEvent` on publish — jobs land in MySQL `jobs` and sit forever without a consumer.
6. Scheduler is defined in code (`account:purge-deletions` daily) but **never invoked** without cron → purge and any future schedules are inert.

**Not the root cause:** Failed jobs table corruption (empty), wrong queue connection in `.env`, or PHP-FPM itself.

---

## B. Current production evidence

| Check | Result |
|-------|--------|
| Git | `main` @ `3e91976`; dirty tree ~208 paths (unchanged by this task beyond docs) |
| Laravel / PHP | 12.69.2 / 8.2.35 |
| `QUEUE_CONNECTION` | `database` |
| Default queue name | `default` (`config/queue.php` / `DB_QUEUE`) |
| `retry_after` | **90** seconds |
| Failed jobs | **0** |
| Pending jobs | **2** on `default` |
| Reserved | both `reserved_at=null`, `attempts=0` |
| Funnity cron | **None** |
| Funnity worker PID | **None** |
| Supervisor | **Not installed / not usable** |
| Peer pattern | Cron + `queue:work --stop-when-empty` or souqnest `cron-fallback.sh` with lock + `--max-time=55` |
| PHP CLI | `/usr/local/bin/php` → `/opt/alt/php82/usr/bin/php` (cli). `/usr/bin/php` is cgi-oriented wrapper — **avoid for Artisan**. |

### Pending job rows

| jobs.id | uuid | queue | class | attempts | reserved | available_at (UTC) | created_at (UTC) |
|--------:|------|-------|-------|---------:|----------|--------------------|------------------|
| 3 | ccc78b0b-ac7a-4126-ac6a-003dc333dae9 | default | `App\Jobs\NotifyFriendsOfPublishedEvent` | 0 | null | 2026-10-27T05:20:38Z | 2026-10-27T05:20:38Z |
| 4 | ba1b3d22-56bf-48ed-900d-e9c3f41cc6a3 | default | `App\Jobs\NotifyFriendsOfPublishedEvent` | 0 | null | 2026-10-27T19:32:43Z | 2026-10-27T19:32:43Z |

Age as of 2026-11-03: **~7 days**.

---

## C. Pending jobs assessment

Payload inspection used regex on serialized `eventId` / `publisherId` only (no user PII printed).

### Job 3 — eventId=603, publisherId=321

| Field | Value |
|-------|--------|
| Event 603 | **MISSING** from `events` |
| User 321 | **MISSING** from `users` |
| If executed now | Job `handle()` returns immediately (`find` null) — **no notifications** |
| Risk if worker starts | Low side-effect; consumes and deletes the job row |
| Product value | None |

### Job 4 — eventId=604, publisherId=102

| Field | Value |
|-------|--------|
| Event 604 | **Exists**, `status=published`, `starts_at=2026-10-30 22:31:00`, `ends_at=NULL`, `created_by=102` |
| Stale-end guard | **Does not apply** (`ends_at` null → code does not skip) |
| Publisher | Active; `notify_friend_events=1` |
| Accepted friends | 3 |
| Eligible recipients (active + notify_friend_events) | **3** |
| Existing `FriendEventNotification` for event 604 | **0** |
| Channels if run | **database + FCM** (via sync `FriendEventNotification` inside job — notification class does **not** implement `ShouldQueue`) |
| Email | **No** for this fan-out |
| Idempotent? | **Partially** — skips recipients who already have DB notification for same `event_id`; first run would still deliver to all 3 |
| Duplicate risk | Low on re-run after success; **high product risk** for first run now (week-old “friend published” push) |

### Recommended handling (do **not** execute)

**Safest option: Quarantine both jobs off `default` (UPDATE `queue` column), then decide later.**

- Preserve rows for audit trail (no delete without approval).
- Do **not** execute job 4 (outdated FCM/database fan-out to ~3 users).
- Job 3 is harmless no-op but should still leave `default` empty before worker start so activation is predictable.

Prepared SQL (inactive):  
`docs/operations/queue-scheduler-prep-2026-11-03/stale-jobs-quarantine.sql`

**Do not use `queue:pause default` alone** as the backlog shield: pausing `default` also blocks *new* jobs until resume. Quarantine-by-queue-name is selective.

---

## D. Job inventory

Search covered `app/Jobs`, `app/Notifications`, `app/Listeners`, dispatch sites.

| Job / queued unit | Queue | Trigger | Side effects | Idempotent? | Risk |
|-------------------|-------|---------|--------------|-------------|------|
| `NotifyFriendsOfPublishedEvent` | `default` (implicit) | `TransitionEventStatusService` on publish | Loads friends; sends `FriendEventNotification` (DB + FCM) per eligible friend; skips ended events, missing models, blocks, prefs, existing DB notif | Partial (DB dedupe) | **Medium** — push noise if delayed |
| `AccountDeletionRequestedNotification` | `default` (ShouldQueue) | `AccountDeletionService::requestDeletion` | Queued **mail** about deletion grace | Yes if mail provider dedupes poorly — generally one-shot | **Medium** — email; delayed mail confusing |
| Other notifications (`FriendRequest*`, `JoinRequest*`, `ChatMessagePush*`, `MessageReacted*`, `DirectMessagePush*`, `FriendEventNotification`) | n/a (sync) | Various services | DB and/or FCM **inline** when `notify()` called | Varies | Not delayed by missing worker **unless** parent job queues them |
| Listeners implementing `ShouldQueue` | — | — | **None found** under `app/Listeners` | — | — |
| `ShouldBeUnique` / `WithoutOverlapping` on jobs | — | — | **Not used** on `NotifyFriendsOfPublishedEvent` | — | Duplicate dispatches possible if publish retried |
| OTP / AuthCode mail | sync path (not ShouldQueue job) | Auth services | Mail via default smtp | — | **Unaffected by queue worker** for current design |
| Ticket generation | not a queued job in inventory | — | — | — | Not queue-backed |

**When a worker starts**, anything on `default` runs: today only the two stale `NotifyFriendsOfPublishedEvent` rows, plus any *new* `ShouldQueue` notifications (account deletion mail) or newly dispatched publish jobs.

---

## E. Scheduler inventory

Source: `routes/console.php`

| Schedule | Command | Lock / overlap | Dry-run | Effects | Risk |
|----------|---------|----------------|---------|---------|------|
| `daily()` (00:00) | `account:purge-deletions` | None in command | **No** | Calls `AccountDeletionService::purgeDueAccounts()` | **Destructive when due** |

### `account:purge-deletions` behavior (confirmed in code)

- Grace: `config('auth.account_deletion_grace_days', 30)` (currently **30**).
- Selects users with `deletion_requested_at <= now() - grace`.
- Per user (`purgeUser`): deletes identity files from storage disks, profile image (local), tokens, device tokens, friendships, DM conversations/messages/reads; **anonymizes** user row (name/email/password/social ids cleared; new public_id).
- Does **not** use the queue.
- Idempotent for already-purged users (they no longer match the query).
- **No dry-run flag.**

### Production counts 2026-11-03 (read-only)

| Metric | Value |
|--------|--------|
| `deletion_requested_at` not null | **0** |
| Due for purge now | **0** |

So a purge run **today** would report `Purged 0 account(s)` — still must not be enabled without approval because it becomes live when users request deletion later.

### Temporary safe scheduler

Until purge is approved: **do not install `schedule:run` cron**.  
Phase 1 activates **queue worker only**.  
Optional future code gate (`SCHEDULE_PURGE_ENABLED`) is **out of scope** here (would change app behavior); prefer process-level deferral.

---

## F. Recommended architecture

### Decision matrix

| Option | Feasible now? | Notes |
|--------|---------------|-------|
| **A — Supervisor** | **No** | Binary/config manager not available to this account |
| **B — systemd user** | **No** | `systemctl` unavailable in environment |
| **C — Cron-managed worker** | **Yes — recommended** | Matches souqnest/concordia hosting reality |

### Recommended: Option C

- Minute cron → flock-wrapped script → `queue:work database --queue=default --max-time=55 --timeout=60 --tries=3 --memory=128`
- Ensures **worker timeout (60) < retry_after (90)**
- `--max-time=55` + flock ⇒ no overlapping workers
- Listen **only** to `default` so `hold_*` quarantine queues are ignored
- Logs: `storage/logs/queue-cron.log`

### Queue isolation (future)

- Today all producers use implicit `default`.
- Safe near-term isolation = **quarantine stale rows** to `hold_stale_2026_10_27`, keep producers on `default`.
- Dedicated app queue rename (`funnity`) requires coordinated code + worker change — **not** required for P0 repair; defer.

### Backlog safety on activation

1. Owner approves quarantine SQL → move ids 3,4 off `default`.  
2. Verify `default` empty (or only intentional new jobs).  
3. Then enable worker cron.  
4. Never start a worker against a `default` queue that still holds job 4.

---

## G. Prepared configuration

All inactive, inside the repo:

| Path | Role |
|------|------|
| `docs/operations/queue-scheduler-prep-2026-11-03/README.md` | Index |
| `docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh` | Worker script template |
| `docs/operations/queue-scheduler-prep-2026-11-03/crontab.proposed.snippets.txt` | Cron lines (commented) |
| `docs/operations/queue-scheduler-prep-2026-11-03/stale-jobs-quarantine.sql` | Quarantine SQL |
| `docs/operations/queue-scheduler-prep-2026-11-03/laravel-worker.conf.example` | Supervisor example (future) |
| `docs/operations/queue-scheduler-prep-2026-11-03/ACTIVATION_CHECKLIST.md` | Step checklist |

### Proposed worker command (exact)

```bash
/usr/local/bin/php /home/d14098/funnity.com/artisan queue:work database \
  --queue=default \
  --sleep=3 \
  --tries=3 \
  --timeout=60 \
  --max-time=55 \
  --memory=128
```

### Proposed scheduler command (Phase 2 only)

```bash
cd /home/d14098/funnity.com && /usr/local/bin/php artisan schedule:run >> storage/logs/scheduler.log 2>&1
```

---

## H. Activation procedure (requires owner approval)

**Not executed in this task.**

1. **Approve & apply** `stale-jobs-quarantine.sql` (DB write).  
2. Verify jobs 3/4 on `hold_stale_2026_10_27`; `default` clear of those ids.  
3. **Approve Phase 1 cron** — install worker snippet only.  
4. `chmod +x` worker script; wait two minutes; inspect `queue-cron.log`.  
5. Optional: approve a controlled new queued item to prove drain (no OTP blast).  
6. **Separate approval** for scheduler / purge.  
7. Separate decision on hold queue: keep / delete / re-queue job 4 (default recommendation: **delete or keep held**, do not re-queue).

---

## I. Rollback

1. Remove Funnity lines from crontab (`crontab -e`).  
2. Confirm no `artisan queue:work` for funnity.com in `ps`.  
3. Quarantined jobs remain untouched unless owner requests restore/delete.  
4. No Supervisor to stop; no service restart required.  
5. Application code unchanged by activation (cron-only), so rollback is process-level.

---

## J. Verification

| Goal | How |
|------|-----|
| Worker runs once | flock log lines; single PID during window |
| Stale jobs not executed | hold queue row count stable; no new `FriendEventNotification` for event 604; no FCM fan-out |
| New jobs process | After approval, dispatch a benign queued notification or publish test on staging; watch `jobs` |
| Failures recorded | Force a failing job only in staging; check `failed_jobs` |
| Recycle | Cron every minute; `--max-time=55` exit codes in log |
| No duplicate notifs | Job dedupe + avoid re-queueing 604 |
| Scheduler once/min | `scheduler.log` timestamps (Phase 2) |
| Purge blocked until approved | No schedule cron in Phase 1 |
| Resources | `ps`/`top` spot check; memory cap 128MB |
| App health | Spot GET `/up`, login page; no queue-related errors in `laravel.log` |

Any test that sends mail/push or modifies user data = **requires approval**.

---

## K. Production safety (this task)

| Action | Done? |
|--------|-------|
| Ran `queue:work` / `listen` / `restart` | **No** |
| Ran `schedule:run` / `schedule:work` | **No** |
| Deleted / flushed / retried jobs | **No** |
| Modified `jobs` / `failed_jobs` rows | **No** |
| Changed `.env` / mail / DNS | **No** |
| Restarted PHP-FPM / Apache | **No** |
| Sent notifications / email / push | **No** |
| Git commit / push | **No** |
| Wrote docs + inactive prep files only | **Yes** |

---

## Next owner decisions (exact)

1. **Approve quarantine SQL** for jobs `id IN (3,4)` → `hold_stale_2026_10_27`?  
2. **Approve Phase 1** cron worker activation (after quarantine)?  
3. **Disposition of hold jobs** — keep / delete / (not recommended) re-execute job 4?  
4. **Approve Phase 2** `schedule:run` knowing it enables destructive `account:purge-deletions` when accounts become due?  
5. Optional later: migrate to Supervisor on a host that supports it.

---

*End of repair plan.*
