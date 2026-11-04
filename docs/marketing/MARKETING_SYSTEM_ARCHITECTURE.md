# Funnity AI Marketing Manager — Architecture (Phase 0)

**Status:** Design only — no code  
**Principle:** AI recommends → Human approves → System executes  
**Hosting decision:** Existing Laravel application (see audit)

---

## 1. Goals

1. Unify Meta (Facebook/Instagram) and TikTok advertising data under one admin UX.
2. Attribute ad-driven outcomes to Funnity KPIs (registration, join, create).
3. Produce deterministic recommendations with optional LLM explanations.
4. Execute **only** explicitly approved, time-limited, integrity-checked mutations.
5. Keep public site and Flutter unchanged.

---

## 2. Bounded context

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Web (Blade)                        │
│  Overview · Campaigns · Recommendations · Approvals · …     │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP (session + CSRF)
┌───────────────────────────▼─────────────────────────────────┐
│              Marketing Application Layer                     │
│  Controllers → Application Services → Domain Services        │
└───────┬─────────────────┬──────────────────┬────────────────┘
        │                 │                  │
        ▼                 ▼                  ▼
┌──────────────┐  ┌───────────────┐  ┌────────────────────┐
│ Repositories │  │ Provider Ports│  │ Outcome Analytics  │
│ (MySQL SSOT) │  │ Meta / TikTok │  │ (Funnity events)   │
└──────────────┘  └───────┬───────┘  └────────────────────┘
                          │ HTTPS
                          ▼
                 Official Ads APIs
```

Marketing is a **new bounded context** under `app/Services/Marketing`, `app/Domain/Marketing` (or equivalent), and `app/Http/Controllers/Web/Admin/Marketing`.

---

## 3. Component responsibilities

| Component | Responsibility | Must not |
|-----------|----------------|----------|
| `MarketingPlatformInterface` | Capability discovery + normalize API ops | Hide unsupported ops as success |
| `MetaMarketingProvider` | Meta Graph Marketing API adapter | Store raw secrets in logs |
| `TikTokMarketingProvider` | TikTok Business API adapter | Assume Meta-identical semantics |
| `AccountSyncService` | Pull accounts/campaigns/ads | Write to platforms |
| `InsightsSyncService` | Pull spend/impressions/clicks | Invent metrics |
| `AttributionService` | Link UTMs/click IDs → users/actions | Send PII without consent |
| `KpiCalculator` | Deterministic CPC/CPA/etc. | Call LLM for numbers |
| `RecommendationEngine` | Rules + thresholds → proposals | Auto-execute |
| `ExplanationComposer` | Optional LLM prose from verified facts | Fabricate KPIs |
| `ApprovalService` | Propose/approve/reject/expire | Reuse expired approvals |
| `ExecutionService` | Idempotent mutate + verify | Retry non-idempotent blindly |
| `BudgetPolicyGuard` | Enforce local spend/change caps | Assume platform enforces Funnity policy |
| `MarketingAuditLogger` | Immutable audit trail | Log secrets |

---

## 4. Provider abstraction

```php
interface MarketingPlatformInterface
{
    public function platform(): MarketingPlatformCode; // meta|tiktok
    /** @return list<MarketingCapability> */
    public function capabilities(): array;

    public function listCampaigns(MarketingAccount $account): CampaignCollection;
    public function getInsights(MarketingAccount $account, InsightsQuery $query): InsightsCollection;

    public function updateBudget(MutationContext $ctx): MutationResult;
    public function updateStatus(MutationContext $ctx): MutationResult;
    // … create/publish only if capability declared
}
```

Unsupported capability → throw `UnsupportedMarketingCapabilityException` (mapped to admin-visible error). Never return fake success.

Capability examples: `READ_CAMPAIGNS`, `READ_INSIGHTS`, `UPDATE_BUDGET`, `UPDATE_STATUS`, `CREATE_CAMPAIGN`, `CREATE_AD`, `READ_CONVERSIONS`.

---

## 5. Data flows

### 5.1 Read / sync (scheduled)

1. Cron triggers `marketing:sync-accounts` / `marketing:sync-insights`.
2. Queue jobs per account (database queue).
3. Provider fetches remote objects → repositories upsert normalized rows.
4. Snapshots append-only for time series.
5. KPI rollups materialize into daily aggregates (optional table).

### 5.2 Recommendation

1. `marketing:recommend` loads fresh snapshots + Funnity conversions.
2. Rules engine emits `AIRecommendation` rows (`PROPOSED`) with evidence JSON.
3. Optional explanation job attaches human-readable EN/AR text from **frozen evidence**.

### 5.3 Approval → execution

1. Admin opens Approval Center; reviews diff (current vs proposed).
2. Approve stores hash of proposal payload, actor, expiry, nonce.
3. Execute job:
   - Re-hash proposal; mismatch → `REQUIRES_REAPPROVAL`
   - Re-read live platform state; drift → `REQUIRES_REAPPROVAL`
   - Budget policy check
   - Provider mutation with idempotency key
   - Confirm via GET
   - Mark `EXECUTED` or `FAILED` + audit

---

## 6. Mapping to existing Funnity stack

| Existing | Reuse |
|----------|-------|
| `layouts/admin` + sidebar | Marketing screens |
| `EnsurePlatformAdmin` + `PlatformRole` | Gate + new capability methods |
| `AdminAuditLogger` / `admin_audit_logs` | Pattern; prefer dedicated `marketing_audit_logs` for volume/retention |
| Database queue | Sync, recommend, execute jobs |
| Laravel Schedule | After Funnity cron is installed |
| `Crypt` / encrypted casts | Token storage |
| `users`, `events`, `event_members`, `identity_documents` | Outcome KPIs |
| Lang files EN/AR | Admin copy |

---

## 7. Admin information architecture

1. **Marketing Overview** — spend, CPA, active campaigns, pending approvals  
2. **Campaign Explorer** — cross-platform table + detail drawer  
3. **AI Recommendations** — evidence-backed cards  
4. **Approval Center** — approve/reject with reason  
5. **Execution History** — status + provider response summary  
6. **Budget Control** — policies & hard caps  
7. **Integration Settings** — OAuth/connect, health, last sync  
8. **Analytics** — platform vs Funnity outcomes  
9. **Reports** — daily/weekly/monthly exports  

Design constraints: light theme, purple primary `#7138F5`, EN/AR + RTL, accessible controls, IBM Plex (existing) unless owner mandates Tajawal.

---

## 8. Single Source of Truth

| Data | SSOT |
|------|------|
| Live campaign config on platform | Platform API (cache locally with `synced_at` + `remote_etag/hash`) |
| Historical performance for charts | Local `marketing_performance_snapshots` |
| Funnity business outcomes | Local Funnity tables + `marketing_conversions` |
| Approvals / executions | Local marketing tables (never platform) |
| Credentials | Encrypted local credentials table (never frontend) |

Do not treat Meta/TikTok UI screenshots as SSOT. Do not invent spend in LLM output.

---

## 9. Failure & resilience

- Rate limits → exponential backoff + circuit breaker per provider
- Token expiry → mark account `needs_reauth`; pause writes
- Partial multi-object ops → not used in v1; one mutation per approval
- Queue failures → `failed_jobs` + admin alert
- Idempotency keys on executions prevent double spend mutations

---

## 10. Non-goals (Phase 0–7)

- Autonomous budget optimization without approval
- Replacing Ads Manager for creative design studios
- Communities product (no community tables exist yet)
- Member-facing Flutter marketing screens
- Installing third-party SaaS marketing suites
