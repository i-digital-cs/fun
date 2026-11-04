# Marketing API Integrations — Meta & TikTok (Phase 0)

**Status:** Research + design  
**Sources:** Official Meta / TikTok developer docs (fetched/searched 2026-10-31)  
**Labels:** VERIFIED (from official docs) · ASSUMED · REQUIRES CONFIRMATION · BLOCKED

Funnity currently has **no** Meta/TikTok ads credentials, pixels, or apps in the repository. Production API access is **BLOCKED** until account setup + app review (where required) complete. See `MARKETING_ACCOUNT_SETUP.md`.

---

## 1. Meta Marketing API (Facebook + Instagram)

### 1.1 What Meta covers

One **Ad Account** can deliver to Facebook and Instagram placements. Instagram requires a Professional account linked to a Facebook Page / Business Portfolio. Insights can break down by `publisher_platform`.

### 1.2 Access model (VERIFIED from Meta docs)

| Concept | Detail |
|---------|--------|
| Product | Marketing API on a Meta Developer App |
| Permissions | `ads_read` (insights/reporting), `ads_management` (create/update/manage) |
| Related | Often depends on Page permissions for some flows (`pages_show_list`, `pages_read_engagement` documented as dependencies for `ads_management`) |
| Access tier | **Limited** (default) vs **Full** (after App Review / Marketing API Access Tier upgrade) |
| Full tier threshold (docs) | Reduced qualification: **500** Marketing API calls in past 15 days + error-rate criteria (confirm in App Dashboard) |
| Business Verification | Required for Advanced/Full access to data you do not own/manage |
| System users | Recommended for server automation; app + system user must belong to same Business |
| Tokens | System user tokens are long-lived relative to user tokens; still must be stored encrypted and rotatable |

**Do not assume Full Access is granted.** Own-business development can start under Limited Access with lower rate limits.

### 1.3 Confirmed capability map

| Operation | Typical permission | Notes | Status |
|-----------|-------------------|-------|--------|
| Read campaigns / ad sets / ads | `ads_read` or `ads_management` | Graph objects under `act_{AD_ACCOUNT_ID}` | VERIFIED capability exists |
| Read insights (impressions, clicks, spend, CTR derivatives) | `ads_read` | Insights edge; some metrics estimated/in-development | VERIFIED |
| Read actions/conversions in insights | `ads_read` | Depends on pixel/CAPI/app events configuration | REQUIRES CONFIRMATION per account |
| Update budget | `ads_management` | Budget/bid/schedule changes do **not** re-trigger ad review (Meta best practices) | VERIFIED |
| Pause / resume | `ads_management` | `status` / effective status updates | VERIFIED |
| Create campaign / ad set / ad | `ads_management` | Creative/targeting changes **can** trigger ad review | VERIFIED |
| Conversions API (server events) | Pixel + appropriate access | Separate from Marketing API object CRUD | VERIFIED (separate product) |

### 1.4 Rate limits (VERIFIED at high level)

Meta documents tiered Marketing API rate limits (Limited vs Full) for Ads Management and Insights. Exact numeric quotas change; **REQUIRES CONFIRMATION** in App Dashboard at implementation time. Design must:

- Cache insights aggressively (hourly sync default)
- Use async insights jobs for large ranges when required
- Back off on throttling headers/errors

### 1.5 Instagram-specific notes

- Ads are bought through the Meta Ad Account, not a separate “Instagram Ads API.”
- Breakdown `publisher_platform` separates FB vs IG delivery.
- Requires linked IG Professional account + Page relationship — account setup checklist.

### 1.6 Meta blockers for Funnity today

| Blocker | Impact |
|---------|--------|
| No Business Portfolio / Ad Account | Cannot sync |
| No Developer App with Marketing API | Cannot auth |
| No App Review / verification (if needed for Full) | Lower limits / restricted |
| No Pixel / CAPI | Weak conversion optimization & incomplete platform-reported conversions |
| No System User token plan | Fragile personal-token automation |

---

## 2. TikTok Marketing API

### 2.1 Access model (VERIFIED)

| Concept | Detail |
|---------|--------|
| Portal | TikTok API for Business (`business-api.tiktok.com`) |
| Prerequisites | TikTok For Business account → developer registration → developer app |
| Auth | Advertiser authorization URL → email verification → `auth_code` (1 hour, single use) → long-term access token via `/oauth2/access_token/` |
| Scopes | **Numeric** permission IDs (not OAuth string scopes). Advertiser approves subset selected on the app |
| Important scopes | Ads Management (2) with Campaign/Ad Group/Ad; Reporting (4); Measurement/Pixel; Ad Account info |
| Default rate tier | Basic: **10 QPS / 600 QPM / 864,000 QPD** (app-level); higher tiers require application |
| Throttle code | `40100` |

### 2.2 Capability map

| Operation | Endpoint family (v1.3) | Status |
|-----------|------------------------|--------|
| List campaigns | `GET /open_api/v1.3/campaign/get/` | VERIFIED |
| Create campaign | `POST /campaign/create/` | VERIFIED |
| Update campaign (name/budget/status) | `POST /campaign/update/` | VERIFIED |
| Ad group / ad CRUD | Under Ads Management scopes | VERIFIED (docs) |
| Reporting | `/report/integrated/get/` (sync) + async variants | VERIFIED; needs Reporting scope |
| Pixel / Events API | Pixel management + `/pixel/track` / batch | VERIFIED as separate measurement stack |
| Creative fatigue insight | Special allowlist in some cases | REQUIRES CONFIRMATION / may be allowlist-only |

### 2.3 Limitations vs Meta (design assumptions marked)

| Topic | Meta | TikTok | Design impact |
|-------|------|--------|---------------|
| FB+IG in one account | Yes | N/A (TikTok inventory) | Separate platform rows |
| Permission model | String permissions + App Review | Numeric scopes + advertiser auth | Dual OAuth connectors |
| Insights shape | Graph Insights edge | Integrated report dims/metrics | Provider-specific mappers → unified snapshot DTO |
| Learning / delivery semantics | Meta learning phase | TikTok learning | Rules must be provider-aware |
| Identical status enums | PAUSED/ACTIVE… | ENABLE/DISABLE/DELETE… | Normalize in domain layer |
| Rate limit philosophy | Call-budget tiers | QPS/QPM/QPD | Shared throttle middleware per provider |

**Do not assume** TikTok supports every Meta mutation with the same side effects. Each provider declares capabilities; UI disables unsupported actions.

### 2.4 TikTok blockers for Funnity today

| Blocker | Impact |
|---------|--------|
| No Ads Manager / advertiser account | Cannot authorize |
| No developer app | No `app_id`/`secret` |
| No advertiser authorization completed | No token |
| Reporting scope not granted | Cannot build analytics |
| Pixel not installed | Weak optimization signal |

---

## 3. Unified provider contract (design)

Both adapters map to:

```
RemoteAccount → MarketingAccount
RemoteCampaign → MarketingCampaign
RemoteAdGroup → MarketingAdGroup
RemoteAd → MarketingAd
RemoteInsightsRow → CampaignPerformanceSnapshot
```

Currency, timezone, and objective enums are stored as:

- `provider_raw_*` JSON (exact API payload subset)
- `normalized_*` fields used by KPI + rules

---

## 4. Auth storage (design — no secrets in repo)

| Field | Storage |
|-------|---------|
| App ID / client ID | `config` / env or encrypted DB |
| App secret | Encrypted DB or env (prefer env for app secret; DB for per-account tokens) |
| Access token | Encrypted cast column |
| Refresh / long-lived metadata | Encrypted JSON |
| Token expiry / last refresh | Plain datetime |
| Granted scopes/permissions | JSON array |
| Ad account external IDs | Plain indexed strings |

OAuth callback routes: `/admin/marketing/integrations/{platform}/callback` — SuperAdmin only, CSRF-safe state param, short-lived.

---

## 5. Write-path policy (product)

Even when API allows create/publish:

| Phase | Allowed writes |
|-------|----------------|
| Phase 2 | **None** (read-only sync) |
| Phase 7 | Budget ±%, pause/resume only (default) |
| Later | Targeting/creative create — separate approvals + higher risk tier |

Every write requires Approval + Execution pipeline.

---

## 6. Official doc anchors

- Meta Permissions Reference: https://developers.facebook.com/docs/permissions/
- Meta Marketing API authorization / Access Tier: Meta Marketing API Get Started → Authorization
- Meta System Users: https://developers.facebook.com/docs/marketing-api/system-users/overview/
- Meta Insights: Graph API Ad Account Insights
- TikTok Marketing API Authorization: https://business-api.tiktok.com/portal/docs/marketing-api-authorization/v1.3
- TikTok Rate Limits: https://business-api.tiktok.com/portal/docs/rate-limits/v1.3
- TikTok Get Campaigns / Campaign Update / Integrated Report: TikTok API for Business portal v1.3

Exact Graph/API versions (`v19`/`v26`/etc.) must be pinned in `config/marketing.php` at implementation and reviewed quarterly — **REQUIRES CONFIRMATION** at Phase 2 start.
