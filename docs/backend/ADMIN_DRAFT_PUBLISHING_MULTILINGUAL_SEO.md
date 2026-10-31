# Admin draft access · Direct publishing · Multilingual SEO

**Date:** 2026-10-31  
**Verdict:** **CONDITIONAL**

---

## A. Environment

| Item | Value |
|------|--------|
| Project path | `/home/d14098/funnity.com` |
| Branch | `main` |
| Baseline HEAD (start) | `810ee245f4f5488c1499e35e5bada2015a575427` |
| `origin/main` | Matched at start |
| Working tree at start | Clean |
| APP_ENV | `production` |
| APP_URL | `https://funnity.com` |
| APP_DEBUG | `false` |
| PHP | 8.2.35 |
| Laravel | 12.69.2 |
| DB | MySQL database name `d14098_fun` (credentials not disclosed) |
| Backups | `storage/app/backups/` present |
| Migrations pending approval | `2026_10_31_120000_add_slug_ar_to_events_table` (**Pending** — not executed) |

---

## B. Event 605 — admin 403

### Confirmed state

Read-only check: `id=605`, **`status=draft`**, slug `tgmaa-mlaky-gyb-shroky`. **Still draft after this work** (no status mutation).

### Exact 403 path

1. Admin UI title/View uses `event_show_url()` → public `GET /events/{id}/{slug}` (`events.show`).
2. `EventController::show` → `$this->authorize('view', $event)`.
3. `EventPolicy::view` allowed published/completed **or** `isManagedBy` only — **no** `platform_role` check.
4. Platform admin who is not host/cohost → **403**.

### Fix

`EventPolicy::view` now allows `$user->platform_role?->canModerateEvents()`.

Drafts remain denied for guests and unrelated users. Discover still excludes drafts. SEO for non-public statuses stays `noindex,nofollow`. Admin preview banner shown when a moderator views a non-owned draft.

### Regression evidence

`tests/Feature/AdminDraftEventAccessTest` — **PASS** (admin OK, owner OK, other/guest 403, discovery excludes, published still public, status unchanged).

---

## C. Publishing (OPTION A)

### Previous behavior

`CreateEventService` always forced `draft`. Publish was a second step (`POST …/publish` / web form on show). Flutter offered an optional dialog (easy to dismiss → stuck draft).

### New approved behavior

| Intent | Field | Result |
|--------|-------|--------|
| Create / Publish | `publish_intent=true` | create draft → `TransitionEventStatusService::publish` → `published` |
| Save draft | `publish_intent=false` / omitted | `draft` |

Web create form: two submit buttons. API: same boolean. Existing drafts **not** migrated. Event **605** untouched.

### Discovery

Published events appear in `GET /api/events` under existing eligibility rules immediately (no inventing coords). Covered by `DirectEventPublishingTest`.

### Flutter

Handoff: [`docs/flutter/EVENT_PUBLISHING_API_HANDOFF.md`](../flutter/EVENT_PUBLISHING_API_HANDOFF.md)

---

## D. SEO (Arabic / English)

### Root cause of weak Arabic visibility

Session/`Accept-Language` locale on **one URL**. Cookieless Googlebot typically received English HTML; no `hreflang`; no `/ar` crawl surface (`docs/seo/INDEXING_ROOT_CAUSE.md` F7).

### Implemented architecture

| Surface | Behavior |
|---------|----------|
| `/` , `/events/...` | English **canonical** (legacy preserved) |
| `/ar/...` | Arabic HTML **without cookies** |
| `/en/...` | **301** → bare English canonical |
| `hreflang` | `ar` / `en` / `x-default` on home + indexable events |
| Homepage meta | Updated EN/AR brand positioning copy |
| Event JSON-LD | Only for public published/completed |
| Draft / admin preview | `noindex` |
| Sitemap | EN loc + AR alternate URLs + xhtml links; drafts excluded |

### Localized slugs

- Migration adds nullable unique `slug_ar` (Unicode).
- Legacy `slug` unchanged (keeps existing indexed/shared URLs).
- When title contains Arabic and column exists, `slug_ar` is generated.
- **Do not invent English translations** for Arabic-only titles (e.g. 605).
- **Migration not run on production** — code guards with `Schema::hasColumn`.

### Legacy URL preservation

Bare `/events/{id}/{slug}` remains valid. No mass 301 of bare → `/en`.

---

## E. Validation

### Automated tests (PASS)

```
AdminDraftEventAccessTest
DirectEventPublishingTest
MultilingualSeoRoutesTest
OrganicDiscoveryTest (updated for hreflang)
EventManagementTest / EventDiscoveryVisibilityTest (subset)
```

**33 passed** in the filtered run above.

### Authorization matrix

| Actor | Draft | Published |
|-------|-------|-----------|
| Guest | 403 | 200 |
| Other user | 403 | 200 |
| Owner | 200 | 200 |
| Platform moderator/admin | 200 (noindex) | 200 |

### Production actions **pending approval**

1. Backup DB (use existing `storage/app/backups` process).
2. `php artisan migrate` — only `add_slug_ar_to_events_table`.
3. Optional backfill: regenerate `slug_ar` for Arabic-titled published events (read-only plan first; **do not change status**).
4. `php artisan cache:forget seo.sitemap.xml` (+ config/route/view cache refresh as usual).
5. Vite rebuild only if unrelated front assets changed (auth JS from prior task may already need deploy).
6. Smoke: `/ar`, `/events/605/{slug}` as admin, create with publish_intent, sitemap XML.
7. Submit updated sitemap in Search Console (indexing not claimed here).

### Rollback

- Revert code deploy.
- `migrate:rollback` one step for `slug_ar` **only if** column empty/safe.
- Do not republish or unpublish events as rollback.

### Remaining risks

- Publish after create is not one DB transaction spanning both services — rare publish failure can leave a draft (documented in Flutter handoff).
- Google indexing lag; Search Console verification still required.
- Until migration runs, Arabic event URLs reuse latin `slug` (still crawlable under `/ar/...`).
- City/activity landing pages under `/ar/...` exist; reciprocal hreflang on those landings is lighter than home/events (follow-up optional).

---

## F. Files changed (high level)

- Policy: `EventPolicy`
- Publish: `StoreEventRequest`, Web/API `EventController`, create blade, lang EN/AR
- SEO: `SetLocale`, `SetLocaleFromPath`, `LocaleUrl`, `Seo`, meta blade, layouts, `SitemapController`, sitemap view, `routes/web.php`, design copy
- Slugs: Event model + migration (pending)
- Tests + docs as above

---

## G. Verdict

**CONDITIONAL**

- Admin draft 403: **fixed and tested**.
- Direct publishing: **implemented and tested**; Flutter UI still needs Windows handoff.
- Multilingual SEO foundation: **implemented and tested** in isolation.
- Not **PASS** until: production `slug_ar` migration approved/executed, sitemap cache cleared, Search Console / live crawler checks, Flutter publish UI shipped, and operator confirms event 605 still draft after deploy smoke.
