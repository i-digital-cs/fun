# Production Incident Investigation — OTP Email + FCM UNREGISTERED

**Date:** 2026-10-31 (investigation UTC)  
**Domain:** https://funnity.com  
**Mode:** Phase 1 **read-only** — no config changes, no restarts, no migrations, no queue clears, no real mail/FCM test sends, no Flutter/Admin V3.1 edits  
**Production changes:** **NONE**

---

## Executive summary

Two separate incidents were investigated.

1. **OTP email (P0):** Laravel is **creating OTP records and treating SMTP `Mail::send` as successful** (no `OTP email delivery failed` log lines). Users still report non-delivery. Today **31** `email_verify` codes were issued and **0** were consumed; **31** new users and **0** email-verified. A real member password-reset flow issued codes (API returned success) while codes remained unused. Root cause of *inbox* non-delivery is **not fully proven in application logs** (provider/spam/DNS path). A **confirmed config smell** exists: `MAIL_ENCRYPTION=ssl` is set but Laravel 12 reads **`MAIL_SCHEME` (null)**. Password-reset API **intentionally masks delivery failures**.

2. **FCM (P1):** `fcm_send_failed` with HTTP **404 / `UNREGISTERED`** is **confirmed** in `storage/logs/laravel.log` at 15:48:56–58 and 15:51:03 UTC. Implementation already deletes matching `device_tokens` on 400/404. Push path is **degraded** (stale tokens + multi-token users), not a total FCM outage. Correlated with friend/DM activity, not with OTP.

These issues do **not** share a root cause.

---

## Production environment verification

| Check | Result |
|-------|--------|
| App path | `/home/d14098/funnity.com` |
| `php artisan env` | `production` |
| `APP_URL` | `https://funnity.com` |
| Timezone | UTC (log timestamps are UTC) |
| Debug | OFF |
| Config cache | **Not present** (`bootstrap/cache/config.php` absent) — runtime reads `.env` |
| Queue | `QUEUE_CONNECTION=database` |
| Mail | `MAIL_MAILER=smtp`, host `mail.funnity.com`, port `465`, user `info@funnity.com` |
| Cache | `CACHE_STORE=database` |

---

## Git baseline

| Item | Value |
|------|--------|
| Branch | `main` |
| HEAD | `3e919763a46b18496ceae2693453b5bec7a34628` |
| Remote | `main...origin/main` |
| Dirty tree | Yes — unrelated Marketing / Admin work present; **preserved** (no reset/clean/pull) |
| Flutter | Not modified |
| Admin V3.1 prototype | Not modified |

---

## OTP flow diagram

```text
API register / login (unverified) / POST /api/email/verify/resend
  / POST /api/forgot-password
        │
        ▼
EmailAuthCodeService::issueAndSend[IfAllowed]
        │  cooldown lock (cache)
        │  invalidate prior unconsumed codes
        │  persist EmailAuthCode (hashed 4-digit)
        ▼
Mail::to(user)->send(AuthCodeMail)   ← SYNCHRONOUS (not queued)
        │
        ├─ success → set cooldown → API { issued: true } / “code sent”
        └─ Throwable → Log::warning('OTP email delivery failed')
                       → DomainException (no cooldown)
                       → register: caught → still 201 with otp metadata
                       → resend: HTTP 503
                       → forgot-password: DomainException swallowed → still 200 “on its way”
```

**Classes involved**

| Layer | Location |
|-------|----------|
| API register | `app/Http/Controllers/Api/Auth/RegisterController.php` |
| API resend / verify | `app/Http/Controllers/Api/Auth/EmailVerificationController.php` |
| API forgot password | `app/Http/Controllers/Api/Auth/PasswordResetController.php` + `PasswordResetTokenService` |
| OTP core | `app/Services/Auth/EmailAuthCodeService.php` |
| Mailable | `app/Mail/AuthCodeMail.php` (markdown `emails.auth-code`) |
| Queue for OTP | **None** — `Mail::send` inline |

---

## OTP failure evidence

### Application / DB (redacted)

| Evidence | Classification |
|----------|----------------|
| Zero matches for `OTP email delivery failed` in `storage/logs/laravel.log` and searchable `storage/logs/api-2026-10-31.log` | CONFIRMED — Laravel did not record SMTP exceptions today |
| Today: **31** `email_verify` codes, **0** consumed | CONFIRMED |
| Today: **31** new users, **0** with `email_verified_at` set | CONFIRMED |
| Today: **2** `password_reset` codes for a real member account; API returned 200 “code is on its way” at **15:58:14** and **15:59:24 UTC**; codes created in DB; user did not complete verify with a code | CONFIRMED (flow) / PROBABLE non-delivery (inbox) |
| Automated test traffic earlier today used `@example.com` and successfully verified codes (control plane works when mailer accepts) | CONFIRMED — does not prove Gmail inbox delivery |

### API correctness

| Path | Behavior | Risk |
|------|----------|------|
| `POST /api/email/verify/resend` | Delivery failure → **503** + message | Honest |
| `POST /api/register` | Delivery `DomainException` → still **201**, OTP metadata may look “ready” without `issued:true` from a successful send | Misleading on hard failure |
| `POST /api/forgot-password` | Always uniform 200; **swallows** delivery `DomainException` (`PasswordResetTokenService`) | **Misleading success** even if send failed |

Success of queue dispatch is **N/A** for OTP (synchronous). “SMTP accepted” ≠ “Gmail inbox delivered”.

### Mail configuration findings

| Item | Value | Notes |
|------|-------|-------|
| Driver | smtp | |
| Host / port | `mail.funnity.com` / **465** | TCP 465 and 587 reachable from app host |
| TLS config | `.env` has `MAIL_ENCRYPTION=ssl` | Laravel 12 `config/mail.php` uses **`MAIL_SCHEME`**, which is **null** at runtime |
| From | `info@funnity.com` / Funnity | |
| SPF | Present (`v=spf1 … ip4:185.181.255.62 … ~all`) | |
| DKIM | Present (`default._domainkey`) | |
| DMARC | `p=none` | Monitoring only |
| MX | `0 funnity.com.` | Self-hosted |
| TLS cert on :465 | CN=`www.funnity.com` (SAN may include mail host — not fully audited) | Hostname alignment should be verified in provider panel |
| Provider delivery logs | Exim mainlog **not readable** from this account | **Additional diagnostic required** (cPanel → Track Delivery / Exim) |

**PROBABLE:** Mis-set `MAIL_SCHEME` (legacy `MAIL_ENCRYPTION` ignored) and/or provider-side deferral/spam filtering for Gmail.  
**UNKNOWN without provider logs:** Exact SMTP transaction result and remote acceptance codes for the 15:58–16:01 sends.

---

## Queue and worker findings

| Item | Finding |
|------|---------|
| Pending `jobs` | **2** on queue `default` |
| Job class | `App\Jobs\NotifyFriendsOfPublishedEvent` |
| Created | **2026-10-27** (~4 days stuck) |
| `failed_jobs` | **0** |
| Funnity cron `queue:work` | **Not present** in `crontab` for `/home/d14098/funnity.com` |
| Funnity `schedule:run` | **Not present** in crontab (schedule defines `account:purge-deletions` daily) |
| Other sites | Have their own queue/schedule crons (unrelated) |

**Impact on OTP:** None directly (OTP is synchronous).  
**Impact on product:** Friend “published event” pushes/emails via that job are **stuck**. Queued `ShouldQueue` notifications (e.g. account deletion) would also stall. FCM chat/friend pushes observed today are **sync** notification channels (not those stuck jobs).

---

## FCM failure evidence

### Log (redacted)

`storage/logs/laravel.log` (UTC), production.WARNING:

```text
[2026-10-31 15:48:56] fcm_send_failed status=404 UNREGISTERED / NotRegistered  (×2)
[2026-10-31 15:48:57] fcm_send_failed … UNREGISTERED  (×7)
[2026-10-31 15:48:58] fcm_send_failed … UNREGISTERED  (×3)
[2026-10-31 15:51:03] fcm_send_failed … UNREGISTERED  (×1)
```

No token strings or user emails are stored in these log lines (good). Payload body is Firebase error JSON only.

### Correlation

| Time (UTC) | Activity |
|------------|----------|
| 15:48:56 | `FriendRequestNotification` persisted for user_id **102** |
| 15:49:19 | `FriendAcceptedNotification` for user_id **226** |
| 15:49:40 / 15:51:02 | Direct messages in conversation involving Play reviewer ↔ member |
| 15:51:03 | Final UNREGISTERED warning; device token row for user **102** shows `last_used_at` same second (likely another token/path) |

### Implementation

| Step | Location | Behavior |
|------|----------|----------|
| Channel | `app/Notifications/Channels/FcmChannel.php` | Calls `FcmPushService::sendToUser` |
| Send | `app/Services/Notifications/FcmPushService.php` | HTTP v1 `messages:send` per token |
| On failure | same | `Log::warning('fcm_send_failed', …)` |
| UNREGISTERED | HTTP 404 → **`DeviceToken` delete by token string** | Already implemented |
| Register | `NotificationInboxService::registerDeviceToken` | `updateOrCreate(['token' => $token], …)` |
| Logout unregister | `DeviceTokenController` + `forgetDeviceToken` | Deletes matching user+token |

### Token lifecycle findings

| Finding | Class |
|---------|--------|
| 14 device token rows; 7 users | CONFIRMED |
| 0 duplicate identical token strings | CONFIRMED |
| user_id **1** holds **6** distinct Android tokens (accumulating since Sept) | CONFIRMED — amplifier for UNREGISTERED storms |
| Several users hold 2 tokens | CONFIRMED |
| Re-register updates same token to current user | CONFIRMED |
| Logout removes only the provided token for that user | CONFIRMED |
| No retry loop inside `FcmPushService` for a single HTTP call | CONFIRMED |
| Queued friend-publish job backlog unrelated to this burst | CONFIRMED |

**CONFIRMED cause of log noise:** FCM rejecting stale/uninstalled app registration tokens (`UNREGISTERED`).  
**PROBABLE trigger volume:** Multi-device stale rows (especially user 1) + burst of friend/DM notifications after Android testing.  
**Not confirmed:** Flutter token refresh bug (handoff only if owner wants client audit).

---

## Confirmed vs suspected causes

### OTP

| Cause | Status | Support |
|-------|--------|---------|
| OTP not generated | Rejected | DB shows codes created |
| OTP stuck on queue with no worker | Rejected | Sync `Mail::send` |
| Laravel SMTP exception | Not evidenced | No `OTP email delivery failed` lines |
| API lies on password-reset failure | CONFIRMED (code) | `PasswordResetTokenService` swallows `DomainException` |
| `MAIL_SCHEME` unset / legacy encryption | CONFIRMED (config) | Runtime `scheme=null`, `.env` has `MAIL_ENCRYPTION=ssl` |
| Provider accepted then dropped / spam | PROBABLE | 0/31 verify consumptions + real reset unused; needs Track Delivery |
| Mass bot signups never verifying | PROBABLE partial | Could inflate 0/31; does not explain real member reset reports |

### FCM

| Cause | Status | Support |
|-------|--------|---------|
| Firebase project misconfigured entirely | Rejected | Some tokens update `last_used_at`; OAuth path functional enough to get 404 semantics |
| UNREGISTERED stale tokens | CONFIRMED | Log `errorCode=UNREGISTERED` |
| Missing delete-on-404 | Rejected | Code deletes on 400/404 |
| No funnity queue worker | CONFIRMED separate issue | Stuck `NotifyFriendsOfPublishedEvent` jobs |

---

## Impact on Android 1.0.3

Repo Flutter `pubspec` baseline remains `1.0.2+3` in tree; Closed Testing may ship a higher Play `versionCode` as **1.0.3** on the owner’s Windows machine. Evidence today shows:

- Real Android devices registering/updating FCM tokens (`platform=android`).
- Friend/DM traffic around the UNREGISTERED burst (Play reviewer account involved).
- Password-reset OTP requests from a member during the same afternoon window.

OTP non-delivery is **backend/mail-path**, not an Android binary defect, unless the client only surfaces the optimistic API message (forgot-password always 200).

FCM UNREGISTERED is **expected** after reinstall/uninstall; cleanup exists but stale multi-tokens still create warning storms.

---

## Proposed minimal fixes (awaiting approval)

### P0 — Restore trustworthy OTP delivery

| # | Change | Files / ops | Risk | Rollback |
|---|--------|-------------|------|----------|
| A | Set `MAIL_SCHEME=smtps` (and stop relying on unused `MAIL_ENCRYPTION`) after owner confirmation | `.env` only | Low if SMTP already works; verify with **one authorized** test send | Revert env key |
| B | In cPanel Track Delivery / Exim: inspect outbound `info@funnity.com` for the 15:58–16:01 window | Provider UI | None (read) | N/A |
| C | Log SMTP failure class/code **without** OTP plaintext or full email (hash user id already) | `EmailAuthCodeService` | Low | Revert commit |
| D | Stop swallowing delivery failures on forgot-password **or** return a distinct soft signal while keeping anti-enumeration | `PasswordResetTokenService` | Medium (product copy) | Revert |
| E | Optional: align register hard-fail messaging when mail throws | `RegisterController` | Low–medium | Revert |

**Tests:** Feature tests for 503 on resend failure; forgot-password still non-enumerating; no OTP in logs.  
**Downtime:** None.  
**Security:** Never log OTP codes or raw recipients.

### P1 — FCM hygiene

| # | Change | Files | Risk | Rollback |
|---|--------|-------|------|----------|
| F | On UNREGISTERED, delete by token (already done); add redacted metrics (`user_id`, token fingerprint prefix) | `FcmPushService` | Low | Revert |
| G | Cap tokens per user (e.g. keep last N per platform) on register | `NotificationInboxService` | Low | Revert |
| H | Do **not** mass-delete tokens without approval | — | — | — |

### P2 — Queue health (separate but production-real)

| # | Change | Ops | Risk |
|---|--------|-----|------|
| I | Add supervised `queue:work` + `schedule:run` for funnity.com (owner approval) | cron/supervisor | Medium — process management |
| J | After worker healthy, process or safely discard the 2 aged `NotifyFriendsOfPublishedEvent` jobs | queue | Low if inspected first |

---

## Required approvals

1. Authorize **one** controlled OTP test send to an owner-owned mailbox.  
2. Approve `.env` `MAIL_SCHEME=smtps` (or provider-documented scheme).  
3. Approve access/review of cPanel Track Delivery results.  
4. Approve any code changes (C–G) and queue worker install (I–J).  
5. Approve any token cleanup beyond automatic UNREGISTERED deletes.

---

## Verification plan (post-approval)

1. Owner mailbox: request OTP → confirm **inbox** (not only API 200).  
2. Confirm new `email_verify` consumption rate moves above zero for real accounts.  
3. Trigger FCM to a known good device; confirm no UNREGISTERED for that token; stale tokens removed once.  
4. Confirm pending job count drains after worker starts.  
5. Watch `laravel.log` for absence of SMTP exceptions and reduced UNREGISTERED rate.

---

## Rollback plan

- Env-only: restore previous `.env` keys; no deploy required beyond PHP-FPM naturally reloading env (no `config:cache` currently).  
- Code: `git revert` specific commits on `main`.  
- Workers: stop supervisor program / remove cron line.  
- Do not re-inject deleted FCM tokens.

---

## Additional diagnostic still needed

1. **cPanel Track Delivery / Exim** for `info@funnity.com` at 15:58–16:01 UTC (and ~16:01 email_verify).  
2. Gmail “Show original” on a successful test (SPF/DKIM/DMARC pass?).  
3. ~~Confirm whether `mail.funnity.com` certificate SAN includes `mail.funnity.com`.~~ **Done in Phase 2 — SAN includes `mail.funnity.com`; OpenSSL verify OK.**  
4. Optional authorized SMTP auth probe (not performed).  
5. **Authorized controlled OTP live test** (Phase 2 plan below).

---

## Verdict block (for operator) — Phase 1

| Field | Value |
|-------|--------|
| INCIDENT VERDICT | **PARTIAL** |
| OTP STATUS | **BROKEN** (user-visible delivery) / app-layer send appears to complete without logged exceptions |
| FCM STATUS | **DEGRADED** |
| PRODUCTION CHANGES | **NONE** |
| APPROVAL REQUIRED | **YES** |

---

# PHASE 2 — OTP DELIVERY ROOT CAUSE VERIFICATION

**Executed:** 2026-10-31 ~16:09–16:15 UTC  
**Mode:** Read-only (no `.env` changes, no live OTP send, no FCM/queue work, no Flutter/Admin V3.1 edits)  
**Production changes:** **NONE**

## 1. Production environment verification

| Check | Result |
|-------|--------|
| Path | `/home/d14098/funnity.com` |
| Environment | `production` |
| PHP | 8.2.35 (CLI) |
| Laravel | **12.69.2** |
| Git branch / HEAD | `main` / `3e919763a46b18496ceae2693453b5bec7a34628` |
| Working tree | Dirty (Marketing/Admin WIP preserved) |
| Config cache | **Absent** (`bootstrap/cache/config.php` not present) |

## 2. Actual mail runtime configuration

| Key | Runtime value |
|-----|----------------|
| `mail.default` | `smtp` |
| Host | `mail.funnity.com` |
| Port | `465` |
| `mail.mailers.smtp.scheme` (config array) | `null` |
| `.env` `MAIL_SCHEME` | unset / null |
| `.env` `MAIL_ENCRYPTION` | `ssl` (**not read** by Laravel 12 `config/mail.php`) |
| Username / password | present (not disclosed) |
| From | `info@funnity.com` / `Funnity` |
| `local_domain` (EHLO) | `funnity.com` |

## 3. SMTP transport analysis

Laravel 12 `Illuminate\Mail\MailManager::createSmtpTransport()`:

```php
if (! $scheme) {
    $scheme = ($config['port'] == 465) ? 'smtps' : 'smtp';
}
```

**Evidence:** Live Symfony transport string resolved to:

```text
smtps://mail.funnity.com
class: Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport
```

**TLS:** OpenSSL to `mail.funnity.com:465` with SNI `mail.funnity.com` → verify return code **0 (ok)**. Certificate SAN **includes** `mail.funnity.com` (and `funnity.com`, `www.funnity.com`, webmail, etc.).

**Conclusion:** Effective encryption is **SMTPS**. `MAIL_SCHEME=null` does **not** mean plaintext on port 465. Setting `MAIL_SCHEME=smtps` would be **documentation alignment only**, not a proven fix for inbox non-delivery. `MAIL_ENCRYPTION=ssl` is legacy/unused and currently **harmless**.

## 4. cPanel / Exim delivery evidence

| Probe | Result |
|-------|--------|
| `/var/log/exim_mainlog` | Not accessible (missing / permission) |
| `/var/spool/exim/msglog` | Not accessible |
| `uapi Email …` | Fails: `Failed to execute /usr/local/cpanel/cpanel` |
| `info@` Maildir `.Sent` | Empty for 2026-10-31 (no Sent copies of OTP traffic) |
| Privilege escalation | Not attempted |

**Owner must provide from cPanel → Email → Track Delivery** (or host Exim search) for sender `info@funnity.com`, window **2026-10-31 15:50–16:10 UTC**, ideally including:

- Event time (UTC)  
- Recipient domain only (redact local-part)  
- Result: Accepted / Deferred / Rejected / Failed  
- Remote SMTP response code/text if shown  
- Message ID if available  

### Delivery-state distinction (current evidence)

| State | Status |
|-------|--------|
| Laravel send completed | **CONFIRMED** for paths that returned `issued: true` / applied cooldown (no `OTP email delivery failed` log) |
| Local SMTP server accepted | **PROBABLE** (Laravel/Symfony completed without exception) — **not proven** via Exim |
| Remote provider accepted | **UNKNOWN** — needs Track Delivery |
| Recipient inbox received | **UNKNOWN** — user reports + 0/31 verify consumptions are symptoms, not SMTP proof |

## 5. SPF / DKIM / DMARC findings

| Record | Observation |
|--------|-------------|
| MX | `0 funnity.com.` → `185.181.255.62` |
| A `mail.funnity.com` | `185.181.255.62` (same host) |
| SPF | `v=spf1 +a +mx +ip4:185.181.255.62 include:spf.mysecurecloudhost.com ~all` — sending IP appears authorized |
| DKIM | `default._domainkey.funnity.com` publishes a key |
| DMARC | `v=DMARC1; p=none;` — monitor-only; does not reject |

DNS is **not** by itself proof of Gmail/Yahoo inbox placement. Soft-fail SPF (`~all`) + `p=none` can still yield spam classification.

Sending provider: **self-hosted cPanel/Exim-class mail on the same VPS** (`mail.funnity.com` / mysecurecloudhost SPF include).

## 6. OTP implementation review

| Topic | Finding |
|-------|---------|
| Transport | Sync `Mail::to(...)->send(new AuthCodeMail(...))` |
| From | Global `config('mail.from')` → `info@funnity.com` |
| Exceptions | Caught in `EmailAuthCodeService::createAndDeliver`; logs class only (no OTP); throws `DomainException` |
| Success timing | API success **after** `Mail::send` returns (not queued) |
| Template | Markdown `emails/auth-code` via `<x-mail::message>`; subject via `__()`; includes HTML OTP display; Laravel markdown supplies text alternative |
| Cooldown / expiry | Config-driven; resend cooldown after **successful** send only |
| Register path | On delivery `DomainException`, still returns **201** with cooldown metadata (`RegisterController`) |
| Resend path | Delivery failure → **503** (honest) |
| Login path | Uses `issueAndSendIfAllowed` (cooldown soft-skip) |

No evidence of invalid custom headers that would block SMTP handshake.

## 7. Password-reset exception handling

`PasswordResetTokenService::requestCode`:

- Catches **`DomainException` only** (including delivery failure from OTP service).  
- Returns the **same public success shape** as a successful send (anti-enumeration).  
- **Does not** re-log beyond whatever `EmailAuthCodeService` already logged.  
- `OtpCooldownException` is absorbed inside `issueAndSendIfAllowed` (returns `issued: false` metadata still wrapped in uniform 200).

**Client:** must stay generic.  
**Server:** should retain/ensure `OTP email delivery failed` warnings remain visible to operators when transport throws.  
**Note:** For the observed 15:58/15:59 resets, cooldown/`issued` behavior indicates **`Mail::send` did not throw** — suppression did **not** hide a transport exception for those specific requests.

## 8. Root-cause classification

| ID | Hypothesis | Classification | Evidence |
|----|------------|----------------|----------|
| A | Incorrect SMTP configuration | **RULED OUT** as TLS/scheme defect | Port 465 → auto `smtps`; live transport `smtps://mail.funnity.com` |
| B | TLS/SSL negotiation failure | **RULED OUT** for app→mail host | OpenSSL verify OK; SAN includes `mail.funnity.com` |
| C | SMTP authentication failure | **RULED OUT** for `issued: true` sends | Would throw → logged + no cooldown |
| D | Sender identity problems | **UNKNOWN** / weak **PROBABLE** at remote | From identity looks correct; remote reputation unknown |
| E | SPF/DKIM/DMARC failures | **UNKNOWN** without message headers | Records present; DMARC `p=none`; need “Show original” |
| F | Rejected by recipient providers | **UNKNOWN** | Needs Track Delivery |
| G | Accepted then spam/junk | **PROBABLE** | Cross-provider unverified signups (21× gmail.com today) + member reset unused; not definitive |
| H | Template/header defects | **RULED OUT** as primary | Standard Laravel markdown mailable |
| I | OTP generation / API defects | **RULED OUT** for generation; **CONFIRMED** misleading UX on forgot-password **if** transport fails | Codes hashed in DB; API 200 always on forgot-password |
| J | Rate limiting blocking sends | **RULED OUT** as primary | Codes issued; limiter is 5/min |
| K | Delivery errors suppressed | **CONFIRMED** for forgot-password catch; **RULED OUT** as explanation for successful `Mail::send` cases | |

**Phase 2 root cause (evidence-based):**  
Application SMTP client configuration is **valid (SMTPS)**. Laravel completed sends without recorded transport exceptions. **Inbox non-delivery root cause remains upstream of the app** (local MTA → remote acceptance → spam) and **cannot be closed** without Track Delivery and/or an authorized live receive test. Unused OTP counts support the **user-facing incident** but are **not** definitive SMTP failure proof (bots/abandonment possible alongside deliverability).

## 9. Minimal remediation proposal

**Do not apply speculative `MAIL_SCHEME=smtps` as the P0 fix** — runtime already uses SMTPS.

| Priority | Change | Exact action | Impact | Risk | Verify | Rollback | Cache/restart |
|----------|--------|--------------|--------|------|--------|----------|---------------|
| P0a | Evidence | Owner exports Track Delivery for 15:50–16:10 UTC | Identifies accept/defer/reject | None | Compare to API times | N/A | None |
| P0b | Live proof | Authorized OTP test to owner mailbox (plan §10) | Confirms inbox path | Low (one send) | Inbox + verify API | N/A | None |
| P1 | Logging | Enrich `OTP email delivery failed` with exception **message class + SMTP code** (still no OTP/recipient) | Faster ops triage | Low | Force failure in staging | Revert commit | No config cache today |
| P1 | Forgot-password ops signal | On caught `DomainException`, `Log::warning` with purpose=`password_reset` if not already logged | Detect swallowed failures | Low | Unit/feature test | Revert | None |
| P2 | Env hygiene | Optionally set `MAIL_SCHEME=smtps`; remove reliance on `MAIL_ENCRYPTION` in docs/`.env.example` | Clarity only | Very low | Transport string still `smtps://` | Unset key | No `config:cache` unless later enabled |
| P2 | Deliverability | If Track Delivery shows spam/reject: provider reputation, DMARC tighten, or transactional ESP | Inbox placement | Medium (DNS/ESP) | Header authentication results | DNS/ESP rollback | Provider-specific |

**FCM / queue workers / stuck jobs:** explicitly **out of scope** this phase.

## 10. Controlled test plan (not executed)

**Requires explicit owner approval + owner-controlled destination address.**

| Item | Detail |
|------|--------|
| Preferred endpoint | `POST https://funnity.com/api/forgot-password` with JSON `{ "email": "<owner-authorized>" }` |
| Why this endpoint | No new account if address already exists; uniform response; exercises production OTP mail path |
| Alternate | Create/use a dedicated owner test account → `POST /api/email/verify/resend` (auth required) |
| Expected DB writes | New `email_auth_codes` row (hashed code); prior unconsumed codes for that purpose marked consumed; cooldown cache key |
| Rate limits | `throttle:auth-codes` = **5/min** per IP (unauthenticated forgot-password); plus OTP cooldown ~60s after successful send |
| Must not | Bypass mailer, hardcode OTP, edit DB, bulk send, log OTP |
| Verify after send | (1) API 200 (2) `laravel.log` absence of `OTP email delivery failed` (3) Track Delivery accept (4) Inbox **and** Spam (5) `POST /api/forgot-password/verify` succeeds (6) expiry/resend cooldown |

**Status:** **NOT EXECUTED** — awaiting authorization.

## 11. Required owner approvals

1. Provide **Track Delivery** (or Exim) redacted results for `info@funnity.com`, 15:50–16:10 UTC 2026-10-31.  
2. Authorize **one** controlled OTP send to an owner mailbox (address to be supplied by owner).  
3. Approve any P1 logging code changes before deploy.  
4. Approve any DNS/ESP/DMARC changes only after delivery evidence.  
5. Do **not** approve speculative `.env` encryption changes as the sole fix without evidence.

## 12. Rollback plan

- No production changes were made in Phase 2.  
- Future env hygiene: revert `MAIL_SCHEME` line; PHP picks up `.env` without config cache.  
- Future code: `git revert`.  
- DNS/ESP: restore prior records/provider.

## Phase 2 operator summary

| Field | Value |
|-------|--------|
| OTP INCIDENT STATUS | **PARTIAL** (user-facing confirmed; SMTP client config validated) |
| SMTP CONFIGURATION | **VALID** (effective `smtps` on 465) |
| MAIL DELIVERY | **UNKNOWN** (remote/inbox); Laravel completion **CONFIRMED** for issued sends |
| ROOT CAUSE | App TLS/SMTP scheme **ruled out**; inbox failure **unknown/probable upstream** |
| REQUIRED FIX | Track Delivery + authorized live receive test first; then deliverability/logging — **not** blind `MAIL_SCHEME` change |
| LIVE TEST REQUIRED | **YES** |
| PRODUCTION CHANGES MADE | **NONE** |
| OWNER APPROVAL REQUIRED | **YES** |
