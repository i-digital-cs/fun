# Phase 0 — SEO baseline

**Site:** https://funnity.com  
**Audit date:** 17 October 2026 (HTTP probes ~13:18–13:20 UTC)  
**Method:** Read-only. Application source plus live HTTP requests. No code or production data was changed.  
**Google Search Console:** Not available in this environment. Index counts below are not GSC figures.

## 1. Stack and rendering

| Item | Evidence |
|---|---|
| Framework | Laravel 12.69.2 (`composer.lock`), PHP `^8.2` (`composer.json`) |
| Frontend | Blade templates, Tailwind CSS 4, Vite 7. No separate SPA. |
| Rendering | Server-rendered HTML. Homepage response includes the H1, event copy, and navigation in the first HTML document (~95 KB, ~3.2k characters of visible text). |
| Assets | Built Vite files under `/build/assets/`. CSS and JS returned HTTP 200 to a Googlebot user agent. |
| Realtime | Laravel Reverb / Pusher JS for authenticated chat. Public pages do not depend on it for primary content. |
| Deployment | `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://funnity.com`. Apache (`Server: Apache`). No `cf-ray` header, so this origin is not behind Cloudflare from these requests. |
| Document root | Project-root `.htaccess` rewrites into `public/`. `public/.htaccess` is the Laravel front controller. |
| URL generation | `AppServiceProvider` forces root URL and HTTPS scheme from `APP_URL`. |

Authenticated member and admin areas stay behind auth. Unauthenticated visitors and the Googlebot user agent used here received the public homepage without a login wall.

## 2. Public route map

Indexable by current robots meta (guest, English locale):

| Route | Name | Role |
|---|---|---|
| `/` | `home` | Marketing home. `index,follow`. |
| `/events` | `events.index` | Discover. `index,follow` on the clean URL. `noindex,follow` when `page > 1` or filters/search are present. Canonical stays `/events`. |
| `/events/{id}/{slug}` | `events.show` | Published and completed events: `index,follow`. Other statuses: `noindex,nofollow`. Wrong slug 301s to the canonical slug. |
| `/u/{publicId}` | `users.public.show` | Public profile. `index,follow`. Not listed in the sitemap. |
| `/safety` | `safety.guidelines` | Safety guidelines. |
| `/privacy-policy` | `legal.privacy` | Privacy policy. |
| `/terms` | `legal.terms` | Terms. |
| `/support` | `legal.support` | Support, including `support@funnity.com`. |
| `/account-deletion` | `legal.account-deletion` | Account deletion help. In the sitemap. Not in the footer link list. |

`noindex,nofollow` or auth-gated (must stay that way):

| Route | Behavior observed |
|---|---|
| `/login`, `/register`, password-reset screens | Public HTML, `noindex,nofollow`. Also disallowed in `robots.txt`. |
| `/dashboard`, `/admin`, `/settings/*`, chats, identity, friends, notifications | Guest request: **302** to `/login`. Layouts default to `noindex,nofollow`. |
| `/api/*` | Disallowed in `robots.txt`. Not a public HTML surface. |

There is **no** public About page and **no** locale-prefixed URLs (`/en`, `/ar`). English and Arabic share the same paths. Locale is switched with `POST /locale` and stored for the session. `<html lang>` follows the active locale (`en` / `ar`). There are **no** `rel="alternate" hreflang` links. Open Graph emits `og:locale` and `og:locale:alternate` only.

## 3. Existing SEO implementation

No third-party SEO package. Logic is in-app:

| Piece | Location |
|---|---|
| Meta, canonical, Open Graph, Twitter card, JSON-LD | `resources/views/components/seo/meta.blade.php` |
| Page metadata builders | `App\Support\Seo` |
| `robots.txt` | `App\Http\Controllers\Web\RobotsController` at `GET /robots.txt` |
| XML sitemap | `App\Http\Controllers\Web\SitemapController`, cached 1 hour as `seo.sitemap.xml` |
| Sitemap cache bust on publish | `TransitionEventStatusService` |
| Health command | `php artisan seo:health` |
| Tests | `tests/Feature/SeoFoundationTest.php`, `SeoHardeningTest.php`, parts of `EventSlugRoutingTest.php` |
| Ops notes | `docs/SEO_OPS.md` |

`docs/SEO_OPS.md` tells the owner to verify **`https://www.funnity.com`** and submit `https://www.funnity.com/sitemap.xml`. Production `APP_URL` and live canonicals use **`https://funnity.com`** (apex, no `www`). Those instructions disagree with the canonical origin.

## 4. Live HTTP baseline

Probes used `User-Agent: Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)` and did not follow redirects unless noted. No `X-Robots-Tag` was present. Responses were `Cache-Control: no-cache, private`. No `Strict-Transport-Security` header.

### 4.1 Hosts (no redirect)

| Request | Status | Redirects | Canonical in HTML |
|---|---|---|---|
| `https://funnity.com/` | 200 | 0 | `https://funnity.com` |
| `http://funnity.com/` | 200 | 0 | `https://funnity.com` |
| `https://www.funnity.com/` | 200 | 0 | `https://funnity.com` |
| `http://www.funnity.com/` | 200 | 0 | `https://funnity.com` |

The HTML canonical is consistent. The server still **serves all four hosts with 200** instead of permanently redirecting to `https://funnity.com`.

### 4.2 Representative pages (`https://funnity.com`)

| URL | Status | Robots meta | Canonical | Title |
|---|---|---|---|---|
| `/` | 200 | `index,follow` | `https://funnity.com` | Meet your neighbors · Funnity |
| `/events` | 200 | `index,follow` | `https://funnity.com/events` | Discover · Funnity |
| `/events?q=cairo` | 200 | `noindex,follow` | `https://funnity.com/events` | Discover · Funnity |
| `/events/503/zamalek-football-match` | 200 | `index,follow` | self | Zamalek football match · Funnity |
| `/events/602/modi-praesentium-itaque-dolor` | 200 | `index,follow` | self | Modi praesentium itaque dolor. · Funnity |
| `/privacy-policy` | 200 | `index,follow` | self | Privacy Policy · Funnity |
| `/terms` | 200 | `index,follow` | self | Terms of Service · Funnity |
| `/support` | 200 | `index,follow` | self | Support · Funnity |
| `/safety` | 200 | `index,follow` | self | Safety guidelines · Funnity |
| `/account-deletion` | 200 | `index,follow` | self | How to delete your account · Funnity |
| `/login` | 200 | `noindex,nofollow` | self | Log in · Funnity |
| `/dashboard`, `/admin` | 302 → `/login` | login page is noindex | — | — |
| `/robots.txt` | 200 | `text/plain` | — | Allows `/`, disallows private prefixes, lists sitemap |
| `/sitemap.xml` | 200 | `application/xml` | — | 562 URLs, all `https://funnity.com` |

Homepage description (live): “Your neighbors are meeting up nearby this week. Grab a spot before they fill up.”  
H1: “Meet your neighbors”.  
`<html lang="en" dir="ltr">`. Viewport meta is present.

Homepage JSON-LD is valid JSON and contains `Organization` (name, url, logo only) and `WebSite` with a `SearchAction` targeting `/events?q={search_term_string}`. The logo URL responded as an SVG under `/storage/media/branding/funity-mark.svg` (filename spelling `funity`). Event pages include `Event` and `BreadcrumbList` JSON-LD.

Approximate time-to-first-byte from this network (lab, not CrUX): `/` ~0.24s, `/events` ~0.14s, `/sitemap.xml` ~0.08s. Core Web Vitals field data was **not** measured in this phase.

### 4.3 Trailing slash

`https://funnity.com/events/` returns **301** to `https://funnity.com/public/events`, which **301**s to `https://funnity.com/events` (200). The same two-hop pattern occurs for `/safety/`, `/privacy-policy/`, and an event URL with a trailing slash. Slashless sitemap URLs are unaffected. The intermediate `/public/…` URL is an Apache rewrite leak (`/.htaccess` plus `public/.htaccess` trailing-slash rule).

### 4.4 Sitemap contents

Live `https://funnity.com/sitemap.xml`:

- 562 `<url>` entries.
- 7 static URLs: `/`, `/events`, `/safety`, `/privacy-policy`, `/terms`, `/support`, `/account-deletion`.
- 555 event URLs. Zero `/u/` profile URLs. Zero query strings.
- Every `loc` is `https://funnity.com…`.
- Static `lastmod` values were identical to the request time (`2026-10-17T13:18:28+00:00`) because `SitemapController` sets them with `now()`.
- Event `lastmod` values vary (7 distinct timestamps in the event set) and come from `updated_at`.
- `https://www.funnity.com/sitemap.xml` also returns 200 and the same apex URLs (duplicate host, same document).

`robots.txt` body (live):

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

Sitemap: https://funnity.com/sitemap.xml
```

CSS and JS are not disallowed.

### 4.5 Internal links (homepage HTML)

The homepage HTML includes crawlable links to home, discover (`/events`), safety, privacy, terms, support, register, login, and individual events. Footer component: `resources/views/components/community/footer.blade.php`. There is no About link because that route does not exist.

## 5. What this baseline does **not** prove

- It does not prove Google has or has not indexed the site. See `INDEXING_ROOT_CAUSE.md`.
- It does not include Search Console coverage, manual actions, or CrUX.
- It does not include a Lighthouse or PageSpeed run.
- Placeholder-looking event copy was confirmed on at least one live URL (`/events/602/modi-praesentium-itaque-dolor`). A spread of 11 other sitemap event URLs had ordinary titles. This audit did not classify all 555 events.
