# Marketing Cost Analysis (Phase 0)

**Goal:** Avoid new recurring marketing-SaaS subscriptions; prefer Laravel + official APIs.

Costs below separate **software**, **hosting**, **AI**, and **ad spend**. Currency: USD unless noted. Figures are planning estimates — **REQUIRES CONFIRMATION** against live provider pricing.

---

## 1. Software costs

| Item | Recurring? | Est. monthly | Notes |
|------|------------|--------------|-------|
| Laravel / PHP / MariaDB | No (already owned) | $0 | VERIFIED existing |
| Meta Marketing API | No API seat fee for standard advertiser use | $0 | Ad spend separate; App Review free |
| TikTok Marketing API | No SaaS fee for standard developer use | $0 | Ad spend separate |
| Open-source PHP HTTP client (Guzzle already via Laravel) | No | $0 | Avoid new SDKs unless needed |
| Third-party Ads Manager SaaS (Supermetrics, Madgicx, etc.) | Yes | Avoided | Explicitly out of scope |
| Charting | Prefer CSS/SVG/Chart.js OSS | $0 | |

**Software verdict:** Incremental software subscription cost ≈ **$0** if Option A (+ optional pay-as-you-go AI).

---

## 2. Hosting costs

| Item | Current | Incremental for Marketing Manager |
|------|---------|-----------------------------------|
| App server | Existing StableServer host (15 GB RAM, 8 CPU) | $0 if stays on same account |
| MySQL storage | ~4 MB DB today | Snapshots grow; still likely <$1/mo equivalent on current disk (163G free) |
| Queue/cron | Must enable Funnity cron/worker | $0 (ops time only) |
| Separate VPS | Not required for v1 | Future $5–40/mo if isolating AI/workers |
| Docker | Unavailable | N/A |

**Hosting verdict:** **No new hosting subscription required** for Phases 1–7 using Option A on existing Laravel.

---

## 3. AI costs

| Option | Infra | Monthly software | Ops risk |
|--------|-------|------------------|----------|
| A Rules only | Existing PHP | **$0** | Low |
| B Ollama local | Needs running daemon + model RAM | $0 license; **hidden** CPU contention on shared host | **High on this host — not recommended** |
| C External LLM | HTTPS API | Example: few thousand explanation calls/mo → often **<$5–30** if terse prompts | Low if feature-flagged |

**AI verdict:** Ship **A**. Enable **C** only when explanations add clear admin value. Do not deploy **B** on current shared host.

---

## 4. Advertising spend (separate)

Advertising budget is **not** a software cost. Meta/TikTok charge for delivery.

| Control | Owner |
|---------|-------|
| Campaign budgets | Ads Manager + Marketing Manager approvals |
| Account spending limits | Set in Meta Business / TikTok Ads finance settings |
| Funnity policy caps | Application guardrails (not a payment hard stop) |

Phase 0 **must not** spend money. Phase 7 writes still spend only when humans approve.

---

## 5. Hidden / setup costs (non-subscription)

| Item | Type | Notes |
|------|------|-------|
| Business verification (Meta) | Time / possible document prep | May be required for Full Access |
| Developer / marketer hours | Labor | Dominant cost |
| Domain / HTTPS | Existing | funnity.com |
| Pixel / CAPI engineering | Labor | Phase 4 |
| App Review delays | Schedule risk | Plan buffer |

---

## 6. Cost comparison summary

| Approach | Software $/mo | Hosting $/mo | AI $/mo | Fits owner goal? |
|----------|---------------|--------------|---------|------------------|
| Rules + Laravel (recommended) | 0 | 0 incremental | 0 | Yes |
| Rules + external explanations | 0 | 0 | ~0–30 | Yes |
| Rules + Ollama on this host | 0 | 0 but risk | 0 | No (ops risk) |
| Buy marketing SaaS | 50–500+ | 0 | bundled | No |

---

## 7. Recommendation

Implement Marketing Manager entirely on **existing Funnity Laravel + MariaDB + database queue**, with **official Meta/TikTok APIs**, **rules-based recommendations**, and **optional** external LLM explanations. Keep advertising spend as a **separate, human-controlled** budget.
