# Marketing Implementation Plan (Phase 0 Roadmap)

**Rule:** Do not start Phase 1 until owner approves.  
**Effort unit:** engineer-days (1 day ≈ 6–8 focused hours). Estimates are planning ranges.

---

## Phase 1 — Marketing Foundation

**Scope:** Schema foundation, provider interfaces, admin permissions, config, empty admin section.  
**Dependencies:** Owner approval of Phase 0 docs; SuperAdmin available.  
**Effort:** 4–6 days

### Technical tasks
- Create `config/marketing.php`
- Migrations: platforms, accounts, credentials, budget_policies, audit_logs
- Seed `meta` / `tiktok` platforms
- `MarketingPlatformInterface` + null/fake provider for tests
- Extend `PlatformRole` capabilities (SuperAdmin)
- Admin nav + placeholder Overview Blade (no live data)
- Service/Repository skeletons

### Database
- See MARKETING_DATABASE_DESIGN § Phase 1 tables

### APIs
- None public; optional internal health `GET /admin/marketing` page only

### Admin screens
- Marketing Overview shell; Integration Settings stub

### Tests
- Migration smoke; permission 403 for non-admin; capability unit tests

### Security
- Role gates; no secrets yet

### Acceptance
- SuperAdmin sees Marketing nav; Moderator denied write routes; migrations clean on fresh DB

### Risks
- Over-building UI before accounts exist — keep shells thin

---

## Phase 2 — Advertising Account Integration (read-only)

**Scope:** Meta + TikTok OAuth/token connect; sync accounts/campaigns/ad groups/ads.  
**Dependencies:** Phase 1; completed checklist items for apps/tokens (MARKETING_ACCOUNT_SETUP).  
**Effort:** 8–12 days

### Technical tasks
- Meta + TikTok providers (read capabilities only)
- Encrypted credential storage
- OAuth/connect UI + callback
- Sync jobs + scheduler commands
- Campaign Explorer read views

### Database
- campaigns, ad_groups, ads

### APIs
- Admin web actions only; provider HTTP clients

### Admin screens
- Integration Settings (connect/disconnect/last sync)
- Campaign Explorer list/detail

### Tests
- Provider mappers with fixture JSON; sync idempotency; encrypted cast

### Security
- State CSRF; SuperAdmin-only connect; redacted logs

### Acceptance
- Connected sandbox/prod account lists campaigns without write calls

### Risks
- App Review delays (Meta Full Access); TikTok scope mismatches — **BLOCKED** until accounts exist

---

## Phase 3 — Marketing Analytics

**Scope:** Insights pull, KPI calc, Overview dashboards.  
**Dependencies:** Phase 2 sync working.  
**Effort:** 6–9 days

### Technical tasks
- InsightsSyncService; snapshot upserts
- KpiCalculator (CTR/CPC/CPM/spend)
- Overview + Analytics charts (light theme)
- Stale-data badges

### Database
- performance_snapshots (+ optional rollups)

### Admin screens
- Overview KPIs; Analytics compare; Reports daily table

### Tests
- KPI fixtures; unique snapshot constraints; timezone edge cases

### Security
- No PII in chart payloads beyond admin session

### Acceptance
- Spend/impressions/clicks match Ads Manager within documented tolerance for a test day

### Risks
- Rate limits; estimated Meta metrics — label in UI

---

## Phase 4 — Funnity Conversion Tracking

**Scope:** First-party attribution + optional Pixel/CAPI design implementation.  
**Dependencies:** Phase 3 helpful but can start after Phase 1; privacy/consent update.  
**Effort:** 7–11 days

### Technical tasks
- Capture UTMs / fbclid / ttclid on landing (first-party cookie/visitor id)
- Emit `marketing_conversions` on register, identity complete, event view, join, create
- AttributionService (last non-direct click default)
- Cost-per-registration / join / create KPIs
- Meta Pixel + CAPI + TikTok Pixel/Events **behind consent**
- Dedupe event_id

### Database
- touchpoints, conversions

### Admin screens
- Analytics outcome panels; funnel counts

### Tests
- Attribution unit tests; consent off → no ads egress; dedupe

### Security
- Consent flags; minimize PII; privacy policy alignment

### Acceptance
- Test campaign UTMs attribute a registration end-to-end in staging/prod carefully

### Risks
- Consent law (jurisdiction REQUIRES CONFIRMATION); Flutter deep links UTMs — document web-first

---

## Phase 5 — Recommendation Engine

**Scope:** Rules engine + optional external explanations.  
**Dependencies:** Phases 3–4 data.  
**Effort:** 6–10 days

### Technical tasks
- RecommendationEngine + gates
- Scheduler `marketing:recommend`
- Recommendations UI with evidence
- Feature-flagged ExplanationComposer (external AI)

### Database
- recommendations

### Admin screens
- AI Recommendations

### Tests
- Rule boundary tests; evidence ⊆ metrics invariant; no auto-execute

### Security
- LLM output display-only

### Acceptance
- Recommendations generated with confidence/risk; numbers match snapshots

### Risks
- Premature pause rules — tune thresholds with owner

---

## Phase 6 — Approval Workflow

**Scope:** Propose → approve/reject → expiry.  
**Dependencies:** Phase 5.  
**Effort:** 4–6 days

### Technical tasks
- ApprovalService; hash/nonce/TTL
- Approval Center UI
- Audit events

### Database
- recommendation_approvals

### Admin screens
- Approval Center

### Tests
- Replay/expiry/hash mismatch

### Security
- SuperAdmin-only approve; CSRF; rate limits

### Acceptance
- Cannot approve tampered proposal; expired approvals unusable

### Risks
- UX friction vs safety — keep TTL configurable

---

## Phase 7 — Controlled Execution

**Scope:** Execute approved pause/resume/budget changes only.  
**Dependencies:** Phase 6; `ads_management` / TikTok write scopes; budget policies.  
**Effort:** 7–10 days

### Technical tasks
- ExecutionService + idempotency
- Pre-flight remote drift check
- BudgetPolicyGuard
- Verify-after-write
- Capability rejection for unsupported ops

### Database
- executions

### Admin screens
- Execution History; Budget Control

### Tests
- Idempotent retry; drift → REQUIRES_REAPPROVAL; failure not marked success

### Security
- Full write threat model controls

### Acceptance
- Approved pause reflects in Ads Manager; unapproved path impossible via UI/API

### Risks
- Real spend impact — use tiny budgets; platform account spending limits mandatory

---

## Phase 8 — Reporting & Notifications

**Scope:** Scheduled digests; optional Telegram.  
**Dependencies:** Phases 3–7.  
**Effort:** 3–5 days

### Technical tasks
- Daily/weekly report commands
- Email and/or Telegram notifier (if owner provides bot token later)
- Sync/token health monitors

### Admin screens
- Reports download / history

### Tests
- Report builders with fixtures

### Security
- No tokens in notifications; redact campaign secrets

### Acceptance
- Owner receives daily summary without logging into Ads Manager

### Risks
- Telegram token handling — env encrypted practices

---

## Phase 9 — QA & Production Readiness

**Scope:** Hardening, runbooks, rollout.  
**Dependencies:** Phases 1–8 as adopted.  
**Effort:** 4–7 days

### Technical tasks
- Integration test suite with recorded HTTP fixtures
- Security checklist pass
- Runbooks: rotate token, revoke, incident spend spike
- Feature flags for write path
- Load check on sync jobs

### Acceptance
- Checklist signed; monitoring live; rollback plan (disable writes via config)

### Risks
- Shared host queue starvation — document worker sizing

---

## Suggested sequence

```
Phase 1 → 2 → 3 → 4 → 5 → 6 → 7 → 8 → 9
```

Parallelism: Phase 4 (tracking) can overlap late Phase 2/3 after privacy approval. Phase 8 can start after Phase 3 for read-only digests.

**Total estimate (v1 through controlled execution):** ~46–69 engineer-days excluding App Review calendar time.

---

## Host prerequisites (before Phase 2 jobs)

1. Funnity crontab `schedule:run`  
2. Queue worker strategy  
3. Meta/TikTok apps + tokens  
4. Account spending limits set on platforms  
