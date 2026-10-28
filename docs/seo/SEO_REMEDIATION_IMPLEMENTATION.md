# SEO remediation implementation

**Date:** 17 October 2026  
**Scope:** Approved code and tests only. Production was not deployed. Production rows were not updated.  
**Verdict:** **CONDITIONAL**

The code matches the approved fixes and the new SEO tests pass. The live site still behaves as in the Phase 0 baseline until these files are deployed. Search Console is not verified. Placeholder and template events were audited and left unchanged.

## 1. Root causes addressed

| ID | Root cause | What changed |
|---|---|---|
| F3 | `http` and `www` answered 200 | Apache and Laravel now 301 those hosts to `https://funnity.com` in one hop for GET/HEAD |
| F4 | `/events/` 301'd through `/public/events` | Trailing-slash rules no longer emit `/public/` |
| F1 (partial) | Homepage title and Organization node did not identify the product | Brand-first title, factual description, visible brand line, Organization `description` + `contactPoint` |
| F5 | Static sitemap `lastmod` was `now()` | Static URLs omit `lastmod`. Event `lastmod` stays `updated_at` |

Not changed, on purpose:

- No events unpublished, deleted, or `noindex`'d (Task 4).
- Public profiles stay `index,follow` (Task 5).
- No About page published (proposal only, below).
- No Search Console token added (Task 6 is a handoff).

## 2. Files changed

| File | Change |
|---|---|
| `.htaccess` | Production-host 301s for scheme, `www`, and trailing slash. ACME path excluded. POST/PUT not redirected. |
| `public/.htaccess` | Trailing slash redirects to the path without `/public`. |
| `app/Http/Middleware/EnforceCanonicalUrl.php` | Same rules in PHP so tests and `php artisan serve` match Apache. |
| `app/Http/Middleware/RedirectPublicPrefix.php` | Removed. Its `/public` behavior lives in `EnforceCanonicalUrl`. |
| `bootstrap/app.php` | Prepends `EnforceCanonicalUrl`. |
| `app/Support/Seo.php` | Homepage title, description, Organization and WebSite JSON-LD. |
| `app/Http/Controllers/Web/HomeController.php` | Passes only the highlight image into `Seo::forHome`. |
| `app/Http/Controllers/Web/SitemapController.php` | Omits `lastmod` on static URLs. |
| `resources/views/components/seo/meta.blade.php` | Optional absolute document title (no extra `· Funnity` suffix). |
| `resources/views/layouts/{home,app,dashboard,admin}.blade.php` | Pass the absolute-title flag. |
| `resources/views/components/community/hero.blade.php` | One brand line above the existing H1. H1 copy unchanged. |
| `lang/en/design.php`, `lang/ar/design.php` | Brand title, description, and visible line. Arabic follows the privacy-policy wording. |
| `tests/Feature/CanonicalUrlTest.php` | New redirect tests. |
| `tests/Feature/RedirectPublicPrefixTest.php` | Expects the apex origin. |
| `tests/Feature/SeoFoundationTest.php` | Homepage brand tags and sitemap `lastmod`. |
| `docs/SEO_OPS.md` | Property and sitemap URL corrected from `www` to `https://funnity.com`. |
| `docs/FLUTTER_WEB_PARITY.md` | Web-only SEO note and reference base URL set to the apex. No Flutter API change. |

## 3. Before / after

| Request | Before | After (once deployed) |
|---|---|---|
| `http://funnity.com/events?q=cairo` | 200 | 301 `https://funnity.com/events?q=cairo` |
| `http://www.funnity.com/safety` | 200 | 301 `https://funnity.com/safety` |
| `https://www.funnity.com/privacy-policy` | 200 | 301 `https://funnity.com/privacy-policy` |
| `http://www.funnity.com/events/?q=cairo` | 200, then slash chain | 301 `https://funnity.com/events?q=cairo` |
| `https://funnity.com/events/` | 301 `/public/events`, then 301 `/events` | 301 `https://funnity.com/events` |
| `POST` to a `www` or HTTP URL | n/a | Not redirected (body preserved) |
| Homepage `<title>` | `Meet your neighbors · Funnity` | `Funnity — neighborhood meetups` |
| Homepage meta description | City or “meeting up nearby” line | Factual platform description from the privacy policy |
| Homepage H1 | Meet your neighbors | Unchanged. A brand line sits above it. |
| Organization JSON-LD | name, url, logo | Adds description and support contact. No `sameAs`. |
| Static sitemap `lastmod` | Generation timestamp | Omitted |
| Event sitemap `lastmod` | `updated_at` | Unchanged |

Canonical origin remains `APP_URL` (`https://funnity.com` in production). Local hosts that are not `funnity.com` or `www.funnity.com` are not forced onto that host unless the path is a `/public` or trailing-slash duplicate, which still redirects to `APP_URL`.

## 4. Tests executed

Command: `php artisan test` on 17 October 2026.

| Suite | Result |
|---|---|
| `CanonicalUrlTest` | PASS (6) |
| `RedirectPublicPrefixTest` | PASS (3) |
| `SeoFoundationTest` | PASS, including homepage JSON-LD and sitemap `lastmod` |
| `SeoHardeningTest`, `HomePageTest`, `EventSlugRoutingTest`, `PublicProfileTest` | PASS |
| Full suite | **227 passed, 5 failed** |

The five failures do not call the new SEO code:

| Test | Assertion | Why it is outside this change |
|---|---|---|
| `MediaConfigTest` (2) | Expected disk `media`, got `r2` | PHPUnit does not override `MEDIA_DISK`. The production `.env` value is `r2`. |
| `UserAuthMethodTest` | Expected `both`, got `google+password` | Auth-method string. Untouched. |
| `BroadcastingAuthApiTest` (2) | Invalid JSON / 200 instead of 403 | POST broadcast auth. Canonical middleware ignores POST. |

SEO route, canonical, redirect, sitemap, and JSON-LD checks that this task added are green. Apache itself was not executed in PHPUnit. The middleware tests mirror those rules. Confirm with `curl -I` after deploy (`SEO_DEPLOYMENT_CHECKLIST.md`).

## 5. Evidence

- Middleware tests assert status 301 and the exact `Location` header, including query strings and a combined www + HTTP + trailing-slash request.
- `SeoFoundationTest` asserts the document title, meta description, brand line, `contactPoint` email `support@funnity.com`, and the absence of `sameAs`.
- Sitemap XML for `/` has no `lastmod` child. A published event's `lastmod` starts with its `updated_at` date (`2026-01-15` in the test).
- Read-only production query (no writes): 555 published or completed events. Details in `SITEMAP_CONTENT_AUDIT.md`.

## 6. Remaining risks

- Until deploy, production still serves four hosts with 200 and the `/public/` slash chain.
- `RewriteCond %{HTTPS} !=on` assumes Apache sees TLS directly. A future proxy that leaves `HTTPS` off while sending `X-Forwarded-Proto` would loop. There is no Cloudflare in front today.
- HSTS was not enabled. Turn it on only after the 301s are confirmed.
- Brand search still competes with funnityweb.com. Tags do not create an index entry.
- 471 eligible events have already ended. 60 titles repeat across cities and dates (template copy). Left in the sitemap.
- Public profiles remain indexable.
- Arabic still has no separate URL or hreflang.

## 7. Rollback

No migration and no data write. Restore the previous copies of the files in §2, including putting `RedirectPublicPrefix` back on the middleware stack, then reload Apache. Sitemap cache key `seo.sitemap.xml` expires within an hour or can be forgotten with `php artisan cache:forget seo.sitemap.xml` if you need the old `lastmod` behavior immediately after a rollback deploy.

## 8. Deployment readiness

**Not deployed. Do not deploy without a separate approval.**

Ready to deploy as an application change: yes, with the conditions in the checklist. Ready to claim Google indexing: no.

## 9. About page proposal (not published)

Do not add a route until this copy is approved. It uses only text already in the privacy policy and terms.

**Title:** About Funnity

**Body:**

Funnity is a neighborhood meetup platform. It helps people discover, create, join, and attend local community events, coordinate in event chat, and build safer in-person connections.

Events are organized by community members. Funnity is a technology platform, not the organizer of every meetup.

Questions: support@funnity.com, or the support page at https://funnity.com/support.

**Leave out until the owner supplies them:** street address, legal entity name, founding date, phone number, social profiles, ratings, and a claim that Funnity operates only in one country.

## 10. Public profile recommendation

See Task 5 in `SITEMAP_CONTENT_AUDIT.md`. Indexability was not changed.
