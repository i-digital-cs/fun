# Marketing Security Review (Phase 0)

**Scope:** Threat model and controls for Funnity AI Marketing Manager  
**Status:** Design — no credentials connected

---

## 1. Assets

| Asset | Sensitivity |
|-------|-------------|
| Ad platform access tokens / app secrets | Critical |
| Campaign budgets & ability to spend money | Critical |
| Performance + conversion analytics | High |
| User attribution identifiers (click ids, user ids) | High / personal data |
| Approval records | High (integrity) |
| Admin sessions | High |

---

## 2. Threat model (STRIDE-lite)

| Threat | Example | Mitigation |
|--------|---------|------------|
| Spoofing | Stolen admin session approves spend | Session auth + SuperAdmin role + optional step-up confirm; short approval TTL |
| Tampering | Modify proposal after approval | `proposal_hash` + nonce; re-hash at execute; drift check |
| Repudiation | “I didn’t approve that” | Immutable `marketing_audit_logs` + approval rows with actor_id, IP, timestamps |
| Information disclosure | Token in logs/UI | Encrypted storage; redaction; no Blade dump of credentials |
| Denial of service | Sync job storms / API ban | Rate limiters; per-account locks; backoff |
| Elevation | Moderator executes ads | New capabilities SuperAdmin-only initially |
| Confused deputy | Token used on wrong ad account | Bind credential ↔ account; allowlist external account IDs |
| Replay | Reuse approval | `consumed_at`, unique nonce, expiry |
| Prompt injection (LLM) | Explanation tries to instruct execute | Explanations are display-only; execution path ignores LLM text |

---

## 3. Authentication & authorization

### Admin

- Existing: session + `EnsurePlatformAdmin` + `PlatformRole`
- Add: `canManageMarketingIntegrations()`, `canViewMarketing()`, `canApproveMarketingMutations()`, `canExecuteMarketingMutations()`
- Default: **SuperAdmin only** for connect / approve / execute
- Moderators: optional read-only later

### OAuth / platform connect

- `state` CSRF param tied to admin session
- Callback rejects mismatched state
- Store tokens server-side only
- Separate Meta system user token vs TikTok advertiser token flows

### API

- Any JSON admin marketing endpoints: same session middleware (web) or Sanctum **admin** token with explicit ability — prefer session web for mutations to keep CSRF

---

## 4. Credential handling

| Control | Requirement |
|---------|-------------|
| At rest | Laravel encrypted casts / `Crypt::encryptString` |
| In transit | HTTPS only |
| In logs | Redact `access_token`, `secret`, Authorization headers |
| Rotation | Documented runbook; `rotated_at` field |
| Least privilege | Meta: prefer `ads_read` until Phase 7; then scoped system user with ANALYZE/ADVERTISE as needed — avoid over-broad Page perms |
| Separation | App secret in env; per-account tokens in DB |

---

## 5. Approval integrity

1. Proposal frozen at `PROPOSED`  
2. Approval stores `proposal_hash` identical to recommendation `config_hash`  
3. TTL (suggested 30–120 minutes)  
4. Single-use nonce  
5. On execute: verify actor authorization, hash, expiry, unconsumed, budget policy, live remote `config_hash`/fields  
6. Any mismatch → `REQUIRES_REAPPROVAL` (not silent continue)  
7. Execution idempotency key = `hash(approval_id + recommendation_id + mutation)`  

---

## 6. Budget safeguards & platform limitations

### Local (Funnity) controls — enforceable

- Max daily/monthly spend **as observed from synced snapshots** (reactive)
- Max % increase/decrease per approval
- Disallow create/publish until policy allows
- Circuit breaker: auto-disable executions if spend velocity anomaly

### Platform limitations — **cannot fully prevent**

| Limitation | Impact |
|------------|--------|
| Humans can change budgets in Ads Manager UI | Local policy does not stop external edits |
| Billing is charged by Meta/TikTok | Funnity cannot hard-cap card charges at processor level via Marketing API alone |
| Delayed insights | Spend may exceed “daily” local view before sync |
| Multiple tokens/users on same ad account | Other tools can mutate campaigns |
| Race between approve and execute | Mitigated by re-read; not eliminated |

**Document to owner:** Budget policies are **application-level guardrails**, not a payment hard stop. Set platform account spending limits inside Meta/TikTok Business settings as the real ceiling.

---

## 7. CSRF, sessions, rate limits

- Admin Blade forms: existing CSRF token  
- Approval POST endpoints: CSRF + method spoofing standards  
- Rate limit approve/execute routes per admin  
- Sync jobs: unique locks (`cache` locks / `withoutOverlapping`)

---

## 8. Privacy & ads measurement

- Do not send email/phone to CAPI/Events API without lawful basis + consent flags  
- Prefer event IDs + fbp/ttclid where consent allows  
- Hashing PII is **not** legal authorization by itself  
- Honor `consent_analytics` / `consent_ads` on touchpoints  
- Align with published Privacy Policy updates before enabling pixels  
- Dedupe browser pixel + server events via `event_id`

---

## 9. Audit & monitoring

Log (redacted): connect, disconnect, sync, recommend, approve, reject, execute, fail, policy change.

Alert (Phase 8): Telegram/email on failed execution, token expiry, policy breach attempt, spend spike.

---

## 10. Secure coding checklist (implementation)

- [ ] No secrets in git  
- [ ] Encrypted credentials  
- [ ] Capability checks on every provider write  
- [ ] Idempotent execution  
- [ ] Tests for hash mismatch / expiry / replay  
- [ ] Admin unauthorized → 403  
- [ ] LLM output never parsed as executable config  
- [ ] SQL injection avoided via Eloquent bindings  
- [ ] Mass assignment guarded on marketing models  
