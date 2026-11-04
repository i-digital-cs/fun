# API registration anti-bot — staged plan (Flutter + Laravel)

**Owner constraint:** Keep `POST /api/register` enabled for Google Play testing. Do **not** require new body fields from currently installed builds. Do **not** enable `TURNSTILE_ENABLED_API` until Stage C is explicitly approved.

## Stage A — live now (server-only, backward compatible)

Shared gate for Web + API (`RegisterUserService` → `RegistrationSecurityGate`):

1. Channel Turnstile (web only today)
2. Observable signals (honeypot if filled, digit-only names, content-length, optional disposable list)
3. Budgets + email concurrency lock + OTP capacity
4. Create user → record budgets → issue OTP

Flutter request contract **unchanged**:

```json
{ "name", "email", "password", "password_confirmation", "sex", "locale?" }
```

## Stage B — Flutter app update (owner-approved build)

**Recommended primary:** Cloudflare Turnstile in a **dedicated registration WebView / in-app browser challenge** that returns a token to Dart, then send as `cf-turnstile-response` or `turnstile_token` (or `X-Turnstile-Token`). Server already verifies via Siteverify.

**Why Turnstile first:** already production-proven on web; one server verifier; works for Android + iOS with the same API field.

**Secondary (Android):** Play Integrity token verified server-side (Google Integrity API). Good attestation, but Android-only and more backend work.

**Secondary (iOS):** App Attest / DeviceCheck — iOS-only; pair with Play Integrity if dual-stack.

**Do not:** hardcode shared secrets in the app; trust a client `captcha_passed: true`; use permanent User-Agent / version bypasses.

### Flutter work (Stage B checklist)

1. Add challenge UI before/during register (WebView Turnstile widget with action `register`).
2. Pass token on `POST /api/register` without removing existing fields.
3. Handle 422 `auth.captcha_failed` and 429 `auth.registration_unavailable`.
4. QA on Play track devices + iOS TestFlight if applicable.
5. Document min app version in release notes (informational only — enforcement is server flag, not UA).

## Stage C — enforce API Turnstile (owner approval required)

1. Confirm Stage B build is what testers/reviewers run.
2. Set `TURNSTILE_ENABLED_API=true` (keep web on).
3. Monitor `registration_captcha_rejected` + successful `/api/register`.
4. Rollback: `TURNSTILE_ENABLED_API=false` only (keep Stage A budgets/signals).

## Explicitly rejected

- Disabling `/api/register`
- Killing registration globally for Play testing
- Deleting/deactivating tester accounts
- Country-wide IP bans as the primary control
