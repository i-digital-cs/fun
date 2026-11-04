# Marketing Database Design (Phase 0)

**Status:** Proposed schema — **no migrations in Phase 0**  
**Engine:** MariaDB 11.4 / MySQL driver (existing)  
**Prefix:** `marketing_*` to isolate from core product tables

---

## 1. Existing schema reuse

| Existing | Reuse strategy |
|----------|----------------|
| `users.id` | FK for actors, attributed users |
| `events`, `event_members`, `event_join_requests`, `identity_documents` | Outcome queries — **do not duplicate** |
| `admin_audit_logs` | Pattern only; marketing gets dedicated audit table (higher volume) |
| `jobs` / `failed_jobs` | Execution/sync jobs |
| `site_settings` | **Do not overload** — keep marketing config in marketing tables / `config/marketing.php` |

**No communities tables exist** — community join KPI is deferred until product has communities.

---

## 2. Ownership & retention

| Data class | Owner | Retention (proposed) |
|------------|-------|----------------------|
| Credentials | SuperAdmin | Until revoked; rotate ≤ 90 days |
| Campaign mirrors | System sync | Soft-delete when remote deleted; hard-delete after 365d inactive |
| Performance snapshots | System | Raw daily rows 400d; then monthly rollup |
| Recommendations | System | 180d |
| Approvals / executions | Compliance | **7 years** or legal minimum — immutable |
| Audit logs | Compliance | Same as approvals |
| Attribution touchpoints | Growth | 90–180d PII-minimized |

SSOT rules: see Architecture doc.

---

## 3. Proposed entities

### 3.1 `marketing_platforms`

Static seed: `meta`, `tiktok`.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| code | VARCHAR(32) UNIQUE | meta, tiktok |
| name | VARCHAR(64) | |
| is_enabled | BOOL | |
| capabilities_json | JSON | Declared defaults |

### 3.2 `marketing_accounts`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| platform_id | FK | |
| external_account_id | VARCHAR(64) | act_… / advertiser_id |
| name | VARCHAR(255) | |
| currency | CHAR(3) | |
| timezone | VARCHAR(64) | |
| status | VARCHAR(32) | active, needs_reauth, disabled |
| last_synced_at | TIMESTAMP NULL | |
| meta_json | JSON | Non-secret remote fields |

**Unique:** `(platform_id, external_account_id)`

### 3.3 `marketing_integration_credentials`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| marketing_account_id | FK NULL | Nullable for app-level |
| platform_id | FK | |
| credential_type | VARCHAR(32) | system_user_token, oauth_token, app_secret_ref |
| encrypted_payload | TEXT | Laravel encrypted |
| scopes_json | JSON | |
| expires_at | TIMESTAMP NULL | |
| rotated_at | TIMESTAMP NULL | |
| created_by | FK users | |

Never select this table in Blade; never log payload.

### 3.4 `marketing_campaigns`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| marketing_account_id | FK | |
| external_campaign_id | VARCHAR(64) | |
| name | VARCHAR(255) | |
| objective | VARCHAR(64) | Normalized |
| status | VARCHAR(32) | Normalized |
| provider_status | VARCHAR(64) | Raw |
| daily_budget | DECIMAL(14,2) NULL | In account currency |
| lifetime_budget | DECIMAL(14,2) NULL | |
| remote_updated_at | TIMESTAMP NULL | |
| config_hash | CHAR(64) | For drift detection |
| provider_raw_json | JSON | Trimmed |
| synced_at | TIMESTAMP | |

**Unique:** `(marketing_account_id, external_campaign_id)`  
**Indexes:** `(status)`, `(marketing_account_id, status)`

### 3.5 `marketing_ad_groups`

Similar to campaigns: external id, campaign FK, budget, bid, targeting hash, status, synced_at.

### 3.6 `marketing_ads`

External ad id, ad group FK, creative fingerprint, status, synced_at.

### 3.7 `marketing_performance_snapshots`

Append-oriented fact table.

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| platform_id | FK | |
| marketing_account_id | FK | |
| level | VARCHAR(16) | campaign, ad_group, ad, account |
| entity_type | VARCHAR(32) | |
| entity_id | BIGINT | Local FK polymorphic-ish |
| external_entity_id | VARCHAR(64) | |
| date | DATE | Account TZ date |
| impressions | BIGINT | |
| reach | BIGINT NULL | |
| clicks | BIGINT | |
| spend | DECIMAL(14,4) | |
| currency | CHAR(3) | |
| conversions_platform | DECIMAL(14,4) NULL | Provider-reported |
| raw_metrics_json | JSON | |
| pulled_at | TIMESTAMP | |

**Unique:** `(platform_id, level, external_entity_id, date)`  
**Indexes:** `(date)`, `(marketing_account_id, date)`

### 3.8 `marketing_touchpoints` (attribution intake)

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| visitor_id | CHAR(36) | First-party anonymous id |
| user_id | FK NULL | Set on login/register |
| landed_at | TIMESTAMP | |
| landing_path | VARCHAR(512) | |
| utm_source | VARCHAR(64) NULL | |
| utm_medium | VARCHAR(64) NULL | |
| utm_campaign | VARCHAR(128) NULL | |
| utm_content | VARCHAR(128) NULL | |
| utm_term | VARCHAR(128) NULL | |
| fbclid | VARCHAR(255) NULL | |
| ttclid | VARCHAR(255) NULL | |
| gclid | VARCHAR(255) NULL | |
| referrer | VARCHAR(512) NULL | Truncated |
| consent_analytics | BOOL | |
| consent_ads | BOOL | |

**Indexes:** `(user_id, landed_at)`, `(utm_campaign, landed_at)`, `(visitor_id)`

### 3.9 `marketing_conversions`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| user_id | FK NULL | |
| event_name | VARCHAR(64) | registration, onboarding_complete, event_view, event_join, event_create, … |
| event_id | FK NULL | Funnity event when applicable |
| occurred_at | TIMESTAMP | |
| touchpoint_id | FK NULL | Last/ first-touch policy stored |
| attribution_model | VARCHAR(32) | last_non_direct_click, … |
| marketing_campaign_id | FK NULL | Matched local campaign if any |
| platform_id | FK NULL | |
| value | DECIMAL(14,4) NULL | Optional |
| dedupe_key | VARCHAR(64) UNIQUE | Prevent double count |
| meta_json | JSON | Minimal |

### 3.10 `marketing_budget_policies`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| scope_type | VARCHAR(32) | global, platform, account, campaign |
| scope_id | BIGINT NULL | |
| max_daily_spend | DECIMAL(14,2) NULL | |
| max_monthly_spend | DECIMAL(14,2) NULL | |
| max_budget_increase_pct | DECIMAL(5,2) NULL | Per approval |
| max_budget_decrease_pct | DECIMAL(5,2) NULL | |
| allow_status_pause | BOOL | |
| allow_status_resume | BOOL | |
| allow_create | BOOL default false | |
| currency | CHAR(3) | |
| active | BOOL | |

### 3.11 `marketing_recommendations`

| Column | Type | Notes |
|--------|------|-------|
| id | ULID/BIGINT | Public recommendation id |
| platform_id | FK | |
| marketing_campaign_id | FK NULL | |
| type | VARCHAR(64) | pause, resume, increase_budget, … |
| status | VARCHAR(32) | draft, proposed, … |
| current_config_json | JSON | Frozen |
| proposed_config_json | JSON | Frozen |
| evidence_json | JSON | Verified metrics only |
| expected_impact_json | JSON | Rule-estimated ranges |
| confidence | DECIMAL(5,4) | 0–1 |
| risk_level | VARCHAR(16) | low, medium, high |
| explanation_en | TEXT NULL | |
| explanation_ar | TEXT NULL | |
| config_hash | CHAR(64) | Hash of current+proposed |
| created_at | TIMESTAMP | |
| expires_at | TIMESTAMP | |

### 3.12 `marketing_recommendation_approvals`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| recommendation_id | FK | |
| actor_id | FK users | |
| decision | VARCHAR(16) | approved, rejected |
| reason | TEXT NULL | |
| proposal_hash | CHAR(64) | Must match recommendation config_hash |
| approved_at | TIMESTAMP | |
| expires_at | TIMESTAMP | Short TTL e.g. 60 min |
| consumed_at | TIMESTAMP NULL | On execute |
| nonce | CHAR(36) UNIQUE | One-time |

### 3.13 `marketing_executions`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| recommendation_id | FK | |
| approval_id | FK | |
| idempotency_key | CHAR(64) UNIQUE | |
| status | VARCHAR(32) | executing, executed, failed, cancelled |
| attempt | INT | |
| request_json | JSON | Redacted |
| response_json | JSON | Redacted |
| verified_state_json | JSON | Post-read |
| started_at | TIMESTAMP | |
| finished_at | TIMESTAMP NULL | |
| error_code | VARCHAR(64) NULL | |

### 3.14 `marketing_audit_logs`

| Column | Type | Notes |
|--------|------|-------|
| id | BIGINT PK | |
| actor_id | FK NULL | System null |
| action | VARCHAR(64) | |
| subject_type | VARCHAR(64) | |
| subject_id | BIGINT NULL | |
| ip | VARCHAR(45) NULL | |
| meta_json | JSON | Redacted |
| created_at | TIMESTAMP | No updated_at preferred |

---

## 4. Recommendation lifecycle states

`DRAFT → PROPOSED → APPROVED → EXECUTING → EXECUTED`  
Also: `REJECTED`, `EXPIRED`, `FAILED`, `CANCELLED`, `REQUIRES_REAPPROVAL`

State transitions only via services (not controllers).

---

## 5. Migration plan (future phases — not executed now)

| Phase | Migrations |
|-------|------------|
| 1 | platforms, accounts, credentials, budget_policies, audit_logs + seeds |
| 2 | campaigns, ad_groups, ads |
| 3 | performance_snapshots + optional daily rollups |
| 4 | touchpoints, conversions + users acquisition helper columns **or** touchpoint-only |
| 5 | recommendations |
| 6 | approvals |
| 7 | executions |

Prefer multiple small migrations over one mega-migration.

### Optional additive columns on `users` (Phase 4 decision)

Either:

- **A (preferred):** attribution only via `marketing_touchpoints` / `marketing_conversions` (no user table change), or  
- **B:** nullable `acquisition_source`, `acquisition_campaign`, `first_touchpoint_id` for fast admin filters.

Recommend **A** first to avoid coupling.

---

## 6. Indexing & performance notes

- Snapshot unique key prevents duplicate daily pulls.
- Partitioning not needed at current DB size (~4 MB); revisit if snapshots exceed tens of millions of rows.
- JSON columns for evidence/raw only; filter on typed columns.
- Charset: `utf8mb4_unicode_ci` to match existing.

---

## 7. What we will not store

- Full raw creative binaries in MySQL (use media disk if needed later)
- Plaintext access tokens
- Unhashed emails sent to ad platforms in logs
- Duplicate copies of `event_members` for join counts
