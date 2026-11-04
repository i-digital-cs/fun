# Marketing Recommendation & AI Engine (Phase 0)

**Core rule:** Numerical KPIs and recommendations are **deterministic**. Optional AI only **explains** verified evidence. AI never invents metrics and never executes.

---

## 1. Option comparison (tied to audited host)

| Option | What | Host fit (15 GB RAM, multi-app, no Docker, Ollama daemon down) | Cost | Recommendation |
|--------|------|------------------------------------------------------------------|------|----------------|
| **A — Rules engine** | PHP services + SQL aggregates + thresholds | Excellent | ~$0 software | **Primary — REQUIRED** |
| **B — Local AI (Ollama)** | Local LLM for explanations | Binary present (`0.13.4`) but **not running**; shared host; models need several GB RAM | Electricity/CPU contention | **Not for production v1** |
| **C — External AI** | Optional OpenAI/compatible API for EN/AR explanations | Outbound HTTPS reachable (`api.openai.com` responded) | Pay-per-token | **Optional Phase 5+ feature flag** |

**Preferred architecture:** A always on; C optional behind `MARKETING_AI_EXPLANATIONS=off|external`; B deferred to a dedicated VPS if ever required.

---

## 2. Deterministic analytics layer

### Inputs (verified only)

- Local snapshots: spend, impressions, reach, clicks
- Derived: CTR, CPC, CPM
- Funnity conversions: registrations, joins, creates (attributed)
- Derived: CPR, CPJ, CPCREATE, cost per activated user
- Campaign age, objective, budget, status
- Sample size (clicks/impressions/conversions counts)
- Data freshness (`pulled_at`)

### Forbidden

- LLM-generated spend/clicks
- Treating platform estimated metrics as exact without labeling
- Classifying failure from high CPC alone

---

## 3. Safeguards & gating rules

A recommendation may be emitted only if gates pass:

| Gate | Example |
|------|---------|
| Min sample | ≥ N clicks **or** ≥ M impressions over window (configurable) |
| Learning period | Skip aggressive pause/increase while `in_learning` / age < X days |
| Attribution delay | Evaluate conversions with lag (e.g. exclude last 24–48h) |
| Freshness | Snapshot age ≤ stale threshold |
| Objective alignment | Traffic campaigns judged on CTR/CPC; conversion campaigns on CPR/CPJ |
| Budget policy | Proposed change within `marketing_budget_policies` |
| Historical baseline | Compare to own 7/14/28d trailing windows |
| Discrepancy check | If platform conversions ≫ local attributed by factor K → emit **investigate tracking**, not scale spend |

---

## 4. Recommendation catalog (v1)

| Type | Trigger (illustrative) | Risk |
|------|------------------------|------|
| `pause_inefficient` | Spend ≥ floor AND conversions = 0 over mature window AND sample OK | Medium |
| `reduce_budget` | CPA ≫ target band for mature window | Medium |
| `increase_budget` | CPA ≤ target AND stable ≥ W days AND headroom under policy | High |
| `investigate_cvr_drop` | CTR stable, CVR down vs baseline | Low |
| `creative_fatigue` | Frequency/CTR decay if metric available; else inconclusive | Low |
| `tracking_anomaly` | Platform vs local mismatch | High (no spend up) |
| `insufficient_data` | Informational — do not pause | Low |
| `cross_platform_compare` | Same objective, divergent CPA | Low |

All thresholds live in `config/marketing.php` / DB policy — not hard-coded magic in controllers.

---

## 5. Recommendation payload contract

Every recommendation row must include:

1. Recommendation ID  
2. Platform  
3. Campaign (and ad set if relevant)  
4. Current configuration (JSON)  
5. Proposed configuration (JSON)  
6. Supporting evidence (JSON of **named verified metrics + windows**)  
7. Expected impact (rule-based range or “unknown”)  
8. Confidence (0–1 from sample size / variance heuristics)  
9. Risk level  
10. Created at / expires at  

`config_hash = sha256(canonical_json(current + proposed + evidence_ids))`

---

## 6. Optional explanation composer (Option C)

```
Input: frozen evidence_json + recommendation type + locale
Output: explanation_en / explanation_ar
Prompt constraints:
  - Use only provided numbers
  - If missing data, say insufficient data
  - No calls to action that bypass approval
```

Store model name, prompt version, and token usage for cost audit. On API failure, keep recommendation without explanation.

---

## 7. Why not Ollama on this server (evidence)

| Fact | Implication |
|------|-------------|
| VERIFIED: Ollama client cannot connect to a running instance | Not operational |
| VERIFIED: Host runs multiple Laravel apps + httpd | RAM/CPU contention risk |
| VERIFIED: Docker unavailable | Harder to isolate GPU/CPU quotas |
| Lightweight models still need GBs of RAM when loaded | May pressure shared 15 GB host |
| Marketing explanations are intermittent, low volume | External API cheaper/safer |

Revisit local AI only with a **dedicated** host and capacity plan.

---

## 8. Quality & testing strategy

- Unit tests for KPI formulas (fixed fixtures)
- Unit tests for each rule with boundary samples
- Property: recommendation evidence metrics ⊆ snapshot metrics
- Property: engine never flips status to EXECUTED
- Golden files for explanation prompts (no live API in CI)

---

## 9. Observability

- Count recommendations by type/day
- Approval rate / rejection reasons
- Execution success rate
- Explanation cost/day (if external AI enabled)
- Sync lag per account
