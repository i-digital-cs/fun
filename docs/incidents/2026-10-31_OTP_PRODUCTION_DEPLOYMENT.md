# OTP Production Deployment — 2026-10-31

**DEPLOYMENT:** SUCCESS  
**Authorization:** Owner-authorized production OTP recovery deploy  
**Executed:** 2026-10-31 ~17:12–17:14 UTC  

---

## Executive summary

Approved OTP security and UI fixes from worktree `/home/d14098/funnity-otp-security` (`security/otp-hardening`) were deployed into `/home/d14098/funnity.com` with path-scoped copies, a surgical `routes/web.php` patch (Marketing routes preserved), autoload refresh, and `npm run build`.

Isolated regression tests: **28 passed / 184 assertions** (sqlite `:memory:`, fake mail).

**SMTP credentials/transport were not changed.** Relay unlock remains **UNKNOWN**. Full authentication recovery is **not claimed** until one owner-authorized OTP is received and verified.

---

## Baseline

| Item | Value |
|---|---|
| Production path | `/home/d14098/funnity.com` |
| Branch | `main` |
| HEAD | `3e919763a46b18496ceae2693453b5bec7a34628` |
| Backup | `/home/d14098/backups/funnity-otp-deploy-20261031171245` |
| Dirty files preserved | **YES** (Marketing, FCM, Admin nav, bootstrap, seeders, etc.) |

---

## Files deployed

### New
- `app/Enums/OutboundOtpLane.php`
- `app/Services/Auth/OutboundOtpBudget.php`
- `app/Support/EmailNormalizer.php`
- `tests/Feature/OtpSecurityHardeningTest.php`

### Replaced from worktree
- `app/Providers/AppServiceProvider.php`
- `app/Services/Auth/EmailAuthCodeService.php`
- `app/Services/Auth/PasswordResetTokenService.php`
- `app/Services/Profile/UpdateProfileService.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Controllers/Api/Auth/EmailVerificationController.php`
- `app/Http/Controllers/Web/Auth/EmailVerificationController.php`
- `app/Http/Controllers/Web/Auth/PasswordResetController.php`
- `config/otp.php`
- `routes/api.php` *(register moved to `throttle:auth-register` only)*
- `resources/js/auth-access.js`
- `resources/views/components/auth/access-shell.blade.php`

### Surgical patch only
- `routes/web.php` — `POST /register` middleware `throttle:auth` → `throttle:auth-register`  
  Marketing admin routes and all other dirty content **preserved**.

### Frontend build
- `npm run build` → manifest entry `resources/js/auth-access.js` → `assets/auth-access-Cc6TC-sN.js`
- Live `/login` serves `https://funnity.com/build/assets/auth-access-Cc6TC-sN.js`
- Prior `public/build` retained in backup

### Docs (already present / updated)
- `docs/incidents/2026-10-31_OTP_PRODUCTION_DEPLOYMENT.md` (this file)
- Prior readiness / hardening / Flutter handoff docs under `docs/`

### Explicitly NOT deployed
- FCM / Queue / Scheduler / Marketing app code / Admin V3.1 / Flutter / SMTP `.env`

---

## Verification

| Check | Result |
|---|---|
| PHP syntax (deployed classes) | PASS |
| Autoload (`EmailNormalizer`, `OutboundOtpBudget`, `OutboundOtpLane`) | PASS |
| `routes/web.php` patch | **PASS** — `auth-register` on web register; marketing routes listed |
| `routes/api.php` register | **PASS** — `ThrottleRequests:auth-register` |
| Limiters registered | `auth`, `auth-register`, `auth-codes`, `auth-code-verify` |
| OTP UI markup | 1× `data-auth-resend`, 1× `data-resend-cooldown` with `sr-only` |
| Vite bundle | Does **not** `classList.remove('hidden')` on cooldown |
| Live login asset | `auth-access-Cc6TC-sN.js` |
| SMTP | UNCHANGED — `smtps://mail.funnity.com`, user/from `info@funnity.com`, config uncached |
| Isolated tests | **28 passed / 184 assertions** |
| New production ERROR lines | None observed (test run wrote `testing.WARNING` lines to `laravel.log` — expected from PHPUnit) |

---

## Rollback

```bash
BACKUP=/home/d14098/backups/funnity-otp-deploy-20261031171245
PROD=/home/d14098/funnity.com
# Restore overwritten files + previous Vite build from $BACKUP
# Remove new-only classes if rolling back fully:
#   app/Enums/OutboundOtpLane.php
#   app/Services/Auth/OutboundOtpBudget.php
#   app/Support/EmailNormalizer.php
#   tests/Feature/OtpSecurityHardeningTest.php
cd "$PROD" && composer dump-autoload -o
```

Do **not** `git reset --hard`.

---

## Hosting note

Security hardening and rate limits are **now live** in the application. SpamExperts outbound unlock is still required for inbox delivery.

---

## Next action

1. Ask host to confirm SpamExperts / `smtp.antispamcloud.com` unlock for `info@funnity.com`.  
2. Owner-authorize **one** controlled OTP receive + verify.  
3. Visually confirm OTP page shows a **single** “Resend code in mm:ss” line.
