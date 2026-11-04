# Flutter handoff — OTP security backend (2026-10-31)

**Status:** Backend-ready in worktree `security/otp-hardening` — **not deployed**.  
**Flutter code changes:** **None required** for API field compatibility.  
**Client action:** Confirm existing 429 / `retry_after_seconds` handling still covers tighter HTTP throttles after deploy.

---

## Unchanged contracts (compatible)

| Endpoint | Notes |
|---|---|
| `POST /api/register` | Same body/response; email normalized server-side (trim + lowercase) |
| `POST /api/login` | Same; may include `otp` cooldown metadata when unverified |
| `POST /api/email/verify` | Same `{ code }` → success payload |
| `POST /api/email/verify/resend` | Same success fields; **429** + `Retry-After` + `retry_after_seconds` / `resend_available_at` on cooldown |
| `POST /api/forgot-password` | Same enumeration-safe success shape |
| `POST /api/forgot-password/verify` | Same → `reset_token` |
| `POST /api/reset-password` | Same |

OTP plaintext is never returned in JSON. Sanctum tokens unchanged.

---

## Throttle behavior after deploy (expect more 429 under abuse)

| Limiter | Behavior |
|---|---|
| `auth-register` | **5/min + 20/hour** per IP on `/api/register` |
| `auth` | 10/min per IP — login / social |
| `auth-codes` | IP 5/min + 40/hour; normalized email 3/min + 10/hour; authed user 3/min + 12/hour |
| `auth-code-verify` | 10/min + 40/hour per user or IP |
| App cooldown | Still **60s** default after successful send (`retry_after_seconds`) |

**Client must:** treat HTTP **429** as cooldown; prefer `retry_after_seconds` body, else `Retry-After` header; single countdown; disable resend while cooling; do not invent a longer client-only lockout.

---

## Soft outbound budget (server-only)

When abuse-prone or member OTP volume budgets are exceeded, issuance fails with a generic delivery error (web/API may surface as **503** on authenticated resend or masked success on forgot-password). Not a new response schema — reuse existing delivery-failure UX / retry.

Lanes are server-internal (`abuse` vs `member`); Flutter does not send a lane flag.

---

## Required Flutter changes

**None** for this release if current OTP repair builds already:

1. Seed countdown from `otp.retry_after_seconds` / `resend_available_at`
2. Handle resend **429** via `RateLimitFailure.retryAfterSeconds`
3. Show one resend control + one timer

Optional QA after backend deploy: hammer register from one IP and confirm 429 does not break the auth UI.
