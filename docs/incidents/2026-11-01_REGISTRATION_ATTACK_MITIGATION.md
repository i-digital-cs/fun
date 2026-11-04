# Registration attack mitigation — 2026-11-01

## Status

**Phase A (budgets / locks / OTP-before-create): ACTIVE in production** via config defaults + `.env` registration keys (2026-11-01).

**Web Turnstile enforcement: ACTIVE (2026-11-01).** Production keys non-empty; `TURNSTILE_ENABLED_WEB=true`; `TURNSTILE_ENABLED_API=false`. Live `/register` renders widget (desktop + mobile). Reject paths (missing/invalid/replay) return 422 with no user/OTP. Controlled QA registration succeeded then cleaned up (`turnstile.activate.qa+20261101@funnity.test`).

**API registration (Play testing): KEPT ENABLED.** Stage A hardening (2026-11-01 evening): channel-aware IP budgets, honeypot/digit-name signals, content-length cap — **no new Flutter request fields**. Stage B/C plan: `docs/flutter/API_REGISTRATION_ANTIBOT_STAGED.md`. Owner rejected disabling `/api/register`.

**User-count note:** Active DB is `d14098_fun`. Post–Group-C delete snapshot was `users_total=450` (`~/backups/funnity-pre-delete-full-20261101113441/delete_after_counts.json`). Live count now **114** (all verified/active; no SoftDeletes). Delta **−336** with **no** `admin_audit_logs` rows after the delete window. No user data modified during Turnstile activation work.

## Confirmed entry point

| Evidence | Finding |
|----------|---------|
| Oct 2026 SSL access log | **6,577** `POST /register` vs **3** `POST /api/register` |
| Successful creates (HTTP 200) | **1,351** web register posts in October |
| Peak days | ~100 successful web registers/day (Oct 27–28) |
| IP spread | **212** unique IPs among successful Oct registers; top IPs low single digits |
| User-Agents | Mix of modern Chrome + **legacy AOL/MSIE** fingerprints |
| Nov 1 (partial) | Quieter: dozens of posts, many **419** (CSRF fail) + some **200**/**422** |
| Live DB snapshot (audit time) | **0** registrations in last 24h; **0** unverified pending; OTP sends last 24h = low (resend/reset noise) |

**Conclusion:** Automated abuse primarily hit **web** `POST /register` (password registration → user create → email OTP). API was not the dominant vector in October logs. Source actor/network is **not** attributed beyond log patterns.

## Protection gaps (pre-mitigation)

1. Per-IP `auth-register` (5/min + 20/h) insufficient against **slow, distributed** IP farms.
2. No **global** registration budget → SMTP/OTP volume could climb with unique IPs.
3. User row created **before** OTP capacity was guaranteed (OTP budget only at send time).
4. No anti-bot challenge (Turnstile/CAPTCHA) on web or API.
5. Concurrent same-email races relied mainly on DB unique + validation.

## Mitigations applied in code (Phase A — safe defaults)

| Control | Location | Default | Rollback |
|---------|----------|---------|----------|
| Kill switch | `REGISTRATION_ENABLED` | `true` | set `false` |
| Global hour/day budgets | `config/registration.php` | 40/h, 200/day | raise env or disable via high values |
| Per-IP day budget | same | 40/day | raise `REGISTRATION_IP_PER_DAY` |
| Per-email hour budget | same | 3/h | raise env |
| Require OTP abuse-lane capacity before create | `REGISTRATION_REQUIRE_OTP_BUDGET` | `true` | set `false` |
| Email concurrency lock | `RegistrationAbuseGuard` | on | revert service |
| Shared email keys on `auth-register` limiter | `AppServiceProvider` | 2/min + 5/h email | revert limiter |
| Turnstile verifier + flags | `config/turnstile.php` | **web=false, api=false** | leave flags false |
| Web widget (flagged) | `auth/register.blade.php` | hidden until web flag + site key | flag off |
| Generic errors | `auth.registration_unavailable` / `auth.captcha_failed` | — | — |
| Abuse alerts | `Log::warning` events (`registration_*`, `turnstile_*`) — no passwords/OTP/tokens | — | — |

Gate order: **Turnstile (if channel enabled) → budgets/lock → create user → record budgets → issue OTP**.

## Turnstile design

- `TurnstileVerifier` implements `CaptchaVerifier` (Siteverify, timeout, hostname/action checks, single-use token via cache).
- Credentials: `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY` (env only).
- Fail closed when a channel flag is on but verify fails / misconfigured.
- **Do not** enable `TURNSTILE_ENABLED_API` until Flutter ships a compatible client.

## Flutter compatibility

| Client | Path | Turnstile today |
|--------|------|-----------------|
| Current Flutter | `POST /api/register` via Dio (`auth_remote_source.dart`) — **no** captcha field | Compatible while `TURNSTILE_ENABLED_API=false` |
| Web Blade | Widget + `cf-turnstile-response` when `TURNSTILE_ENABLED_WEB=true` | Needs Cloudflare site/secret + flag |

**Required before API enforcement:** Flutter secure WebView (or attested path) that obtains a Turnstile token; store release; owner flips `TURNSTILE_ENABLED_API`. No permanent UA/version bypass.

## Rollout plan (owner-gated)

### A — Immediate server budgets (this code)

- Impact: caps signup farm + OTP create-before-send gap; Flutter password register still works under budgets.
- Rollback: raise/disable env budgets; `REGISTRATION_REQUIRE_OTP_BUDGET=false`; revert commits if needed.
- Deploy when owner approves PHP/config push (no Flutter store dependency).

### B — Web Turnstile

- Set keys; `TURNSTILE_ENABLED_WEB=true`; `TURNSTILE_EXPECTED_HOSTNAMES=funnity.com,www.funnity.com`.
- Rollback: flag `false`.

### C — Flutter challenge integration

- App update + QA on Android/iOS; document in parity §10.35.

### D — API enforcement

- Only after C verified in production traffic; then `TURNSTILE_ENABLED_API=true`.

## Tests

`php artisan test --filter=RegistrationAbuseMitigationTest` — 15 passed.  
Related OTP/Google suites also green at land time.

## Remaining risks

- Distributed bots under global budget still consume slots (budgets protect SMTP; may briefly delay legit signup under flood).
- Carrier NAT: per-IP day=40 may need tuning.
- Google/Apple signup paths are **not** Turnstile-gated (OAuth attestation separate).
- Cache driver is `database` — locks/increments work; Redis preferred long-term for lock latency.
- Web Turnstile not enabled until owner sets keys/flags.

## Activation checklist (Web Turnstile) — blocked on secrets

1. Admin sets `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` in production `.env` (never commit; never paste into chat).
2. Confirm `TURNSTILE_EXPECTED_HOSTNAMES=funnity.com,www.funnity.com` and `TURNSTILE_EXPECTED_ACTION=register`.
3. Keep `TURNSTILE_ENABLED_API=false`.
4. Set `TURNSTILE_ENABLED_WEB=true`.
5. Clear compiled views so the widget appears: `php artisan view:clear` (config is not cached today).
6. Verify `/register` shows Turnstile; probe missing/invalid token → no user, no OTP.
7. Monitor: `php artisan security:registration-report`.

## CAPTCHA-only rollback (keep Phase A budgets)

```env
TURNSTILE_ENABLED_WEB=false
TURNSTILE_ENABLED_API=false
```

Then `php artisan view:clear`. Do **not** raise/disable `REGISTRATION_*` budgets as part of CAPTCHA rollback.

## Monitoring

```bash
php artisan security:registration-report
php artisan security:registration-report --json --hours=24
```

Reports budget counters, Turnstile flag/key presence (booleans only), user create rates, and anonymized log event tallies. Log lines from PHPUnit (`testing.*`) may inflate counts if tests share `storage/logs/laravel.log`.

## Actions requiring owner / admin

1. ~~Deploy Phase A~~ — verified active on this host.
2. **Provide Cloudflare Turnstile site + secret keys in production `.env`.**
3. Approve enabling `TURNSTILE_ENABLED_WEB=true` after keys are present.
4. Flutter mobile release with Turnstile; then enable `TURNSTILE_ENABLED_API`.
5. Any further user deletion / quarantine (out of scope).
