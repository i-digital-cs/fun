# OTP delivery / Resend UX & new-event discovery repair

**Date:** 2026-10-31  
**Branch:** `main`  
**Baseline commit:** `350c7ba06810772daa28974b1cb4e57addd7a81b`  
**Working tree:** clean before work; changes left uncommitted (no auto-commit/push).

**Final verdict:** **CONDITIONAL**

---

## OTP

### 1. Registration OTP flow

1. `POST /api/register` (or web `POST /register`) creates the user.
2. `EmailAuthCodeService::issueAndSendIfAllowed` issues a **4-digit** email OTP (`EmailAuthPurpose::EmailVerify`), bcrypt-hashes it in `email_auth_codes`, emails via `AuthCodeMail` (**synchronous**, not queued).
3. API returns Sanctum token + `needs_email_verification: true` + `otp` cooldown metadata.
4. Client opens verify email; user submits `POST /api/email/verify` `{ code }`.

### 2. Login OTP flow

There is **no passwordless login OTP**. Login is email+password. If `email_verified_at` is null, the server auto-issues (or respects cooldown for) the same **email verify** code and gates the client to the verify screen.

Password-reset OTP is a separate purpose (`password_reset`) via `POST /api/forgot-password` / web session flow.

### 3. Actual delivery provider

- **Channel:** email only (no SMS).
- **Mailable:** `App\Mail\AuthCodeMail` → `resources/views/emails/auth-code.blade.php`.
- **Transport:** Laravel mailer from env (`MAIL_MAILER`). Local/example defaults to `log` / `array` in tests. Production host uses SMTP (do not commit secrets).
- **Queue:** `AuthCodeMail` is **not** `ShouldQueue` — send blocks the HTTP request. A successful API response means the mailer accepted the send call, **not** inbox delivery confirmation.

### 4. Root cause of delivery failure (if established)

| Layer | Finding |
|-------|---------|
| App logic | OTP is generated and hashed before `Mail::send`. Delivery exceptions now surface as `auth.code_delivery_failed` **without** starting cooldown so Resend stays usable. |
| Local/dev | `.env.example` uses `MAIL_MAILER=log` — codes go to the log, not a mailbox. |
| Production | Requires live SMTP health (host/auth/TLS/from-domain). **Not verified from this workspace with live sends** (no OTP spam). |

**Server diagnostic handoff (read-only):**

1. Confirm `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS` (values only; do not paste passwords into tickets).
2. `php artisan tinker` → `Mail::raw('probe', fn ($m) => $m->to('you@…')->subject('mail probe'));` on a controlled address.
3. Inspect provider bounce/reject logs for `info@…` / configured from-address.
4. Check `storage/logs/laravel.log` for `OTP email delivery failed` (class name only; code never logged).
5. Confirm no outbound firewall block on SMTP port.

### 5. Root cause of missing Resend button

**Web (confirmed):** Resend lived in `data-footer-theater`, toggled **hidden** on the OTP form stage and only shown during the cinematic theater (`resources/js/auth-access.js` + `access-shell.blade.php`). Users on the normal code-entry form could not see Resend.

**Flutter:** Resend existed but had **no cooldown UX**; disabled only while a request was in flight. Not “missing,” but easy to miss under load/error states. Theater still replaces the form temporarily.

### 6. Backend rate-limit policy

| Control | Mechanism |
|---------|-----------|
| Issuance / resend HTTP | `throttle:auth-codes` — 5/min per user id or IP |
| Verify HTTP | `throttle:auth-code-verify` — 10/min per user id or IP |
| Per user+purpose cooldown | `config/otp.php` → `resend_cooldown_seconds` (**default 60**), atomic `Cache::lock` + cache timestamp |
| Verify brute-force | `otp.max_verify_attempts` (**default 5**) per active code → code consumed |
| Expiry | `otp.expires_minutes` (**default 15**) |
| Enumeration | Forgot-password always returns the same message; cooldown fields are uniform for unknown emails |

Authoritative responses include `retry_after_seconds`, `resend_available_at`, `expires_in_minutes`. Authenticated email-verify resend returns **429** + `Retry-After` during cooldown.

### 7. Flutter countdown behavior

- `OtpResendSection` always renders prompt + button.
- Cooldown label: `Resend code in MM:SS` / AR equivalent.
- State survives navigation via `emailVerifyResendProvider` / `passwordResetResendProvider`.
- Seeded from register/login/resend/`forgot-password` metadata; 429 updates via `RateLimitFailure.retryAfterSeconds`.
- Fallback seed of 60s if metadata missing (matches default config).

### 8. Error handling

| Case | Client behavior |
|------|-----------------|
| Success resend | Info toast/subtitle + restart countdown from server |
| 429 cooldown | Show message; sync countdown |
| Delivery failure (503) | Message; Resend remains available when cooldown is 0 |
| Invalid / expired / max attempts | 422 field/message |
| Network timeout | Existing `NetworkFailure` mapping |

### 9. Exact fixes

- `EmailAuthCodeService` cooldown, lock, delivery catch, verify attempt limits.
- `config/otp.php`; `OtpCooldownException`; `EmailAuthIssueResult`.
- API/Web auth controllers return cooldown metadata; register/login use `issueAndSendIfAllowed`.
- Web: always-visible `data-footer-resend` + JS countdown; theater cancel footer preserved for identity.
- Flutter: OTP meta parsing, resend section, rate-limit retry-after, l10n EN/AR.
- Separate verify throttle name `auth-code-verify`.

### 10. Test evidence

**PHPUnit (PASS):** `EmailOtpCooldownTest`, `ApiAuthFlowsTest`, related auth filters.  
**Flutter (PASS):** `otp_resend_section_test.dart`, `auth_controller_test.dart`, `session_restore_test.dart`, `create_event_controller_test.dart` (Flutter **3.47** / Dart 3.13).  
**Emulator OTP:** **NOT RUN** (no Android emulator session in this Linux environment).

---

## EVENTS

### 1. Event publication rules

- Create always persists **`draft`** (`CreateEventService`).
- Public discovery requires **`published`**.
- No moderation approval queue for publish; owner/manager calls publish.
- Publish requires active category + city (`TransitionEventStatusService`).

### 2. Event creation flow

API `POST /api/events` / web `POST /events` → draft → optional publish dialog (Flutter) / explicit publish action (web).

### 3. Discovery eligibility rules

Shared filter (list/home/map):

- `status = published`
- `starts_at >= now() - 1 day`
- Optional city / radius / category / `when` / text `q`
- Blocked creators excluded for the viewer

Drafts remain visible in **My Events** only.

### 4. Root cause of missing new events

**Primary (by design, not a regression):** event left as **draft** (e.g. Flutter publish dialog dismissed). Drafts never appear in Discover.

Secondary traps: radius/city filters, map city-only without pin, browse date window, Discover `ensureLoaded` skipping refetch after publish (addressed with `markStale()`).

### 5. Cache and filter findings

- No server-side event list result cache for discover rows.
- Nearby city ID cache (10 min) can skew radius scope briefly — not “hide this row forever.”
- Flutter Discover could stay stale after publish → `DiscoverController.markStale()` on successful publish.

### 6. Web / Flutter consistency

| Surface | Create message | Discover |
|---------|----------------|----------|
| Web | “Event created as draft.” | Published only |
| Flutter (fixed copy) | “Event saved as draft” + publish offer | Published only; stale mark after publish |

### 7. Exact fixes

- Flutter l10n: draft vs published wording.
- `DiscoverController.refresh` / `markStale`; create publish marks Discover stale.
- Feature tests: draft excluded until publish; city filter; past-start browse drop.
- Parity doc §2.12a + §10 gap notes.

### 8. Test evidence

**PHPUnit (PASS):** `EventDiscoveryVisibilityTest`, `EventManagementTest` (subset).  
**Emulator create/discover:** **NOT RUN**.

---

## PART C — User’s event from “yesterday”

No event ID/title was provided; no production DB writes or private user inspection performed.

**Need from reporter:** event ID or exact title (+ approximate create time).  
Then read-only check: `status`, `starts_at`, `city_id`, lat/lng, compare to Discover filters.

Do **not** auto-publish.

---

## FINAL

### Git baseline

- Root: `/home/d14098/funnity.com`
- Branch: `main` (tracking `origin/main`)
- Start: clean @ `350c7ba`

### Files changed (high level)

- Backend OTP: service, config, exceptions, API/web auth controllers, rate limiters, routes, lang EN/AR, `auth-access.js`, access-shell + verify blades
- Flutter OTP: remote/repo/controller, resend UI/state, failures/error mapper, l10n
- Events: discover stale mark, create publish wiring, draft/published copy
- Tests: `EmailOtpCooldownTest`, `EventDiscoveryVisibilityTest`, Flutter OTP widget/unit tests
- Docs: this file, `FLUTTER_WEB_PARITY.md`

### Tests

| Suite | Result |
|-------|--------|
| PHPUnit OTP + event visibility + ApiAuthFlows | **PASS** |
| Flutter targeted auth/create tests | **PASS** (SDK 3.47) |
| Full `flutter test` / emulator QA | **NOT RUN** |
| Live SMTP delivery | **NOT VERIFIED** |

### Emulator results

**BLOCKED / not executed** in this environment.

### Backend dependencies

- Working mail transport for real inbox delivery
- Rebuild/publish Vite assets so web `auth-access.js` changes reach production (`npm run build` / deploy pipeline)
- Optional env overrides: `OTP_RESEND_COOLDOWN_SECONDS`, `OTP_EXPIRES_MINUTES`, `OTP_MAX_VERIFY_ATTEMPTS`

### Unverified scenarios

- Production inbox delivery / provider rejects
- Android emulator registration/login/resend/verify
- Specific user event from “yesterday” without ID
- Physical device RTL soak

### Remaining risks

- 4-digit OTP space remains small (pre-existing); mitigated by hash + attempts + throttle
- Sync mail still couples latency to request time
- Vite asset deploy lag can leave web Resend bug live until JS is rebuilt

### Verdict

**CONDITIONAL**

- Resend UX + server cooldown + draft/discover rules are fixed and tested in isolation.
- Cannot claim full **PASS** until: (1) production mail probe confirms delivery, (2) web Vite assets are deployed, (3) emulator OTP + publish→Discover QA completes, (4) the reporter’s event is identified if still “missing.”
