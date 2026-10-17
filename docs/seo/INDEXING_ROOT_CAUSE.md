# Indexing root cause

**Site:** https://funnity.com  
**Date:** 17 October 2026  
**Scope:** Read-only. No production changes.

## Verdict

The homepage is **technically indexable**. It is not blocked by `noindex`, `robots.txt`, authentication, or a JavaScript-only render.

What the public web search index returned on this date is a different fact: queries for the brand did **not** surface `funnity.com`. They surfaced an unrelated business that already uses the name Funnity (`funnityweb.com`, Coimbra). Search Console data is **unavailable**, so this is not an official index count and not a manual-action finding.

The leading explanation is discovery and entity confusion, not a missing meta-tag package:

1. Google has no confirmed sitemap submission or verified property for this origin, and public search tools returned no `funnity.com` URLs.
2. The query “Funnity” already belongs, in that index, to another events company with citations (website, directory, LinkedIn, maps).
3. Host duplicates (`http`, `www`) answer 200 instead of 301. Canonical tags point at the apex, so this is a consolidation weakness, not a hard block.
4. Brand signals on the homepage are a slogan (“Meet your neighbors”) plus a thin `Organization` node (name, url, logo). There is no About page and no verified `sameAs` profiles in the markup.

Adding more keywords will not fix (1) or (2).

## Scorecard (this audit only)

| Area | Result | Basis |
|---|---|---|
| Google discoverability | **Unknown** | No Search Console access. Public web search returned no `funnity.com` URLs. |
| Homepage indexability | **PASS** | 200, `index,follow`, self canonical `https://funnity.com`, content in initial HTML. |
| Robots.txt | **PASS** with gaps | 200, allows public content, lists the apex sitemap, does not block CSS/JS. Does not fix host duplicates. |
| Sitemap | **PASS** with defects | Valid urlset, 562 HTTPS apex URLs, indexable types only. Static `lastmod` is generation time. |
| Canonical URLs | **FAIL** | Tags are consistent, but HTTP and `www` do not 301 to `https://funnity.com`. Trailing-slash URLs 301 through `/public/…`. |
| Meta tags | **PASS** with brand weakness | Unique titles on sampled pages, descriptions present, viewport and `lang` present. Homepage title/H1 do not lead with the brand. |
| Structured data | **PASS** syntax, **thin** entity | Homepage JSON parses. Organization lacks description, contact, and `sameAs`. |
| Internal linking | **PASS** | Homepage and footer expose crawlable links to primary public pages. No About URL exists to link. |
| Mobile SEO | **Not fully measured** | Viewport is correct. No mobile Lighthouse run in this phase. |
| Core Web Vitals | **Unavailable** | No CrUX and no Lighthouse. Lab TTFB only (~0.14–0.24s from this network). |
| SEO regression tests | **Not re-run** | Existing tests cover meta/robots/sitemap content. They do not cover host 301s. |
| Production verification | **Read-only pass on current HTML** | Live probes above. No deploy in this phase. |

## 1. Search visibility

Tool: Cursor web search on 17 October 2026. This is **not** a browser Google session and **not** Search Console. `site:` is not an exhaustive index count. Treat the rows as observed tool output.

| Query | Observed |
|---|---|
| `site:funnity.com` | Tool response: “No results found”. |
| `"Funnity"` / `funnity.com` / `Funnity platform` | Results were `https://funnityweb.com/` (“Organização e Animação de Eventos em Coimbra”), `casamentos.pt` listing for that business, LinkedIn company `funnity` (homepage listed as funnityweb.com), a YouTube channel, and a maps/place record in Coimbra. **No result URL was on `funnity.com`.** |
| `"funnity.com"` | Same competing set. The exact host did not appear in the result list returned by the tool. |

**Status:** UNVERIFIED as an official Google index report. Consistent across these queries: this domain was absent from the returned results, and another “Funnity” already occupies the name.

## 2. Google Search Console

**Access: unavailable.**

Checked:

- No Search Console credential, site-verification token, or HTML verification file in the repo or non-secret environment keys.
- `docs/SEO_OPS.md` describes a manual checklist. It does not record that verification was completed.
- That checklist names the property `https://www.funnity.com`, which is not the canonical host.

Do not treat any coverage, manual action, or impression number as known.

### Owner checklist (required before anyone claims index status)

1. In [Google Search Console](https://search.google.com/search-console), add a **Domain** property for `funnity.com` (DNS TXT), or a URL-prefix property for `https://funnity.com`. Do not make `www` the only property.
2. Confirm the DNS token or HTML file is the one Google issued. Do not invent a token in the repo until Google shows it.
3. Open **Pages** (indexing) and record: indexed count, “Discovered — currently not indexed”, “Crawled — currently not indexed”, duplicate canonical, and soft 404.
4. URL Inspection on `https://funnity.com/` and on one real event URL. Record “URL is on Google”, referring page, and last crawl.
5. Sitemaps: submit `https://funnity.com/sitemap.xml` only after host 301s exist, or submit it now and resubmit after the redirect fix. Note discovered vs indexed.
6. Check **Manual actions** and **Security issues**. Record “none” only if the UI says so.
7. Performance: filter query `funnity` and page `https://funnity.com/` for impressions, clicks, and position. Zero impressions means Google has not shown the URL, which is different from a ranking drop.
8. Experience → Core Web Vitals: record field data or “insufficient data”.

## 3. Findings

### F1 — Brand query is already owned by a different Funnity

- **Severity:** CRITICAL for branded search visibility. Not a crawl defect.
- **Route / component:** Homepage title and H1 (`Seo::forHome`, `resources/views/components/community/hero.blade.php`), `Organization` JSON-LD in `App\Support\Seo::forHome`. No About route in `routes/web.php`.
- **Root cause:** The string “Funnity” is an established events brand in Coimbra (`funnityweb.com` and third-party citations). This product is a neighborhood meetup platform (privacy copy: discover, create, join, and attend local community events). The homepage H1 is “Meet your neighbors”. Organization schema is only name, url, and logo. Nothing in the markup ties the entity to verified profiles, and no such profiles were confirmed in this audit, so none should be invented.
- **Evidence:** Web search results in §1. Live title `Meet your neighbors · Funnity`. JSON-LD keys on the homepage: `@type`, `name`, `url`, `logo` for Organization.
- **Impact:** Even a fully crawled homepage can fail to appear for “Funnity” while the other entity remains stronger. Eligible queries are descriptive (“neighborhood events”, city + activity) until the entity is distinct and cited.
- **Proposed fix (needs approval):** Keep the canonical origin. Put the brand in the title in a natural way (for example “Funnity — neighborhood events and meetups”) using existing product language from the privacy/terms copy, not new marketing claims. Add a short public About page only with facts already published (what the service does, `support@funnity.com`). Extend Organization with `description` and `contactPoint` from that same copy. Add `sameAs` only for profiles the owner verifies. Off-site: claim or create official profiles and consistent citations. Do not buy links.
- **Test results:** Not implemented.
- **Remaining risk:** Disambiguation can take months and is not guaranteed. The other company may keep the head query.

### F2 — No confirmed discovery path into Google’s index

- **Severity:** HIGH
- **Route:** `/sitemap.xml`, Search Console process (`docs/SEO_OPS.md`).
- **Root cause:** The site can be crawled, but this audit found no evidence Google was given the sitemap or that any external link points at `funnity.com`. Public search tools returned no URLs for the host.
- **Evidence:** `site:funnity.com` tool result empty. No verification file. Sitemap exists and is linked from `robots.txt` (`Sitemap: https://funnity.com/sitemap.xml`), which helps only after a crawler fetches robots or an external URL.
- **Impact:** A new or unlinked host can remain unindexed while every on-page tag is correct. Crawlability is not indexing.
- **Proposed fix:** Owner completes the Search Console checklist in §2 and requests indexing for `/` and a handful of real event URLs. Do not use the Indexing API for ordinary pages.
- **Test results:** Not applicable to code.
- **Remaining risk:** Inspection can still return “Discovered — currently not indexed” if Google treats the site as low demand. That status is a quality/demand outcome, not a robots bug.

### F3 — Four hosts return 200

- **Severity:** HIGH
- **Route:** Apache vhost / `.htaccess` (not Laravel). Affected URLs: `http://funnity.com/`, `http://www.funnity.com/`, `https://www.funnity.com/`, and the same paths on other public pages.
- **Root cause:** No permanent redirect to the canonical origin. Laravel canonical tags and `APP_URL` already use `https://funnity.com`, and `URL::forceScheme('https')` makes generated links HTTPS, but the web server still answers the other hosts.
- **Evidence:** HEAD/GET on 17 October 2026: all four homepage variants `200`, `num_redirects=0`. Each HTML document contained `<link rel="canonical" href="https://funnity.com">`. `www` sitemap also `200`.
- **Impact:** Google can consolidate on the canonical tag, and often does. Duplicate hosts still waste crawl, split signals if any external link uses `www` or `http`, and conflict with the ops doc that tells staff to verify `www`. Session cookies are also set on cleartext HTTP (security, separate from indexing).
- **Proposed fix:** At Apache (or the panel in front of it), 301 in one hop:
  - `http://funnity.com/*` → `https://funnity.com/*`
  - `http://www.funnity.com/*` → `https://funnity.com/*`
  - `https://www.funnity.com/*` → `https://funnity.com/*`
  Keep `.well-known` available for certificate renewal. Add HSTS only after HTTPS redirects are confirmed, to avoid locking clients onto a bad host. Update `docs/SEO_OPS.md` so the property and sitemap URL match `https://funnity.com`.
- **Test results:** Not implemented. Existing PHP tests never request a second host.
- **Remaining risk:** A mistaken rule can redirect loop. Verify with `curl -I` on all four hosts before and after.

### F4 — Trailing slash redirects through `/public/`

- **Severity:** MEDIUM
- **Route:** `/.htaccess` rewrite to `public/$1` combined with the trailing-slash rule in `public/.htaccess`.
- **Root cause:** A request such as `/events/` is redirected using a URI that still contains the internal `public` prefix. A second rule then strips `public`.
- **Evidence:** `https://funnity.com/events/` → 301 `https://funnity.com/public/events` → 301 `https://funnity.com/events` → 200. Same pattern for `/safety/`, `/privacy-policy/`, and `/events/503/zamalek-football-match/`.
- **Impact:** Sitemap URLs have no trailing slash, so the primary crawl path is fine. External or user links with a slash burn two hops and briefly expose `/public`.
- **Proposed fix:** Emit the slash redirect from the project-root rewrite, or base it on a URI that does not include `public`, so the Location is `https://funnity.com/events` in one hop.
- **Test results:** Not implemented.
- **Remaining risk:** Low if the rule is limited to GET and excludes real directories.

### F5 — Sitemap `lastmod` for static URLs is “now”

- **Severity:** MEDIUM
- **Route:** `App\Http\Controllers\Web\SitemapController`
- **Root cause:** Home, discover, safety, and legal URLs set `lastmod` to `now()` inside the cache closure. Event rows correctly use `updated_at`. Cache TTL is 3600 seconds, so static lastmod jumps on each rebuild.
- **Evidence:** Live sitemap static lastmod `2026-10-17T13:18:28+00:00` matched the fetch. Code: `'lastmod' => now()->toAtomString()` for the seven static entries.
- **Impact:** Not an indexing block. It trains crawlers to ignore `lastmod`.
- **Proposed fix:** Use real content timestamps (legal “Last updated: 28 September 2026” where that is the actual change, or file/translation mtime). Omit `lastmod` when unknown. Do not stamp every URL with the current time.
- **Test results:** `SeoHardeningTest` checks cache invalidation, not lastmod honesty.
- **Remaining risk:** None if dates are omitted rather than invented.

### F6 — At least one indexable URL is placeholder copy

- **Severity:** MEDIUM (quality). Could become HIGH if many sitemap URLs are filler.
- **Route:** `/events/602/modi-praesentium-itaque-dolor` (and any similar published rows).
- **Root cause:** Published/completed events are emitted as `index,follow` and included in the sitemap with no quality gate. This URL’s title and description are Latin filler.
- **Evidence:** Live title `Modi praesentium itaque dolor. · Funnity`, description `Laudantium dolorum iste at. Adipisci quod laborum pariatur impedit.`, robots `index,follow`, canonical self. A sample of 11 other event URLs from the sitemap had ordinary English or Arabic titles (for example Zamalek football match, حجز كورة خماسي).
- **Impact:** If Google crawls the sitemap, low-value URLs compete with real events and can contribute to “Crawled — currently not indexed”. This does not explain a total absence of the homepage by itself.
- **Proposed fix:** Unpublish or noindex filler events in production data (owner decision; do not delete blindly). Keep the sitemap limited to published/completed events that have a real title. Do not mass-generate landing pages.
- **Test results:** Not implemented.
- **Remaining risk:** Unpublishing changes member-visible data. Confirm with the owner which events are seed data.

### F7 — Arabic is not a separate crawlable URL

- **Severity:** LOW for indexing the English site. MEDIUM if Arabic queries are a growth goal.
- **Route:** `POST /locale`, `SetLocale` middleware, `components/seo/meta.blade.php`.
- **Root cause:** One URL serves both languages based on session. Googlebot received `lang="en"`. There is no hreflang pair.
- **Evidence:** Homepage HTML `lang="en"`. No `rel="alternate" hreflang` in the document. Translations exist in `lang/en` and `lang/ar`.
- **Impact:** Google indexes the default-language HTML it receives. Arabic copy is largely invisible to crawling. `og:locale:alternate` does not create an Arabic URL.
- **Proposed fix:** Do not add fake `/ar` pages in this pass. If Arabic landing pages are required later, give them their own URLs, reciprocal hreflang, and a single canonical per language. That is a product change, not a tag tweak.
- **Test results:** `LocaleSwitchTest` covers the session switch only.
- **Remaining risk:** Splitting URLs without unique content creates duplicates.

### F8 — Public profiles are indexable and absent from the sitemap

- **Severity:** LOW
- **Route:** `/u/{publicId}`, `Seo::forPublicProfile` (`index,follow`). `SitemapController` does not list them.
- **Root cause:** Profiles were left indexable while the sitemap was limited to home, legal, and events.
- **Evidence:** Code review. Live sitemap profile count: 0.
- **Impact:** Profiles are discovered only via internal links. Thin profiles can be indexed if linked. Omission from the sitemap is acceptable if profiles should not be a growth surface.
- **Proposed fix:** Decide explicitly. Prefer `noindex,follow` for member profiles unless a profile has a stable public bio worth ranking. Do not add every user to the sitemap by default.
- **Test results:** Not implemented.
- **Remaining risk:** noindex on profiles changes who can be found by name in Google. Owner decision.

### F9 — What was checked and is not the blocker

| Check | Result |
|---|---|
| Homepage `noindex` | Absent. `index,follow`. |
| `X-Robots-Tag` | Absent on sampled responses. |
| `robots.txt` blocking `/` or assets | No. `/build/assets/app-*.css` and `app-*.js` returned 200. |
| Auth wall on `/` | No. 200 for a logged-out Googlebot UA. |
| Soft 404 on `/` | No. Real H1 and event text. |
| Canonical pointing at another site | No. Apex HTTPS. |
| JS-only content | No. Primary copy is in the first HTML. |
| Cloudflare / WAF challenge | Not observed. `Server: Apache`, no challenge page for the Googlebot UA. |
| Private areas exposed | `/dashboard` and `/admin` 302 to `/login`. |

## 4. Prioritized remediation (do not start until approved)

| Order | Item | Type | Owner |
|---|---|---|---|
| 1 | Verify Search Console on `funnity.com` / `https://funnity.com`, submit `https://funnity.com/sitemap.xml`, inspect `/` | Ops | Site owner |
| 2 | 301 HTTP and `www` to `https://funnity.com` in one hop; fix the ops doc hostname | Server config | Owner + implementation |
| 3 | Collapse trailing-slash `/public/` chain to one 301 | `.htaccess` | Implementation |
| 4 | Homepage title and Organization description from existing product facts; About page only if copy already exists or owner supplies it | App | Implementation after copy approval |
| 5 | Honest sitemap `lastmod`; noindex or unpublish confirmed placeholder events | App + data | Owner confirms which events |
| 6 | Profile indexation decision | Product | Owner |
| 7 | Hreflang URLs | Later | Only if Arabic landing pages are in scope |
| 8 | Core Web Vitals lab + field | Measure after redirects | Do not strip features for a score |

Phases 2–9 in the original brief (structured data expansion, tests, deploy, 90-day roadmap) should wait for approval of items 2–5. Item 1 can start immediately and does not require a code deploy.

## 5. Explicit non-claims

- This document does not say Google has indexed or de-indexed the site.
- It does not report manual actions, security issues, or impression counts.
- It does not promise first-page rankings for “Funnity” or any other query.
- Crawl success on 17 October 2026 does not mean Googlebot has visited.
