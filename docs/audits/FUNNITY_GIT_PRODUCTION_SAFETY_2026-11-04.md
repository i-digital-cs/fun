# Funnity — Production Git Safety & Change Inventory

**Date:** 2026-11-04  
**Phase:** Inventory & planning only (no deploy, no cleanup, no Git mutations by this audit)  
**Project:** `/home/d14098/funnity.com`  
**Related:** Queue Phase 1 activation (worker live); prior dirty-tree audit `FUNNITY_PRODUCTION_AUDIT_2026-11-03.md`

---

## Executive summary

**VERDICT: CONDITIONAL**

As of this investigation the production working tree is **clean**. The previously large dirty set (~200+ paths observed 2026-11-03) was consolidated into a **single commit on `main`**:

- **HEAD:** `c94fd5de11551570372d51e2f7eef93f65feb117`
- **Message (narrow):** “Add Brevo SMTP mailer configuration…”
- **Actual scope:** **444 files**, +34199 / −1690 vs prior `3e91976` — mixing Brevo, auth/OTP hardening, marketing admin foundation, discovery/Mapbox previews, Flutter email tips, ops/queue docs+scripts, large QA screenshot trees, and more.
- **Remote:** `main` is **up to date with `origin/main`** (already pushed).
- **Live queue worker** continues to run from the **tracked** path  
  `docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh`  
  (working tree matches HEAD; cron cycles healthy at investigation time).
- **`.env` remains untracked** (ignored). Placeholder keys only in `.env.example`.
- **Risk is no longer “uncommitted production drift”** — it is **monolithic commit hygiene**, **rollback granularity**, and **runtime files that should not be in Git** (`error_log`, large QA binaries, stub key material in a test).

This phase did **not** alter code, DB, cron, or Git history.

---

## Git baseline

| Item | Value |
|------|--------|
| Branch | `main` |
| HEAD | `c94fd5de11551570372d51e2f7eef93f65feb117` |
| Author (commit) | i-digital-cs \<idigitaleg@gmail.com\> |
| Commit time | 2026-11-04 09:18:47 +0000 |
| Parent (prior dirty base) | `3e919763a46b18496ceae2693453b5bec7a34628` |
| Working tree | **Clean** (`git status` empty) |
| Ahead/behind `origin/main` | **0 / 0** (synced) |
| `git status --porcelain=v1` | empty |
| Modified tracked (WT) | 0 |
| Deleted tracked (WT) | 0 |
| Untracked (WT) | 0 |
| Ignored paths (summary) | ~168 `!!` entries; dominant: `storage/*`, `mobile/` build/.dart_tool, `vendor`, `node_modules`, `public/build`, `.env` |

### Recent commits (context)

1. `c94fd5d` — Brevo-titled mega-commit (444 files)  
2. `3e91976` — Admin V3.1 design prototype  
3. Prior Admin V3 add/remove churn on 2026-10-31  

### `.gitignore` (relevant)

Tracked ignores include: `.env`, `.env.backup`, `.env.production`, `/vendor`, `/node_modules`, `/public/build`, `/public/storage`, `/storage/*.key`, `/storage/pail`, and (added in `c94fd5d`) `/storage/backups`.

**Not ignored (notable):** root `error_log` (modified in `c94fd5d`).

---

## Complete change classification

> Counts below classify **files in commit `c94fd5d`** (the change set that replaced the dirty tree). Working-tree deltas today: **0**.

| Category | Files | Tracked in commit | Intent / purpose | Used in production? | Sensitive config? | Depends on other uncommitted work? |
|----------|------:|-------------------|------------------|---------------------|-------------------|--------------------------------------|
| 01 V3 web (views/css/js general) | 21 | Yes | Member/admin UI, auth tips, layouts | **Yes** (served from this tree) | No | N/A — already on `main` |
| 01b Web discovery previews | 31 | Yes | Living map / Mapbox trial Blade+assets | Preview routes only (signed/throttled) | Tokens via env, not in these files | N/A |
| 02 Flutter | 8 | Yes | Email delivery tip + l10n on verify/reset screens | App store builds from `mobile/` when released | No | N/A |
| 03 Brevo / email integration | 6 | Yes | Isolated `brevo` mailer, test command, mailable, views, tests | Opt-in Artisan only; **default mailer unchanged** | Placeholders in `.env.example` only | N/A |
| 04 Ops / queue | 7 | Yes | Repair plan, quarantine SQL, **cron worker script**, checklist | **Critical:** cron invokes tracked worker script | No secrets in script | Live crontab depends on this path |
| 05 Laravel backend (general) | 48 | Yes | Chat/media, helpers, factories, routes, services | **Yes** | No | N/A |
| 05b Auth / email security | 24+9 UX | Yes | Turnstile, registration abuse gates, OTP budget, AuthCode mail | **Yes** (web registration captcha etc.) | Turnstile placeholders in `.env.example` | N/A |
| 05c Marketing admin | 35 | Yes | Marketing foundation models/controllers | Admin-gated surfaces | Credential **models** exist; secrets in DB/env not in commit | May need migrations applied ops-side (not verified here) |
| 05d Discovery backend | 6 | Yes | Discover map service / related | **Yes** (API discovery) | No | N/A |
| 06 Tests | 20 | Yes | Brevo, OTP, registration, FCM, marketing, media | CI/local only | Stub RSA-shaped string in FCM test (fixture) | N/A |
| 07 Documentation / QA | 226 | Yes | Discovery QA screenshots, email QA, marketing docs, audits | Not runtime | Screenshots may show UI; no env secrets observed by name scan | Inflates repo size |
| 09 Repo meta | 2 | Yes | `.gitignore`, `.env.example` | Example env for developers | Empty secret placeholders | N/A |
| 09 Runtime | 1 | Yes | `error_log` modified | Should **not** be source-of-truth | May contain request noise — treat as sensitive-ish | Should be gitignored going forward |
| Unknown | 0 | — | — | — | — | — |

**Totals:** 444 paths in `c94fd5d`.

### Working tree vs ignored runtime

| Class | Notes |
|-------|--------|
| Ignored `.env` | Present on disk; **not** in Git — correct |
| Ignored `public/build` | Manifest present on disk for Vite — deploy artifact, not in Git |
| Ignored `storage/logs/*` | Includes queue-cron logs and job JSON backups — correct |
| Ignored `vendor` / `node_modules` | Correct |
| Quarantined DB jobs | Not files; still `hold_stale_2026_10_27` ×2 (ops state) |

---

## Production dependencies (working tree / HEAD)

| Dependency | Status | Evidence |
|------------|--------|----------|
| Laravel app code | Served from this checkout @ `c94fd5d` | Clean WT = disk == HEAD |
| Blade / routes / services | In commit; live | Routes include preview + marketing + auth hardening |
| Vite `public/build` | **On disk, gitignored** | `manifest.json` exists; must be preserved in deploys/backups even though not in Git |
| Queue worker script | **Tracked + live** | Crontab → `docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh`; log shows exit 0 ~minute |
| Quarantine SQL / checklist | Tracked docs; already applied earlier | Do not re-run without need |
| Brevo mailer | Config in repo; secret only in `.env` | Default mailer still `env('MAIL_MAILER')` — production remains smtp unless env changed |
| Flutter | Partial tip/l10n in commit | Not serving HTTP; store builds separate |
| Docs/QA images | Tracked; not required for HTTP | Safe to keep; optional later prune |

**Critical:** Any future `git checkout`/`reset` of the worker path or deletion of `docs/operations/queue-scheduler-prep-2026-11-03/` would break cron until crontab is updated. Do not move that script without a coordinated crontab change.

---

## Security concerns

| ID | Severity | Finding | Recommendation (future; not done now) |
|----|----------|---------|----------------------------------------|
| GIT-SEC-01 | P3 | Commit message understates 444-file blast radius | Future commits: split by domain; accurate messages |
| GIT-SEC-02 | P3 | `error_log` tracked/modified in Git | Add to `.gitignore`; stop committing; purge from history only with explicit approval |
| GIT-SEC-03 | P3 | Large QA JPEG trees in Git | Prefer LFS or external artifact store for future QA dumps |
| GIT-SEC-04 | P4 | FCM test contains stub `BEGIN RSA PRIVATE KEY` block (invalid short stub) | Keep as test-only; ensure never replaced with real key |
| GIT-SEC-05 | OK | `.env` not tracked; `.env.example` uses empty placeholders | Maintain; never commit real `BREVO_MAIL_PASSWORD` |
| GIT-SEC-06 | OK | No live secret values printed/found as env assignments in example | Continue redaction discipline |
| GIT-SEC-07 | P2 ops | Monolithic commit on production `main` already pushed | Rollback is all-or-nothing to `3e91976`; plan feature flags / follow-up commits carefully |

---

## Proposed preservation strategy

1. **Treat `c94fd5d` as the current production Git baseline** (already on `origin/main`).  
2. **Do not** `reset`/`clean`/`force-push` to “undo” the mega-commit without a written rollback plan.  
3. **Filesystem backup (owner-approved, later):** rsync/tar of app tree **excluding** `.env` contents from offsite copies *or* encrypt secrets separately; **include** `public/build`, `storage/app` as needed, and confirm `docs/operations/queue-scheduler-prep-2026-11-03/cron-fallback-queue-worker.sh` mode `755`.  
4. **Document crontab + hold-queue state** outside Git (already in ops docs); re-verify after any deploy.  
5. **Logical future commit boundaries** (for *new* work only — do not rewrite `c94fd5d` without approval):

| Future boundary | Scope |
|-----------------|--------|
| A | Ops/queue only |
| B | Brevo/mail test harness only |
| C | Auth/OTP/Turnstile/registration security |
| D | Marketing admin foundation |
| E | Discovery / living-map / Mapbox previews |
| F | Flutter email tip / l10n |
| G | Docs & QA screenshots (or stop committing binaries) |
| H | Hygiene: gitignore `error_log`, remove from index |

6. **Rollback capability:**  
   - App code: revert/revert-commit strategies on `main` (prefer forward fixes).  
   - Worker: crontab line removal (Phase 1 rollback) independent of Git.  
   - Hold jobs: remain in DB; independent of Git.

7. **This phase did not create a disk backup** (avoids large I/O / secret sprawl without approval).

---

## Recommended commit boundaries

**Past (already done — do not rewrite without approval):** one commit `c94fd5d` mixing A–G above.  

**Going forward:** enforce one domain per PR/commit; keep ops worker script changes in dedicated PRs with crontab checklist; never mix `.env` secrets.

---

## Risks and blockers

| Risk | Level | Notes |
|------|-------|-------|
| Coarse rollback | High | Reverting `c94fd5d` reverts Brevo **and** auth hardening **and** discovery previews **and** worker docs |
| Message mismatch | Med | Operators may underestimate blast radius |
| `error_log` in repo | Low–Med | Noise / possible sensitive request data over time |
| QA binary weight | Low | Clone/fetch cost |
| `public/build` not in Git | Med for deploys | Deploy process must build or copy artifacts |
| Marketing migrations | Unknown | Confirm migrate status before relying on admin marketing UI (out of scope to run migrations here) |

**Blockers for “perfect Git hygiene”:** none for continuing ops — tree is clean. Blockers for **surgical** history cleanup: need explicit owner approval (history rewrite on shared `main` is dangerous).

---

## Safe next actions (owner-gated)

1. Approve whether to **gitignore + untrack `error_log`** in a small follow-up commit.  
2. Approve encrypted/offsite **filesystem backup** policy (include/exclude secrets).  
3. Confirm **marketing migrations** applied if marketing admin is to be used.  
4. Keep Phase 2 scheduler (`schedule:run`) **off** until separately approved.  
5. For new work: branch/PR discipline with boundaries A–H.  
6. Do **not** move the queue worker script path without updating crontab in the same change window.

---

## Exact production changes made **by this audit**

| Change type | Result |
|-------------|--------|
| Git | **NONE** (no stage/commit/push/reset/clean/stash) |
| Filesystem (app code) | **NONE** except creating this document |
| Database | **NONE** |
| Cron / worker | **NONE** (observed only; worker left running) |
| `.env` / mail | **NONE** |

**File created:** `docs/audits/FUNNITY_GIT_PRODUCTION_SAFETY_2026-11-04.md`  
*(Creating this file makes the working tree dirty by one untracked doc until committed — intentional deliverable.)*

---

*End of Git production safety inventory.*
