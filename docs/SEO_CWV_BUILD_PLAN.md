# Technical SEO & Core Web Vitals — Build Plan

**Stack:** Laravel Blade + Vite (SSR). Not Next.js — no SSG/ISR port required.  
**Scope:** Web crawlability, metadata, CWV. Flutter is out of scope for robots/sitemap/OG HTML; note shared-media and public URL changes under Flutter parity.  
**Related:** [`FLUTTER_WEB_PARITY.md`](FLUTTER_WEB_PARITY.md) · [`DESIGN.md`](../DESIGN.md)

---

## Goals

| Outcome | Measure |
|---------|---------|
| Indexable public pages have unique title + description + canonical | Home, Discover, Event show, Public profile, Safety |
| Social shares show correct preview | `og:*` + Twitter card + real cover image |
| Crawlers discover events | `/sitemap.xml` + `Sitemap:` in robots |
| Private app chrome stays out of the index | `noindex` + robots Disallow |
| Faster LCP on home / event show | Compressed covers, LCP attrs, font strategy |
| Smaller JS on marketing pages | Echo/Pusher and maps not in global critical path |

---

## Phase overview

| Phase | Name | Priority | Est. effort | Depends on |
|-------|------|----------|-------------|------------|
| **1** | SEO foundation (meta, robots, sitemap, JSON-LD) | P0 | 1–2 days | — |
| **2** | Core Web Vitals (images, fonts, JS split) | P0/P1 | 2–3 days | Phase 1 optional but parallel OK |
| **3** | Architecture (slugs, canonical filters, i18n URLs, IA) | P1 | 3–5 days | Phase 1 canonical stack |
| **4** | Hardening (tests, Search Console, monitoring) | P2 | 1 day | Phases 1–2 |

Ship Phase 1 before or with Phase 2. Do **not** start Phase 3 locale URL work until Phase 1 is live (hreflang needs a meta/canonical pattern first).

---

## Phase 1 — SEO foundation (P0)

### 1.1 Shared SEO component

**Add**

- `resources/views/components/seo/meta.blade.php`
- Optional View Composer or request attributes: `$seoTitle`, `$seoDescription`, `$seoImage`, `$seoUrl`, `$seoType`, `$seoRobots`, `$seoJsonLd`

**Props**

| Prop | Default | Notes |
|------|---------|-------|
| `title` | brand only | Render as `{title} · {brand}` when title set |
| `description` | site tagline from lang | Strip tags, limit ~155 chars |
| `image` | `site_logo_url()` / `logo.svg` | Absolute URL |
| `url` | `url()->current()` | Prefer named route URL without junk query |
| `type` | `website` | Event show may use `article` or keep `website` + Event JSON-LD |
| `robots` | `index,follow` | Private shells: `noindex,nofollow` |
| `jsonLd` | null | Encoded `application/ld+json` |

**Wire into**

- `layouts/home.blade.php`
- `layouts/app.blade.php`
- `layouts/dashboard.blade.php` — default `noindex,nofollow` **except** when view opts into indexing (Discover, Event show, Public profile use dashboard shell today)
- `layouts/admin.blade.php` — always `noindex,nofollow`

**Important:** `/events`, `/events/{id}`, `/u/{id}` extend `layouts.dashboard`. Either:

- **A (preferred):** pass `$seoRobots = 'index,follow'` from those controllers/views, layout default `noindex`, or  
- **B:** move public event/profile pages to `layouts.app` / `layouts.home` (larger UX change — defer unless desired).

**Acceptance**

- View source on `/` and `/events/{published}` shows description, canonical, og:title/image/url, twitter:card.
- `/dashboard`, `/admin`, `/login` show `noindex`.

### 1.2 Page payloads

| Route | Title | Description source | Image | JSON-LD |
|-------|-------|--------------------|-------|---------|
| `/` | Brand + short tagline | `__('design.welcome_sub_generic')` or city-aware | Logo or highlight cover | `WebSite` + `Organization` (+ optional `SearchAction` → `/events?q={search_term_string}`) |
| `/events` | Discover + brand | `__('events.discover_subtitle')` | Logo | Optional `CollectionPage` |
| `/events/{id}` | Event title + brand | `$event->description` or fallback | `$event->coverUrl()` | **`Event`** (required) |
| `/u/{publicId}` | Name · public_id | Short bio/fallback | Profile photo | `Person` |
| `/safety` | Guidelines title | Intro paragraph | Logo | Optional `WebPage` |
| Auth / settings / chat / admin | Existing titles | Minimal | — | none; **noindex** |

**Controllers to touch**

- `HomeController`
- `EventController@index`, `@show`
- `PublicProfileController@show`
- `SafetyGuidelinesController`
- Guest auth controllers (or layout default noindex)

### 1.3 robots.txt

**Replace** static allow-all with either:

1. Static `public/robots.txt`, or  
2. `Route::get('/robots.txt', …)` (easier env-based `APP_URL` for Sitemap).

**Rules (minimum)**

```
User-agent: *
Disallow: /admin
Disallow: /dashboard
Disallow: /settings
Disallow: /chats
Disallow: /identity
Disallow: /email
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /my-events
Disallow: /favorites
Disallow: /report
Disallow: /account
Disallow: /api/

Allow: /
Allow: /events
Allow: /safety
Allow: /u/

Sitemap: {APP_URL}/sitemap.xml
```

**Also:** remove or block crawl of junk files:

- Delete or move out of web root: `public/code.html`, `public/together_home_page.html`

### 1.4 Sitemap

**Add**

- `app/Http/Controllers/Web/SitemapController.php`
- `resources/views/seo/sitemap.blade.php` (XML)
- Route `GET /sitemap.xml` → `sitemap`

**Include**

1. Home, Discover, Safety  
2. Published + Completed events (`id`, `updated_at`) — paginate/chunk if >5k  
3. Optional: public profiles that are intended to be indexed

**Exclude**

- Drafts, cancelled (unless product wants cancelled indexed — default exclude)  
- Auth/admin URLs  

**Cache:** `Cache::remember('sitemap.xml', 3600, …)` or regenerate on event publish via observer (optional stretch).

### 1.5 Favicon / icons

- Replace empty `public/favicon.ico`
- Add `<link rel="icon">` (+ apple-touch if logo PNG available) from `site_logo_url()` / branding assets

### 1.6 Tests (Phase 1)

- Feature: home / event show HTML contains `og:title`, `canonical`, `application/ld+json`
- Feature: dashboard / login contain `noindex`
- Feature: `GET /sitemap.xml` is 200 + includes a published event URL
- Feature: `GET /robots.txt` contains `Sitemap:` and Disallow `/admin`

### 1.7 Flutter / docs (Phase 1)

- **No Flutter UI required** (web HTML head only).
- Update `FLUTTER_WEB_PARITY.md` §10 / event show note: “Web emits Event JSON-LD + OG for share URLs; API unchanged.”
- If later API adds `share_image` / `meta_description` fields for in-app share sheets, document then — **not required for Phase 1**.

### Phase 1 checklist

- [x] `x-seo.meta` component  
- [x] Layouts wired + public vs private robots rule  
- [x] Home / Discover / Show / Profile / Safety payloads  
- [x] Event JSON-LD  
- [x] robots.txt + sitemap  
- [x] Remove prototype HTML from `public/`  
- [x] Favicon links  
- [x] Feature tests  
- [x] Parity doc note  

---

## Phase 2 — Core Web Vitals (P0/P1)

### 2.1 Stock activity images (largest LCP win)

**Problem:** `public/images/activities/*.png` up to **1.5MB**, displayed ~148px tall in cards.

**Work**

1. Generate WebP (and optionally AVIF) at widths **320 / 640 / 1280**.  
2. Update `event_cover_url()` or add `event_cover_srcset()` helper returning `src` + `srcset` + `sizes`.  
3. Update consumers:
   - `components/events/card.blade.php`
   - `components/community/hero.blade.php` (LCP candidate → eager + `fetchpriority="high"`)
   - `events/show.blade.php` (LCP)
   - `components/dashboard/now-playing.blade.php` (lazy + tiny size)

**Acceptance:** Lighthouse LCP image &lt; ~200KB on home with stock cover; cards use `sizes` matching layout.

### 2.2 Upload pipeline

**Touch:** `app/Services/Media/PublicMediaUploader.php` (+ validation still max 5MB input OK)

**On store event image**

1. Decode upload  
2. Resize max edge **1600px** (detail)  
3. Create card variant **640px** (optional second path or Intervention/GD)  
4. Prefer WebP storage when supported  
5. Store paths; `coverUrl()` / new accessor prefer card variant for lists

**Acceptance:** New uploads never serve multi‑MB originals on Discover cards.

### 2.3 Image attribute hygiene

| Context | `loading` | `fetchpriority` | `alt` | dimensions |
|---------|-----------|-----------------|-------|------------|
| Event show cover | eager (default) | `high` | event title | width/height or aspect-ratio box |
| Hero highlight | eager | `high` | title | yes |
| Event cards | `lazy` | auto | event title | yes |
| Avatars / now-playing thumbs | `lazy` | auto | name or empty if decorative | yes |

### 2.4 Fonts

**Layouts today:** render-blocking Bunny CSS, 8 weights, no `display=swap`.

**Plan (pick one)**

| Option | Pros | Cons |
|--------|------|------|
| **A.** Bunny URL + `&display=swap` + preload | Fast change | Still third-party |
| **B.** Self-host WOFF2 (400/600 Latin + Arabic) + `font-display: swap` in `app.css` | Best CWV | Slightly more asset ops |

**Recommendation:** B for production; A as interim in same PR if self-host slips.

Drop weight **700** if design allows (extrabold via 600 + tracking).

### 2.5 JS code-splitting

**Today:** `resources/js/app.js` always imports Echo + Pusher + always inits place maps.

**Target**

```
app.js          → live-search, confirm, page-loader, attachment-folder (light)
realtime.js     → Echo/Pusher (dynamic import when [data-realtime] or chat toast host)
event-place-map → already separate logic; only import when picker/viewer nodes exist
```

**Layouts:** set `data-realtime="1"` on dashboard/chat when authenticated and toast host present; **omit** on marketing `layouts.home` for guests if no Echo needed.

**Acceptance:** Guest home JS payload no longer includes pusher-js in the critical graph (check Vite build / Network).

### 2.6 Motion / INP (optional stretch)

- Extend `prefers-reduced-motion` to ping, EQ, espiral, page-loader where cheap  
- Pause or simplify `x-community.ambient` aurora on mobile / when offscreen  

### 2.7 Flutter / docs (Phase 2)

- Upload resize improves API media → **Flutter cards get faster images automatically**.  
- Document in parity: “Event cover URLs may be optimized variants; clients should not assume original dimensions.”  
- No Flutter screen work required unless share sheet needs explicit `image_variants` in JSON (defer).

### Phase 2 checklist

- [ ] Compress/convert stock activities + helper srcset  
- [ ] Upload resize + WebP  
- [ ] LCP attrs on show + hero  
- [ ] Font display / self-host  
- [ ] Split Echo/Pusher + conditional maps  
- [ ] Quick Lighthouse pass on `/` and `/events/{id}`  
- [ ] Parity note on media variants  

---

## Phase 3 — Site architecture (P1)

### 3.1 Event URL slugs

**Goal:** `/events/{id}/{slug}` with 301 from `/events/{id}`.

**Work**

1. Migration: `events.slug` (unique, nullable first) + backfill from title  
2. On create/update: regenerate slug (Str::slug + uniqueness)  
3. Route: `events/{event}/{slug?}` — if slug missing/wrong, 301 to canonical  
4. `Event::getRouteKeyName()` stays id **or** custom binding — prefer explicit route params `{event}` + `{slug}`  
5. Update all `route('events.show', $event)` helpers to include slug  

**Flutter parity (required)**

- API remains `/api/events/{id}` — **no breaking API change**.  
- Update `FLUTTER_WEB_PARITY.md` web route column: `/events/{id}/{slug}`.  
- If app uses universal/app links to web URLs, update link patterns; in-app GoRouter can stay `/events/:id`.  
- §10 roadmap: “Web public event URL includes slug; Flutter deep link optional.”

### 3.2 Canonical Discover URLs

- Canonical for `/events` = clean URL **without** filter query (or only allowlisted params if product wants filtered landing pages indexed — default: strip `q`, `city_id`, `distance_km`, `fragment`).  
- Pagination: either `rel=next/prev` or noindex page 2+ (choose one; prefer canonical to page 1 + index only page 1).

### 3.3 Locale / hreflang

**Current:** session locale via `POST /locale` — same URL for EN/AR.

| Option | Effort | SEO value |
|--------|--------|-----------|
| **A. Keep session locale** | 0 | Document limitation; no fake hreflang |
| **B. Prefix routes `/en`, `/ar`** | High | Real hreflang + alternate |

**Recommendation:** Phase 3 ships **A documented**; schedule **B** as separate epic if bilingual SERP is a business goal. Do not emit incorrect hreflang on identical URLs.

### 3.4 Internal linking IA

- Footer: Home · Discover · Safety (remove duplicate Trust → same URL)  
- Optional: category chips linking to `/events?category_id=` (nofollow or canonical-aware)  
- Breadcrumbs on event show + `BreadcrumbList` JSON-LD  

### 3.5 Flutter / docs (Phase 3)

- Update route map in `FLUTTER_WEB_PARITY.md`  
- §10 checklist item for slug URLs + optional Flutter app-link handling  
- No change to member API contracts unless adding `slug` field for share text (nice-to-have on `GET /api/events/{id}`)

### Phase 3 checklist

- [ ] Slug column + backfill + canonical redirects  
- [ ] Discover canonical policy  
- [ ] Locale decision recorded (A vs B)  
- [ ] Footer / breadcrumbs  
- [ ] Parity doc + §10  

---

## Phase 4 — Hardening (P2)

1. Feature tests for slug 301s, robots, sitemap freshness  
2. Submit sitemap in Google Search Console / Bing  
3. Optional: `spatie/laravel-sitemap` if maintenance of XML becomes painful  
4. Monitor CWV via CrUX / PageSpeed on `/` and sample event URLs  
5. Content: ensure published events have non-empty descriptions (product validation soft-warn)

---

## Execution order (sprints)

```
Sprint A (Phase 1)
  1.1 meta component → layouts
  1.2 page payloads + Event JSON-LD
  1.3–1.4 robots + sitemap
  1.5 favicon
  1.6 tests + parity note

Sprint B (Phase 2 — parallelizable after 1.1)
  2.1 stock WebP/srcset
  2.3 LCP attributes
  2.4 fonts
  2.5 JS split
  2.2 upload resize (can trail 2.1)

Sprint C (Phase 3)
  3.1 slugs
  3.2 discover canonical
  3.4 footer/breadcrumbs
  3.3 locale epic decision only (or defer B)

Sprint D (Phase 4)
  GSC + tests + monitoring
```

---

## Explicit non-goals

- Rewriting Blade to Next/Nuxt for SSR  
- Meta keywords  
- Indexing Flutter app screens  
- Porting `/admin` SEO  
- Fake hreflang without locale URLs  

---

## Decision log (fill during build)

| Decision | Choice | Date |
|----------|--------|------|
| Public pages on dashboard layout robots | A: opt-in index from controller | |
| Font strategy | A interim / B self-host | |
| Locale URLs | A session only (Phase 3) | |
| Discover page 2+ | canonical to page 1 | |
| Cancelled events in sitemap | exclude | |

---

## Done definition (all phases)

- [ ] Public HTML heads complete (title, description, canonical, OG, Twitter)  
- [ ] Event JSON-LD valid ([Rich Results Test](https://search.google.com/test/rich-results))  
- [ ] robots + sitemap live; junk HTML gone  
- [ ] Private/auth/admin noindex  
- [ ] Stock + upload images optimized; LCP attrs on heroes  
- [ ] Marketing JS without forced Echo  
- [ ] Slugs + redirects (Phase 3)  
- [ ] `FLUTTER_WEB_PARITY.md` updated for any public URL / media contract change  
| [ ] Feature tests green  
