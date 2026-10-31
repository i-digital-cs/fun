# Production deployment verification — draft access, direct publishing, multilingual SEO

**Date:** 2026-10-31 (UTC)  
**Production:** https://funnity.com  
**Application path:** `/home/d14098/funnity.com`  
**Operator scope:** Safe deploy + verify only (no feature redesign; no disposable production writes; no auto-commit/push)

**Verdict: CONDITIONAL**

All Phase 1–2 safety gates passed. The approved `slug_ar` migration was backed up, pretended, executed, and verified. Event **605** remains `draft`. Admin/owner/guest/other authorization matches policy. Live Arabic/English SEO HTTP checks succeed. Targeted feature tests: **15 passed** on isolated SQLite. Remaining: Flutter publish UI still pending, Search Console submission is a manual operator step, and `slug_ar` values are intentionally unset (English slug fallback used on `/ar/events/...`).

---

## 1. Production environment

| Item | Value (no secrets) |
|------|--------------------|
| Path | `/home/d14098/funnity.com` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://funnity.com` |
| PHP | 8.2.35 (cli) |
| DB driver | `mysql` / MariaDB **11.4.13** |
| DB name | `d14098_fun` |
| Charset / collation | `utf8mb4` / `utf8mb4_unicode_ci` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `SESSION_DRIVER` | `database` |

Laravel PDO connectivity verified before backup and migration.

---

## 2. Git baseline

| Item | Value |
|------|-------|
| Branch | `main` (tracks `origin/main`, up to date) |
| HEAD | `85b84cce769abe94e819957395c2b026bb3e0367` |
| Subject | Implement direct event publishing and multilingual SEO enhancements |
| Working tree | Clean except unrelated local `error_log` modification (**left untouched**) |
| Force-pull / reset / clean | **Not used** |

Implementation (policy, `publish_intent`, locale routes, migration file, tests, docs) is present at HEAD on the production path. No divergent uncommitted PHP for this feature.

---

## 3. Backup verification

| Item | Result |
|------|--------|
| Method | `mariadb-dump` via Laravel-generated `--defaults-extra-file` (password never printed; file removed after dump) |
| Flags | `--single-transaction --routines --triggers --hex-blob` + utf8mb4 |
| Path | `storage/app/backups/d14098_fun-slug_ar-pre-migrate-20261031T100703Z.sql.gz` |
| Size | **323 695 bytes** (~317K) |
| `gzip -t` | OK |
| Header | MariaDB dump 10.19 / server 11.4.13 / database `d14098_fun` |
| Integrity | `CREATE TABLE \`events\`` present; pre-migrate dump shows `slug` only (no `slug_ar`) |

First dump attempt via shell-sourced env failed (`Access denied` — special characters in password). Retried with defaults-extra-file → **BACKUP_OK**. Failed 20-byte artifact removed.

---

## 4. Migration review and execution

### Pre-review (`2026_10_31_120000_add_slug_ar_to_events_table`)

| Check | Result |
|-------|--------|
| Pending migrations | **Only this one** |
| Destructive ops | None (add nullable column + unique index) |
| Existing column | `slug_ar` absent before migrate |
| Unique conflict | New index `events_slug_ar_unique`; existing `events_slug_unique` on `slug` unchanged |
| Nullability | `varchar(160) NULL` — safe for 558 existing rows |
| Charset | Table/DB utf8mb4 — Arabic-capable |
| Rollback | `down()` drops unique then column |
| Bulk slug fabrication | **Not performed** |

### Pretend SQL (`php artisan migrate --pretend --force`)

```
alter table `events` add `slug_ar` varchar(160) null after `slug`
alter table `events` add unique `events_slug_ar_unique`(`slug_ar`)
```

### Execution

```
php artisan migrate --force --no-interaction
→ 2026_10_31_120000_add_slug_ar_to_events_table ... DONE (batch 12)
```

### Post-verify

| Check | Result |
|-------|--------|
| Column | `slug_ar` varchar(160) NULL, UNI, default NULL |
| Unique index | `events_slug_ar_unique` present |
| Event count | 558 unchanged |
| Status histogram | `cancelled` 3 · `completed` 401 · `draft` 1 · `published` 153 (unchanged) |
| `slug_ar` null count | 558 (expected; no fabricated translations) |
| Event 605 | `status=draft`, `slug=tgmaa-mlaky-gyb-shroky`, `slug_ar=NULL` |

---

## 5. Event 605 authorization results

Path: `/events/605/tgmaa-mlaky-gyb-shroky`

| Actor | HTTP | Draft banner | `noindex` | Notes |
|-------|------|--------------|-----------|-------|
| Guest (live curl) | **403** | — | yes (error page) | Public denied |
| Unrelated user (id ≠ owner/admin) | **403** | no | yes | Policy deny |
| Owner (`created_by=1547`) | **200** | no | **yes** | Own draft; no admin banner |
| Platform `super_admin` | **200** | **yes** (`events.admin_draft_preview`) | **yes** | Moderator preview |

`EventPolicy::view` — guest/other `0`, owner/admin `1`. Authorization middleware **not** weakened. No auth cookies/tokens recorded in this report.

Nationwide discovery API: `has_605=no`. Sitemap: draft **absent**.

---

## 6. Event 605 unchanged-status confirmation

| Before migrate | After migrate | After auth probes |
|----------------|---------------|-------------------|
| `draft` | `draft` | `draft` |

Slug unchanged. `slug_ar` remains `NULL`. Only draft in production remains id **605**.

---

## 7. Direct publishing contract results

Verified via **isolated SQLite** feature tests (`DirectEventPublishingTest`) — **no production event creates/publishes**.

| Contract | Result |
|----------|--------|
| Missing `publish_intent` → draft | PASS (`test_api_create_without_intent_stays_draft`) |
| `publish_intent=true` → published + discoverable | PASS |
| Web `publish_intent=0` → draft | PASS |
| Web `publish_intent=1` → published | PASS |
| Existing drafts not bulk-published | PASS (`Must Stay Draft` stays draft) |

Web Create Event form (`resources/views/events/create.blade.php`): Publish button `publish_intent=1`, Save Draft `publish_intent=0`. Blade-only; Vite rebuild **not** required for this change. `public/build/manifest.json` dated 2026-10-29 left as-is.

---

## 8. Arabic / English SEO results (live HTTP)

| URL | Status | Notes |
|-----|--------|-------|
| `/` | 200 | `lang=en` `dir=ltr`; hreflang ar/en/x-default; canonical `https://funnity.com`; `index,follow`; JSON-LD |
| `/ar` | 200 | **No session cookie required**; `lang=ar` `dir=rtl`; ~3833 Arabic chars; Arabic title/metadata; reciprocal hreflang; canonical `/ar` |
| `/en` | 200 after 1 redirect → `/` | English canonical preserved; no loop |
| `/events/604/bolyng` | 200 | Canonical EN slug URL; hreflang → `/ar/events/604/bolyng`; `index,follow`; JSON-LD Event name بولينج |
| `/ar/events/604/bolyng` | 200 | Arabic HTML; RTL; reciprocal hreflang; JSON-LD `@graph` Event name بولينج |
| `/events/604` (legacy) | 200 | Serves event; canonical points to slug URL |
| Draft 605 guest | 403 + noindex | Not used as indexable example |

No redirect loops observed (`/en` single hop). Legacy id URL intact. With `slug_ar` null, Arabic event paths correctly fall back to English slug (hreflang pairs match).

---

## 9. Sitemap results

| Item | Result |
|------|--------|
| Production sitemap URL | **https://funnity.com/sitemap.xml** |
| HTTP | 200 |
| Cache key | `seo.sitemap.xml` (TTL 3600, database cache) |
| Invalidation | `Cache::forget('seo.sitemap.xml')` only — **not** a full cache flush |
| Rebuild | Confirmed via refetch after forget |
| URL count | 135 `<url>` entries |
| Event 604 | Present + xhtml hreflang en/ar/x-default |
| Event 605 (draft) | **Absent** |
| `/ar` home | Present |
| `robots.txt` | 200; disallows private areas; `Sitemap: https://funnity.com/sitemap.xml` |

**Indexing note:** Presence in the sitemap does **not** mean Google has indexed the URL.

---

## 10. Cache / asset changes

| Action | Done? |
|--------|-------|
| Forget `seo.sitemap.xml` | Yes |
| Full `cache:clear` / config/route/view wipe | **No** |
| Vite rebuild | **Not required** (no pending JS/CSS build delta for this deploy; Blade + PHP only) |
| Asset manifest | `public/build/manifest.json` present |

---

## 11. Tests

**Runner:** `php artisan test --filter='AdminDraftEventAccessTest|DirectEventPublishingTest|MultilingualSeoRoutesTest'`  
**DB:** phpunit `sqlite` `:memory:` (`APP_ENV=testing`) — **not** production MySQL.

| Suite | Result |
|-------|--------|
| AdminDraftEventAccessTest (5) | PASS |
| DirectEventPublishingTest (4) | PASS |
| MultilingualSeoRoutesTest (6) | PASS |
| **Total** | **15 passed (55 assertions)** |

Plus safe read-only production HTTP checks documented in §§5–9.

---

## 12. Pending Flutter work

Tracked in `docs/flutter/EVENT_PUBLISHING_API_HANDOFF.md` and `docs/FLUTTER_WEB_PARITY.md` §10 item **1b**:

- Flutter Create Event must send `publish_intent=true` on Publish and `false`/omit on Save Draft.
- Discover stale refresh after successful publish.
- Do not invent a divergent API shape.
- Backend already returns `published`, `message`, optional `seo_warning`.

Until Flutter ships dual CTAs, mobile create may still behave as draft-only depending on client payload.

---

## 13. Remaining risks

1. **Flutter UI lag** — web/API contract live; mobile may still omit `publish_intent`.
2. **Empty `slug_ar`** — Arabic URLs use English slug until editors set Arabic slugs; SEO works via `/ar` prefix + hreflang, but dedicated Arabic slug paths are unused.
3. **Search Console** — sitemap not auto-submitted from this session (no GSC credentials in scope).
4. **Local `error_log` dirty** — unrelated; do not commit.
5. **Dump credential sensitivity** — always use defaults-extra-file for MariaDB dumps when passwords contain shell metacharacters.

---

## 14. Rollback procedure

### Schema only (column unused in production data)

```bash
cd /home/d14098/funnity.com
php artisan migrate:rollback --step=1 --force
# rolls back 2026_10_31_120000_add_slug_ar_to_events_table
```

App code guards `Schema::hasColumn(..., 'slug_ar')` where needed; after rollback, English-only slug paths remain valid.

### Full DB restore (if required)

1. Put app in maintenance if needed: `php artisan down`.
2. Restore from  
   `storage/app/backups/d14098_fun-slug_ar-pre-migrate-20261031T100703Z.sql.gz`  
   via `gunzip -c … | mariadb …` using a secure defaults file (do not echo password).
3. Confirm event 605 `draft` and counts.
4. `php artisan up`.
5. Optionally redeploy prior git SHA if code rollback required (`85b84cc` parent: `cc95ecf`).

### Sitemap cache after rollback

```php
Cache::forget('seo.sitemap.xml');
```

---

## 15. Google Search Console handoff (manual)

1. Open [Google Search Console](https://search.google.com/search-console) for `https://funnity.com`.
2. **Sitemaps** → submit: `https://funnity.com/sitemap.xml` (or confirm existing entry shows Success).
3. Optionally **URL inspection** for `/`, `/ar`, and a published event (e.g. `/events/604/bolyng`) — request indexing if needed.
4. Do **not** request indexing for `/events/605/...` (draft).
5. Monitor Coverage / Page indexing over subsequent days — sitemap inclusion ≠ indexed.

---

## Final verdict

| Gate | Status |
|------|--------|
| Safety / path / git / env | PASS |
| Backup | PASS |
| Migration review + sole pending | PASS |
| Migration execute + data integrity | PASS |
| Event 605 still draft | PASS |
| Admin draft access | PASS |
| Publishing contract (isolated tests) | PASS |
| Live multilingual SEO HTTP | PASS |
| Sitemap + targeted cache | PASS |
| Feature tests (15) | PASS |
| Flutter publish UI | **PENDING** |
| GSC submission | **MANUAL** |

### **CONDITIONAL**

Production deploy and verification of the approved Laravel work succeeded. Do not treat as full product **PASS** until Flutter sends `publish_intent` per handoff and an operator confirms Search Console sitemap acceptance.
