# Marketing Phase 1 — Production QA

**Date:** 2026-10-31  
**Environment:** Production `funnity.com` after foundation deploy

---

## 1. Automated tests (re-run after index fix)

| Suite | Result |
|-------|--------|
| `Tests\Unit\Marketing` + `Tests\Feature\Marketing` | **21 passed / 0 failed** |
| Pre-deploy combined with admin/auth regression | **43 passed / 0 failed** |

---

## 2. Public HTTP health

| URL | Result |
|-----|--------|
| `GET https://funnity.com/` | **200** |
| `GET https://funnity.com/events` | **200** |
| `GET https://funnity.com/login` | **200** |
| `GET https://funnity.com/admin` | **302** (auth) |
| `GET https://funnity.com/admin/marketing` | **302 → /login** (guest denied) |

---

## 3. Authenticated route verification (in-process HTTP kernel)

Uses real production DB + SuperAdmin identity via Laravel HTTP kernel (not a visual browser session).

| Check | Result |
|-------|--------|
| SuperAdmin `/admin/marketing` | **200**, title present, empty-state present |
| No fabricated `$12,345` metrics | PASS |
| SuperAdmin integrations | **200**, 2× Not Connected, Connect disabled |
| SuperAdmin budget policies | **200**, provider-limit disclaimer present |
| Active member `/admin/marketing` | **403** |
| Guest `/admin/marketing` | **302** |
| Moderator production user | **Not tested** (no moderator row present in DB at QA time) |

---

## 4. Budget policy QA write

| Step | Result |
|------|--------|
| Create global policy `max_daily_spend=0.01` | policy id **1** |
| Disable policy | `active=0` |
| Audit actions | `budget_policy_created`, `budget_policy_disabled` |
| Provider accounts/credentials still 0 | PASS |
| Policy left disabled (not deleted) to preserve audit subject integrity | intentional |

---

## 5. Browser visual QA (desktop/mobile/RTL screenshots)

| Check | Status |
|-------|--------|
| Interactive browser login + visual layout | **NOT EXECUTED** (no safe interactive SuperAdmin browser session used) |
| Tajawal font load visual confirmation | **NOT EXECUTED** in browser |
| Mobile overflow visual | **NOT EXECUTED** |

Code includes Tajawal `@push('head')` on marketing views for `html[lang^=ar]`.

---

## 6. Queue comparison

| Metric | Before | After |
|--------|--------|-------|
| jobs | 2 | 2 |
| failed_jobs | 0 | 0 |

Pending `NotifyFriendsOfPublishedEvent` jobs untouched.

---

## 7. Distilled status

| Area | Label |
|------|-------|
| Marketing foundation | **Deployed** |
| Automated tests | **Tested / PASS** |
| Guest/member authz on prod routes | **Tested / PASS** |
| SuperAdmin HTML responses | **Tested / PASS** (kernel) |
| Visual browser QA | **Not tested** |
| Cron/Worker | **Not activated** |
| Ads execution | **Blocked / disabled** |
