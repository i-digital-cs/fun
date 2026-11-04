# Funnity AI Marketing Manager — Master Context

**Audience:** Future implementation agents and technical owners  
**Phase 0:** Complete (audit + design)  
**Phase 1 code:** Implemented + tested  
**Phase 1 production:** **DEPLOYED** on 2026-10-31 (foundation only; execution disabled)

---

## 1. Product principle

**AI recommends. Human approves. System executes.**

No autonomous live campaign mutations.

---

## 2. Current status matrix

| Layer | Status |
|-------|--------|
| Phase 1 repository code | DONE |
| Automated tests | PASS |
| Production migration + seed | **DONE** (batch 13) |
| Platforms seeded | meta, tiktok |
| Connected ad accounts / credentials | **0** |
| Cron / queue worker | **Not activated** |
| Execution / AI | **Disabled** |
| Phase 2 OAuth / sync | **Not started** |
| Git commit/push of Phase 1 | **Not done** (working tree still dirty) |

---

## 3. Production identity

| Item | Value |
|------|-------|
| Path | `/home/d14098/funnity.com` |
| URL | `https://funnity.com` |
| DB | `d14098_fun` |
| Laravel | 12.69.2 |
| PHP | 8.2.35 |
| MariaDB | 11.4.13 |
| Backup | `storage/backups/pre_marketing_phase1_20261031_150510.sql` |

---

## 4. Admin surface

- `/admin/marketing`
- `/admin/marketing/integrations`
- `/admin/marketing/budget-policies`

SuperAdmin only via `marketing.access`.

---

## 5. Deployment docs

| File | Purpose |
|------|---------|
| `MARKETING_PHASE1_PREDEPLOY_AUDIT.md` | Gate audit |
| `MARKETING_PHASE1_PRODUCTION_DEPLOYMENT.md` | What ran in prod |
| `MARKETING_PHASE1_PRODUCTION_QA.md` | Post-deploy QA |
| `MARKETING_PHASE1_ROLLBACK_PLAN.md` | Rollback options |
| `MARKETING_QUEUE_CRON_RUNBOOK.md` | Future cron/queue |

---

## 6. Known production deploy incident

First migrate attempt failed on MariaDB 64-char index limit. Incomplete empty marketing tables were dropped; index renamed to `mkt_cred_platform_type_idx`; migrate re-run successfully. Non-marketing data unchanged.

---

## 7. Next step (do not implement until approved)

1. Commit Phase 1 working tree (owner decision).  
2. Optional: activate Funnity scheduler + bounded queue worker (runbook).  
3. Complete Meta/TikTok account setup checklist.  
4. Authorize **Phase 2** read-only OAuth + sync only after explicit approval.

**Do not start Phase 2 without owner approval.**
