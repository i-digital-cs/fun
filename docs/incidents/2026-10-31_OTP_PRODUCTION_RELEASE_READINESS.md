# OTP Production Release Readiness — 2026-10-31

**Mode:** Isolated worktree `/home/d14098/funnity-otp-security` · branch `security/otp-hardening`  
**Production path:** `/home/d14098/funnity.com` · `main` @ `3e919763a46b18496ceae2693453b5bec7a34628`  
**Production changes this phase:** **NONE**  
**Prior docs:** `2026-10-31_PRODUCTION_OTP_FCM_INVESTIGATION.md`, `2026-10-31_OTP_SECURITY_HARDENING.md`

---

## Executive summary

Authentication recovery is **code-ready** in the isolated worktree: OTP abuse protections, partitioned outbound mail budget, registration throttles, password-reset ops logging, and a **confirmed fix** for the duplicated web “Resend code in mm:ss” timer.

**Not done without owner approval:** production deploy, Vite publish on live, live OTP send, SpamExperts unlock confirmation.

**Root cause status**

| Issue | Status |
|---|---|
| SpamExperts SMTP 550 lock | Confirmed by host; unlock **UNKNOWN** |
| SMTP password / `.env` update | Done by owner; runtime **VERIFIED** (`smtps://mail.funnity.com:465`, config uncached) |
| Registration/OTP flood (Oct 27–31) | Confirmed pattern (381 signups / 375 unverified) — plausible lock contributor |
| Duplicate web OTP resend timer | **FIXED** in worktree (JS painted countdown on both button + visible `<p>`) |
| Global budget as DoS | **MITIGATED** — abuse vs member lanes; rolling windows |

---

## Security review — OutboundOtpBudget

| Check | Finding |
|---|---|
| Atomic counters | `Cache::add` for TTL seed + `Cache::increment` (atomic on `database`/`redis`) |
| Concurrency | Small overshoot possible under parallel success paths; acceptable |
| Expiration | Hour key `YmdH` / day `Ymd` — **not permanent** |
| Shared cache | Uses app `CACHE_STORE=database` |
| Per-recipient / per-IP | Enforced by `throttle:auth-codes` + cooldown (not inside budget class) |
| Global thresholds | Per-lane hour/day caps |
| Temp mail failures | Failures **do not** call `record()` — no double-count |
| Retries | No uncontrolled mail retries in OTP path |
| **DoS via global budget** | **Addressed:** `OutboundOtpLane::AbuseProne` vs `::Member` — exhausting register flood does **not** block authenticated resend lane |

---

## Rate limiting review

| Endpoint | Limiter | Notes |
|---|---|---|
| Register API/Web | `auth-register` 5/min + 20/h IP | Separate from login |
| Login / social | `auth` 10/min IP | |
| OTP issue/resend / forgot-password | `auth-codes` IP + normalized email + user | 429 from framework |
| OTP verify | `auth-code-verify` | |
| App cooldown | 60s after successful send | 429 + `Retry-After` on API resend |
| X-Forwarded-For bypass | **Rejected** (no trusted proxy spoof in tests) |
| Email case/whitespace | `EmailNormalizer` | |

API JSON contracts preserved.

---

## Duplicate timer — root cause & fix

**Cause:** `resources/js/auth-access.js` `paintResendUi()` set the same “Resend code in mm:ss” string on **both** `[data-auth-resend]` and `[data-resend-cooldown]`, while the cooldown `<p>` was un-hidden (`classList.remove('hidden')`).

**Fix:**
- Button is the **only** visible countdown / resend control.
- `[data-resend-cooldown]` is `sr-only` aria-live (no second visual line).
- Blade comment documents the invariant.

**Evidence:** Feature test `test_otp_verify_page_has_exactly_one_visible_resend_control`; Vite build `auth-access-Cc6TC-sN.js`.

---

## Flutter compatibility

**PASS (contract)** — no Flutter code changes required.  
Informational handoff: `docs/flutter/OTP_SECURITY_BACKEND_HANDOFF.md` (tighter 429 thresholds; same fields).

---

## Test results

```text
php artisan test --filter='OtpSecurityHardeningTest|EmailOtpCooldownTest|ApiAuthFlowsTest|AuthRegistrationTest'
Tests: 28 passed (184 assertions)
```

Isolated sqlite + `MAIL_MAILER=array`. No production DB / SMTP.

---

## Exact changed files (deploy set)

### New
- `app/Enums/OutboundOtpLane.php`
- `app/Services/Auth/OutboundOtpBudget.php`
- `app/Support/EmailNormalizer.php`
- `tests/Feature/OtpSecurityHardeningTest.php`
- `docs/incidents/2026-10-31_OTP_SECURITY_HARDENING.md`
- `docs/incidents/2026-10-31_OTP_PRODUCTION_RELEASE_READINESS.md`
- `docs/flutter/OTP_SECURITY_BACKEND_HANDOFF.md`

### Modified
- `app/Providers/AppServiceProvider.php`
- `app/Services/Auth/EmailAuthCodeService.php`
- `app/Services/Auth/PasswordResetTokenService.php`
- `app/Services/Profile/UpdateProfileService.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Controllers/Api/Auth/EmailVerificationController.php`
- `app/Http/Controllers/Web/Auth/EmailVerificationController.php`
- `app/Http/Controllers/Web/Auth/PasswordResetController.php`
- `config/otp.php`
- `routes/api.php`
- `routes/web.php`
- `resources/js/auth-access.js`
- `resources/views/components/auth/access-shell.blade.php`

### Build artifact (gitignored — must rebuild on deploy)
- `public/build/**` via `npm run build` (new `auth-access-*.js` hash)

### Do **not** deploy from this branch
- Live dirty Marketing / FCM / Admin nav edits on `main` working tree
- `error_log` deletion in worktree

---

## Conflicts with production work

Live `/home/d14098/funnity.com` has **unrelated** dirty files (Marketing foundation, FCM job, admin nav, `routes/web.php`, etc.).

**Deploy method:** copy **only** the file list above from the worktree into production (or cherry-pick commits limited to those paths).  
**Do not** `git reset` / merge whole dirty trees blindly.  
`routes/web.php` on live is also dirty — merge the **register throttle line** carefully without dropping Marketing routes.

---

## Configuration / migrations

| Item | Required? |
|---|---|
| DB migrations | **None** |
| Env (optional) | `OTP_OUTBOUND_PER_HOUR`, `OTP_OUTBOUND_PER_DAY`, `OTP_OUTBOUND_MEMBER_PER_HOUR`, `OTP_OUTBOUND_MEMBER_PER_DAY` — defaults OK |
| `config:clear` | Only if config was cached (currently **not**) |
| Vite | **`npm run build`** on production after PHP/Blade/JS copy |

---

## Deployment commands (owner-approved only)

```bash
# 0) Backup
cd /home/d14098/funnity.com
tar -czf ~/backups/funnity-otp-$(date +%Y%m%d%H%M%S).tgz \
  app/Providers/AppServiceProvider.php \
  app/Services/Auth \
  app/Http/Requests/Auth/RegisterRequest.php \
  app/Http/Controllers/Api/Auth/EmailVerificationController.php \
  app/Http/Controllers/Web/Auth \
  app/Services/Profile/UpdateProfileService.php \
  config/otp.php routes/api.php routes/web.php \
  resources/js/auth-access.js \
  resources/views/components/auth/access-shell.blade.php \
  public/build

# 1) Copy approved files from worktree (example)
WT=/home/d14098/funnity-otp-security
# ... rsync/cp each path in Exact changed files ...

# 2) Frontend
npm ci || npm install
npm run build

# 3) No migrate. Optional:
# php artisan config:clear   # only if config cached

# 4) Smoke (no bulk mail)
# - Load /email/verify while logged-in unverified: ONE "Resend code in …" line
# - POST /api/register ×6 from one IP → HTTP 429 on 6th
```

### Rollback

Restore tarball files + prior `public/build`. No DB rollback.

---

## Post-deploy verification

1. Rate limiters: 6th register → 429  
2. Resend cooldown: second resend → 429 + single UI timer  
3. Duplicate timer gone on OTP page  
4. Legitimate register still 201 + otp metadata  
5. Existing Sanctum/session cookies still work  
6. SMTP runtime still `smtps://mail.funnity.com` / `info@funnity.com`  
7. Watch `laravel.log` for `otp_outbound_budget_exceeded`, `OTP email delivery failed`, `password_reset_delivery_masked`  
8. After host unlocks SpamExperts: **one** owner-authorized OTP receive + verify  

---

## Hosting provider reply (draft)

> We rotated the `info@funnity.com` mailbox password and updated the application SMTP credentials. Laravel sends via SMTPS to `mail.funnity.com` on port 465 and is reading live environment configuration (no stale config cache).  
> We prepared stronger registration/OTP rate limits and outbound OTP volume protections; these are ready to activate on the application and are **not yet claimed as live** until we deploy.  
> Please confirm the SpamExperts / `smtp.antispamcloud.com` outbound lock for this mailbox is cleared so we can run a single controlled delivery test.

---

## Required approvals

1. Owner: deploy file set + `npm run build` on production  
2. Host: SpamExperts unlock confirmation  
3. Owner: authorize one live OTP test after unlock  

**STOP — no live deployment performed.**
