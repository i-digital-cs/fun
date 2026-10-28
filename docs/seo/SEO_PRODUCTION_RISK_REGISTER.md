# SEO production risk register

**Date:** 17 October 2026  
**Hosting observed in Phase 0:** Apache, TLS terminated on the origin, no `cf-ray` header.  
**Rules:** `.htaccess` (project root and `public/.htaccess`) and `App\Http\Middleware\EnforceCanonicalUrl`.

## Are the rules complementary or redundant?

They are complementary. They are not meant to run as two hops.

| Layer | When it runs | What it does |
|---|---|---|
| Project-root `.htaccess` | Apache, document root is the project directory | For `funnity.com` / `www.funnity.com`, GET and HEAD only: one 301 to `https://funnity.com` plus the path, query string kept. Trailing slash is removed in that same redirect. `/.well-known/` stops before the redirect so certificate challenges still work. `/public/...` in the original request 301s to the path without `public`. |
| `public/.htaccess` | Apache, if the request was rewritten into `public/` or the vhost root is already `public/` | Trailing slash 301s to the clean path. A URI that still contains `/public/` redirects to the path without that prefix, so the old `/events/` → `/public/events` hop does not return. |
| `EnforceCanonicalUrl` | PHP, after Apache has already redirected, or when Apache is not in front (`php artisan serve`, PHPUnit) | Same GET/HEAD outcome using `APP_URL`: strip `/public`, strip a trailing slash, send `www` and HTTP on the canonical host to `APP_URL`. Other hostnames are left alone unless the path itself is a `/public` or slash duplicate. |

On production Apache, a non-canonical GET is answered by `.htaccess` and never reaches PHP. The middleware is the safety net for servers that do not read those files. It does not add a second redirect when Apache already sent the browser to the apex.

POST, PUT, PATCH, and DELETE are not redirected in either layer. Login, registration, and `/api/*` writes keep their body. A POST to `http://www.funnity.com/login` is still accepted on that host. HSTS is intentionally not turned on until the GET redirects are confirmed, because HSTS on a bad host is hard to undo.

## Risks

| ID | Risk | Severity | Evidence | Status |
|---|---|---|---|---|
| R1 | TLS-terminating proxy sets `HTTPS` off at Apache. The rule `HTTPS !=on` would 301 to HTTPS forever. | HIGH if a proxy is added. Not observed today. | Phase 0: direct Apache HTTP/2, no Cloudflare header. Laravel does not trust `X-Forwarded-Proto` unless `TrustProxies` is configured. The `.htaccess` rule does not read `X-Forwarded-Proto`. | Open for a future proxy. Current origin is safe. Do not put Cloudflare “Flexible SSL” in front without changing this rule. |
| R2 | Live vhost not exercised. | MEDIUM | Redirects are covered by `CanonicalUrlTest` against the middleware. Apache rewrite was reviewed, not requested against production in this pass. | Close with the curl list in `SEO_DEPLOYMENT_CHECKLIST.md` after an approved deploy. |
| R3 | Placeholder events 501, 502, 503, 602 stay in the indexable sitemap set. | MEDIUM for content quality. Not a redirect defect. | `EVENT_CONTENT_QUALITY_AUDIT.md`. Rows were not modified. | Owner decision. |
| R4 | Null broadcaster used to hide channel auth in tests. | LOW in production. Production connection is `pusher`. | Baseline test: empty 200 for every private channel when `BROADCAST_CONNECTION=null`. After the test fix, own channel signs and the other user is 403. | Test gap closed. Do not set production `BROADCAST_CONNECTION=null`; that driver does not enforce channel callbacks. |
| R5 | Test runs inherited `MEDIA_DISK=r2` from `.env`. | LOW after the phpunit pin. | Baseline `MediaConfigTest` saw `r2`. `phpunit.xml` now forces local disk names. `.env` was not changed. | Closed for the suite. |
| R6 | `authMethod()` contract drift. | LOW | Code returns `google+password`. The old test expected `both`. Admin labels already map `google+password` to the “both” copy. | Test aligned. No auth change. |
| R7 | Query loss or redirect loop on the canonical host. | LOW on the reviewed rules | Substitution `https://funnity.com%{REQUEST_URI}` and `https://funnity.com/%1` do not include `?`, so Apache keeps the original query. Middleware tests assert `?q=cairo` survives www + HTTP + trailing slash in one 301. Root `/` is not stripped to an empty path. | Re-check on the vhost (R2). |
| R8 | API GET on `www` will 301 to the apex. API POST will not. | LOW | GET canonicalization is intended. Mobile clients should call `https://funnity.com`. A POST to the www host still hits that host. | Documented. Not changed, so bodies are preserved. |
| R9 | LiteSpeed. | LOW | LiteSpeed reads Apache rewrite syntax, including `RewriteCond` and `R=301`. The same loop risk as R1 applies if `HTTPS` is unset. | No LiteSpeed header was seen in Phase 0. |

## Release posture

Not **BLOCKED**. GET/HEAD canonicalization does not rewrite authentication posts, and the full suite passed (232 tests).

Not **PASS**. Four test events remain indexable, and the Apache rules are not yet confirmed on the live vhost.

**CONDITIONAL**, matching `SEO_RELEASE_READINESS.md`.
