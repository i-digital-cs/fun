# Marketing Phase 1 — Pre-Deployment Audit

**Date:** 2026-10-31  
**Auditor role:** Independent verification against repository + production host  
**Decision:** Proceed to deploy after gate PASS (with one migration index-name remediation)

---

## Environment (VERIFIED)

| Item | Value |
|------|-------|
| Path | `/home/d14098/funnity.com` |
| Branch | `main` |
| HEAD | `3e919763a46b18496ceae2693453b5bec7a34628` |
| App | Funnity / `https://funnity.com` / `APP_ENV=production` |
| Laravel | 12.69.2 |
| PHP CLI | 8.2.35 |
| MariaDB | 11.4.13 |
| Database | `d14098_fun` @ `127.0.0.1` |
| Queue / Cache / Session | database |
| Disk | ~163G free |
| Funnity cron | None |
| Funnity queue worker | None observed |
| Pending jobs | 2 × `NotifyFriendsOfPublishedEvent` (untouched) |
| Failed jobs | 0 |

---

## Git scope (VERIFIED)

Working tree changes were **Marketing Phase 1 only** (modified shared admin nav/role/bootstrap/routes/seeder/.env.example + new marketing trees + docs/tests).

No unrelated Flutter/Web feature diffs mixed into the deployment scope.

`git diff --check`: trailing blank line warning in `.env.example` (cosmetic; corrected).

---

## Database migration audit

File: `2026_10_31_160000_create_marketing_foundation_tables.php`

| Check | Result |
|-------|--------|
| Only creates marketing_* tables | PASS |
| No DROP of unrelated tables | PASS |
| No alter of users/events/etc. | PASS |
| Table name collisions | PASS (none pre-existed) |
| Only pending migration | PASS (scoped path safe) |
| MariaDB JSON / decimal / FK | PASS |
| Identifier length | **FAIL then FIXED** — auto index name on credentials exceeded 64 chars |

Remediation before successful deploy: renamed index to `mkt_cred_platform_type_idx`, dropped incomplete empty marketing tables from failed first attempt, re-ran scoped migrate.

---

## Security audit

| Check | Result |
|-------|--------|
| SuperAdmin-only `marketing.access` | PASS |
| Moderator/member/guest denied (tests + in-process) | PASS |
| `execution_enabled=false`, `read_only=true`, AI off | PASS |
| No Meta/TikTok HTTP clients in Phase 1 | PASS |
| Credential encrypted cast + hidden | PASS |
| Audit redaction of secrets | PASS |
| No secrets in marketing git paths | PASS |
| CSRF on budget forms | PASS (token field present; web middleware) |

---

## Queue / Cron

Phase 1 does **not** require workers. Cron/workers **not** activated. Pending jobs **not** processed.

---

## Automated tests (isolated SQLite)

| Suite | Result |
|-------|--------|
| Marketing unit+feature | 21 passed |
| AdminPanel + AdminDashboardV2 + AuthRegistration | 22 passed |
| Combined pre-deploy filter | **43 passed / 0 failed** |

---

## Gate matrix

| Requirement | Result |
|-------------|--------|
| Production identity verified | PASS |
| Git change scope isolated | PASS |
| No destructive migration | PASS |
| MariaDB compatibility verified | PASS (after index rename) |
| Backup created and verified | PASS |
| Security audit | PASS |
| Marketing tests | PASS |
| Relevant regression tests | PASS |
| Execution disabled | PASS |
| Cron/Worker unchanged | PASS |
| No unrelated deployment changes | PASS |

**Gate decision: PROCEED**
