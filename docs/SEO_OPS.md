# Technical SEO operations (Phase 4)

Use this after deploying Phases 1–3. Code already provides robots, sitemap, OG/JSON-LD, slugs, and CWV assets — this checklist is **ops + monitoring**.

## 1. Verify locally / on staging

```bash
php artisan seo:health --skip-http          # DB + filesystem
php artisan seo:health                      # also probes APP_URL over HTTP
php artisan seo:health --base=https://funnity.com
php artisan test --filter='SeoFoundationTest|EventSlugRoutingTest|SeoHardeningTest'
```

Expect: robots lists `Sitemap:` and Disallows `/admin`; sitemap includes `/` · `/events` · `/safety` · published event URLs with slugs; event HTML has `og:title` + JSON-LD.

## 2. Google Search Console

1. Verify a **Domain** property for `funnity.com` (DNS TXT). The canonical origin is `https://funnity.com`, not `www`.
2. **Sitemaps** → submit `https://funnity.com/sitemap.xml`.
3. Request indexing for `/` and one published `/events/{id}/{slug}` (URL Inspection).
4. Rich results: paste an event URL into [Rich Results Test](https://search.google.com/test/rich-results) — expect **Event** (+ BreadcrumbList).
5. Monitor **Coverage / Pages** for soft-404s and unexpected `noindex` on public URLs.

## 3. Bing Webmaster Tools

1. Import from GSC or verify the same domain.
2. Submit the same sitemap URL.
3. Optional: enable IndexNow later if you add push-on-publish.

## 4. Core Web Vitals monitoring

| Tool | What to check |
|------|----------------|
| [PageSpeed Insights](https://pagespeed.web.dev/) | `/` and a published event URL (mobile + desktop) |
| CrUX (Search Console → Experience → Core Web Vitals) | Field LCP / INP / CLS after traffic accumulates |
| Chrome DevTools Lighthouse | Confirm LCP image is a `*-640.webp` / `*-1280.webp`, not multi‑MB PNG |

**Targets (guide):** LCP &lt; 2.5s, INP &lt; 200ms, CLS &lt; 0.1 on home and event show.

## 5. Content hygiene

- Publishing without a description still works, but web/API flash a **soft warning** (`events.published_missing_description`).
- `php artisan seo:health` reports count of published events with empty descriptions.
- Prefer adding a short description before publish for better snippets and Event rich results.

## 6. When to revisit code

| Signal | Action |
|--------|--------|
| Sitemap &gt; 50k URLs or slow to generate | Split index + consider `spatie/laravel-sitemap` |
| Bilingual SERP needed | Locale URL epic (`/en`, `/ar` + hreflang) — see Phase 3 decision A |
| LCP still poor on mobile | Re-compress stock WebP; ensure uploads use card variant on lists |
| GSC “Duplicate without user-selected canonical” on Discover | Confirm filtered `/events?*` stay `noindex` |

## 7. Deploy reminder

After release:

1. `php artisan migrate` (slug column if not already)
2. `npm run build` (split JS + CSS)
3. `php artisan seo:health --base=https://funnity.com`
4. Submit / refresh sitemap in GSC
