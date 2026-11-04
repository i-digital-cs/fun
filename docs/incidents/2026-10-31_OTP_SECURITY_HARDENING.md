# Production Auth Security Hardening — OTP Abuse Prevention & SMTP Recovery (Phase 3)

**Date:** 2026-10-31  
**Domain:** https://funnity.com  
**Mode:** Isolated code + tests in git worktree `security/otp-hardening` (`/home/d14098/funnity-otp-security`)  
**Production changes:** **NONE**  
**Related:** `docs/incidents/2026-10-31_PRODUCTION_OTP_FCM_INVESTIGATION.md` (Phases 1–2 preserved)

---

## Executive summary

Hosting confirmed outbound mail from `info@funnity.com` was rejected by `smtp.antispamcloud.com` with SMTP **550 account locked**. Owner rotated the mailbox password and updated production `.env` SMTP credentials. This phase:

1. Verified runtime mail config reads live `.env` (config **not** cached) and effective transport remains **`smtps://mail.funnity.com`** on port **465**.
2. Mapped all email-generating auth endpoints and audited rate limiters.
3. Confirmed **strong registration/OTP volume** Oct 27–31 (381 new users, **375 unverified**, ~380 email-verify OTPs) consistent with automated signup pressure — **not** proven as the sole lock cause.
4. Prepared defense-in-depth controls in an **isolated worktree** (not deployed): tighter registration throttles, per-email OTP limits, global outbound OTP soft budget, email normalization, and internal logging for masked password-reset delivery failures.
5. **Did not** send a live test email, clear caches, rotate SMTP again, modify Flutter/Admin/FCM/queues, or deploy.

**OTP inbox delivery is not confirmed restored** until an owner-authorized live send succeeds.

---

## Environment verification

| Check | Result |
|---|---|
| App path (live) | `/home/d14098/funnity.com` |
| Isolation path | `/home/d14098/funnity-otp-security` (branch `security/otp-hardening`) |
| `APP_ENV` | `production` (live) |
| PHP | 8.2.35 |
| Laravel | 12.69.2 |
| Git branch (live) | `main` |
| HEAD (live) | `3e919763a46b18496ceae2693453b5bec7a34628` |
| Live working tree | Dirty (Marketing + prior FCM/job edits) — **preserved**, not reset |
| Config cache | **Absent** (`bootstrap/cache/config.php` not present) |
| Cache store | `database` (atomic counters OK for rate limits / OTP budget) |
| Queue | `database` (unchanged; OTP mail remains **synchronous**) |
| TrustProxies | Default Laravel stack; **no** app-configured always-trust of `X-Forwarded-For` (direct Apache) |
| Deployment | Direct filesystem / cPanel-style (Apache); no separate deploy pipeline invoked |

---

## SMTP runtime configuration status

| Item | Status |
|---|---|
| `MAIL_MAILER` | `smtp` |
| Host | `mail.funnity.com` |
| Port | `465` |
| Username / From | `info@funnity.com` |
| `MAIL_SCHEME` in env | null (unchanged) |
| Effective Symfony transport | `EsmtpTransport` → **`smtps://mail.funnity.com`** (Laravel auto-SMTPS on 465) |
| Config cache vs new password | **UPDATED** — no config cache; runtime reads `.env` |
| Credentials in this report | **Never printed** |
| Password re-rotation | **Not performed** |
| Relay lock status | **UNKNOWN** (no authorized live send) |
| Recommended if credentials ever cached | `php artisan config:clear` only after owner approval |

**Do not change `MAIL_SCHEME` without new evidence.** Prior Phase 2 already ruled out null scheme as a TLS failure on port 465.

---

## Authentication endpoint inventory

Email-generating **auth** surfaces (inspected in code). Non-auth mail (join-request notifications, account deletion) listed separately.

| Method | Route | Controller | Service | Auth | Middleware / limiter | Dispatch condition | Abuse potential | Web | Flutter |
|---|---|---|---|---|---|---|---|---|---|
| POST | `/api/register` | `Api\Auth\RegisterController` | `RegisterUserService` + `EmailAuthCodeService` | No | **`throttle:auth-register`** (worktree) | Creates user → OTP email_verify if allowed | High (signup farm) | Yes (API) | Yes |
| POST | `/register` | `Web\Auth\RegisteredUserController` | same | No | **`throttle:auth-register`** (worktree) | same | High | Yes | N/A |
| POST | `/api/login` | `Api\Auth\LoginController` | `EmailAuthCodeService::issueAndSendIfAllowed` | No | `throttle:auth` | Unverified user → may email OTP | Medium | Yes | Yes |
| POST | `/login` | `Web\Auth\AuthenticatedSessionController` | same | No | `throttle:auth` | same | Medium | Yes | N/A |
| POST | `/api/email/verify/resend` | `Api\Auth\EmailVerificationController` | `EmailAuthCodeService::issueAndSend` | Sanctum | `throttle:auth-codes` | Unverified only | High | Yes | Yes |
| POST | `/email/verify/resend` | `Web\Auth\EmailVerificationController` | same | Session | `throttle:auth-codes` | same | High | Yes | N/A |
| POST | `/api/email/verify` | `Api\Auth\EmailVerificationController` | `EmailAuthCodeService::verify` | Sanctum | `throttle:auth-code-verify` | No email | Brute-force OTP | Yes | Yes |
| POST | `/email/verify` | Web verify | same | Session | `throttle:auth-code-verify` | No email | Brute-force | Yes | N/A |
| POST | `/api/forgot-password` | `Api\Auth\PasswordResetController` | `PasswordResetTokenService` | No | `throttle:auth-codes` | Known email → password_reset OTP | High (bombing) | Yes | Yes |
| POST | `/forgot-password` | `Web\Auth\PasswordResetController` | `EmailAuthCodeService` | No | `throttle:auth-codes` | same | High | Yes | N/A |
| POST | `/forgot-password/resend` | Web | same | Session | `throttle:auth-codes` | Resend reset OTP | High | Yes | N/A |
| POST | `/api/forgot-password/verify` | API | `PasswordResetTokenService::verifyCode` | No | `throttle:auth-code-verify` | No email | Brute-force | Yes | Yes |
| PUT | `/api/settings/profile` | Profile | `UpdateProfileService` | Sanctum | `throttle:uploads` | Email change → verify OTP | Medium | Yes | Yes |
| POST | `/api/auth/google` · `/api/auth/apple` | Social | — | No | `throttle:auth` | No OTP email on success path | Low (auth spam) | Yes | Yes |

**Other outbound mail (not OTP auth):** `JoinRequestReceivedNotification` (mail channel), `AccountDeletionRequestedNotification` (mail). Rate-limited by authenticated action throttles; not part of this OTP budget.

**Not found:** invitation emails; classic Laravel password-reset notification links (app uses 4-digit OTP instead).

---

## Current rate-limiting coverage (before → after worktree)

| Control | Live (pre-deploy) | Worktree (prepared) |
|---|---|---|
| Registration per IP | `auth` 10/min | **`auth-register` 5/min + 20/hour** |
| Registration per email | unique DB only | Normalize + unique (no separate email RL) |
| OTP per IP | `auth-codes` 5/min | **5/min + 40/hour** |
| OTP per normalized email | **None** | **3/min + 10/hour** |
| OTP per authenticated user | 5/min (id\|ip) | **3/min + 12/hour** |
| OTP resend cooldown | 60s after **successful** send | Unchanged (config `otp.resend_cooldown_seconds`) |
| OTP verify attempts | 5/code + HTTP 10/min | Same + **40/hour** verify HTTP |
| Password reset requests | via `auth-codes` | Same + email key + budget |
| Login attempts | `auth` 10/min | Unchanged |
| Email change | `uploads` 5/min | Unchanged (gap noted) |
| Global OTP outbound budget | **None** | Soft hour/day circuit breaker |
| Header spoof bypass | Untrusted XFF (good) | Tests assert spoof does not bypass |

Cache backend `database` supports shared counters across PHP-FPM workers.

---

## Abuse investigation (2026-10-27 → 2026-10-31, privacy-safe)

| Metric | Value |
|---|---|
| New users | **381** |
| Email-verified | **6** |
| Unverified | **375** (~98.4%) |
| OTP rows | **382** (email_verify **380**, password_reset **2**) |
| OTP consumed | **5** |
| Unique OTP recipients (user_id) | **378** |
| Users with >3 OTPs | **1** (max codes on a user: 4) |
| Password-null (social-only) new users | **0** |
| Locale | EN **379**, AR **2** |
| Top email domains | gmail-heavy, then outlook/hotmail/icloud (aggregated only) |
| Peak hour signups | ≤10 / hour |
| Daily signups | 103 / 96 / 73 / 76 / 33 |
| Baseline Oct 1–26 | **988** users (~**38**/day) |
| Same password-hash clusters (>5) | **0** |

### Interpretation

- Volume and **near-total non-verification** support **automated / low-intent registration** pressure and elevated OTP send volume — a **plausible** relay-lock contributor.
- Patterns do **not** alone prove a single bot farm (no large shared password-hash clusters; hourly peaks moderate).
- **Insufficient evidence** for credential stuffing or classic email bombing of arbitrary third parties via password-reset (only 2 password_reset codes).
- **Do not** treat all unverified accounts as bots; **do not** auto-delete.

**Verdict:** **ABUSE DETECTED = YES** (registration/OTP flood pattern) with lock causality still **INCONCLUSIVE** without provider bounce telemetry.

---

## Confirmed security gaps (live, pre-deploy)

1. Registration throttle shared with login (`auth` 10/min) — too loose for signup farms.
2. No per-recipient email OTP rate limit (case/whitespace variants).
3. No application-level global OTP send budget / soft circuit breaker.
4. Password-reset delivery `DomainException` swallowed with **no** internal warning log (ops-blind).
5. Email normalization inconsistent across some lookup paths (`strtolower` only).
6. Profile email-change OTP not on `auth-codes` (uses `uploads`).
7. Upstream relay may still be locked — app cannot self-heal that.

---

## Proposed security controls (implemented in worktree)

### Layer 1 — IP limits
`auth-register`: 5/min + 20/hour per `$request->ip()`.

### Layer 2 — Email limits
`auth-codes`: +3/min +10/hour keyed by `sha256(EmailNormalizer::normalize(email))`.

### Layer 3 — Resend cooldown
Existing 60s post-success cooldown + issue lock (unchanged semantics).

### Layer 4 — Verify attempt limits
Existing max attempts + HTTP verify limiter tightened with hourly bound.

### Layer 5 — Global outbound OTP budget
`OutboundOtpBudget` via `config/otp.php`: default **120/hour**, **800/day**. Fail closed for **new** sends with generic `auth.code_delivery_failed`; windows roll (no permanent global Deny). Logs `otp_outbound_budget_exceeded`.

### Layer 6 — Turnstile
**Recommended later**, not implemented. Would need web widget + Flutter token pass-through + server verify + replay controls. Separate handoff if approved. Do not rely on client-only CAPTCHA.

---

## OTP lifecycle review (worktree)

| Topic | Status |
|---|---|
| Entropy | 4-digit `random_int` (existing product contract) |
| Storage | bcrypt hash in `email_auth_codes` |
| Expiration | `otp.expires_minutes` (15) |
| Single-use | `consumed_at` set on success / max failures |
| Resend | Invalidates prior unused codes before new issue |
| Concurrency | Cache lock on issue |
| Logging | Delivery failures log class/message; **no plaintext OTP** |
| API leakage | Tests assert responses omit code |

---

## Password reset silent failures

**Before:** `DomainException` caught; public success preserved; **no** server log.  
**After (worktree):** same public contract + `Log::warning('password_reset_delivery_masked', ['user_id', 'exception'])` — no email/OTP/SMTP secrets.

---

## Isolated code changes (worktree only)

| File | Change |
|---|---|
| `app/Support/EmailNormalizer.php` | **New** — trim + lowercase |
| `app/Services/Auth/OutboundOtpBudget.php` | **New** — hour/day soft budget |
| `config/otp.php` | Outbound budget keys |
| `app/Providers/AppServiceProvider.php` | `auth-register`, richer `auth-codes` / verify limiters |
| `routes/api.php` / `routes/web.php` | Register → `throttle:auth-register` |
| `app/Http/Requests/Auth/RegisterRequest.php` | Normalize email pre-validation |
| `app/Services/Auth/EmailAuthCodeService.php` | Budget gate + DI |
| `app/Services/Auth/PasswordResetTokenService.php` | Normalize + masked failure log |
| `app/Http/Controllers/Web/Auth/PasswordResetController.php` | Normalize + masked failure log |
| `tests/Feature/OtpSecurityHardeningTest.php` | **New** security/regression tests |

**Not modified:** Flutter, Admin V3.1, FCM, queue/scheduler, SMTP provider, live `/home/d14098/funnity.com` app code.

---

## Test results (worktree)

Command:

```bash
cd /home/d14098/funnity-otp-security
php artisan test --filter='OtpSecurityHardeningTest|EmailOtpCooldownTest|ApiAuthFlowsTest|AuthRegistrationTest'
```

**Result: 26 passed (175 assertions).** Fake mail + sqlite `:memory:` only. No production DB writes. No real SMTP.

Note: worktree uses its **own** `vendor/` (do **not** symlink to production `vendor` — a mistaken `composer dump-autoload` from a symlink briefly remapped production classmap; **corrected** by regenerating autoload from `/home/d14098/funnity.com`).

---

## Web / Flutter compatibility

- Request/response shapes for register, login, email verify/resend, forgot-password unchanged.
- Cooldown metadata fields preserved.
- New/tighter limits return HTTP **429** (Flutter already maps `RateLimitFailure` / `Retry-After` per existing OTP repair docs).
- Sanctum tokens unaffected.
- **No Flutter code changes.** Separate `docs/flutter/OTP_SECURITY_BACKEND_HANDOFF.md` **not required** for this activation (optional later if Turnstile is approved).

---

## Production activation plan (owner approval required)

1. **Baseline:** live `main` @ `3e91976…`; preserve dirty Marketing/FCM work or commit separately first.
2. **Merge strategy:** cherry-pick / merge `security/otp-hardening` files listed above into a deployment branch; avoid unrelated dirty files.
3. **Config:** optional env `OTP_OUTBOUND_PER_HOUR`, `OTP_OUTBOUND_PER_DAY` (defaults OK).
4. **Migrations:** **none**.
5. **Caches:** config not cached — no clear required for code deploy of PHP files. If `config:cache` is introduced later, rebuild after deploy.
6. **Deploy:** copy/rsync approved files or merge + release; `php-fpm`/Apache reload only if required by host norms.
7. **Smoke (authorized):** one owner OTP to a controlled inbox; confirm receipt; watch `laravel.log` for `OTP email delivery failed` / `otp_outbound_budget_exceeded` / `password_reset_delivery_masked`.
8. **Monitor:** registration 429 rates, OTP issue counts/hour, SMTP 550s, unverified signup ratio.

### Rollback

Revert the listed PHP/config/route files to pre-hardening versions; no DB migration to undo. Budget/rate-limit keys expire naturally in cache.

---

## Hosting provider response draft

> We rotated the `info@funnity.com` mailbox password and updated application SMTP credentials. Laravel is configured for SMTPS to `mail.funnity.com:465` and is reading live environment configuration (no stale config cache).  
> Application logs showed a large volume of new registrations from 27–31 Oct 2026 with a very high share of unverified accounts and corresponding email verification OTP sends — a plausible contributor to antispam relay restrictions, though not proof of the sole cause.  
> Registration and OTP rate limiting already existed; we have prepared (not yet deployed) stronger per-IP registration limits, per-email OTP limits, and an application-level OTP send budget to reduce burst risk. We have **not** claimed CAPTCHA is live.  
> Please confirm whether the `smtp.antispamcloud.com` lock on this mailbox has been lifted and whether bounce/complaint metrics are available for the lock window.

---

## Remaining risks

- Upstream relay may still be locked.
- 4-digit OTP entropy remains a product constraint (mitigated by attempt limits).
- Distributed bots across many IPs can still generate volume under soft budgets.
- Live tree still contains unrelated dirty work — careful merge required.
- Turnstile not yet in place.

---

## Final checklist

| Item | Status |
|---|---|
| Live production code altered this phase | **NONE** |
| Real OTP / bulk email sent | **NONE** |
| Owner approval for deploy / live mail test | **REQUIRED** |
| Next action | Owner: confirm relay unlock with host, then authorize deploy of `security/otp-hardening` + one controlled OTP test |
