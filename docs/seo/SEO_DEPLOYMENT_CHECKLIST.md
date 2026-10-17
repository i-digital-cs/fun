# SEO deployment checklist

**State:** Not deployed.  
**Verdict:** **CONDITIONAL** — safe to schedule after owner approval. Not safe to describe as live.

## Before deploy

- [x] SEO tests pass (`CanonicalUrlTest`, `RedirectPublicPrefixTest`, `SeoFoundationTest`, `SeoHardeningTest`).
- [x] Full `php artisan test`: 227 passed. 5 failed for media disk, auth-method label, and broadcast auth. Those failures track `.env` and existing auth code, not the SEO diff. Re-check them if this deploy also changes media or broadcasting.
- [x] No database migration and no data update in this change.
- [x] Authentication routes are still POST-safe: canonical redirects apply to GET and HEAD only.
- [ ] Owner approval to deploy.
- [ ] Deploy window and a person who can restore the previous release.

## Deploy

1. Ship the application files listed in `SEO_REMEDIATION_IMPLEMENTATION.md`, including both `.htaccess` files.
2. Reload Apache so the new rewrite rules load. PHP-FPM reload alone does not pick up `.htaccess` until the next request, but a reload avoids stale workers.
3. Do not run destructive database commands.
4. Optional: `php artisan cache:forget seo.sitemap.xml` so the first sitemap request uses the new `lastmod` rules immediately. The key expires within an hour anyway.

## After deploy — prove the redirects

Use `curl -sI` and confirm **one** 301. `Location` must be the apex HTTPS URL. Query strings must survive.

| Request | Expected |
|---|---|
| `http://funnity.com/` | 301 `https://funnity.com/` |
| `http://www.funnity.com/events?q=cairo` | 301 `https://funnity.com/events?q=cairo` |
| `https://www.funnity.com/` | 301 `https://funnity.com/` |
| `https://funnity.com/events/` | 301 `https://funnity.com/events` and the body of that hop must not contain `/public/` |
| `https://funnity.com/` | 200 |
| `https://funnity.com/robots.txt` | 200, `Sitemap: https://funnity.com/sitemap.xml` |
| `https://funnity.com/sitemap.xml` | 200, home `<loc>` has no `<lastmod>`, event `<lastmod>` is an event timestamp |
| `https://funnity.com/.well-known/acme-challenge/` | Not forced through a host redirect that would break certificate renewal (the rewrite stops on `.well-known`) |

Then view source on `https://funnity.com/`:

- `<title>Funnity — neighborhood meetups</title>`
- meta description names Funnity as a neighborhood meetup platform
- canonical `https://funnity.com`
- `index,follow`
- Organization JSON-LD includes `description` and `support@funnity.com`
- no `sameAs`

Guest checks that must still work:

- `/login` 200 and `noindex`
- `/dashboard` and `/admin` 302 to `/login`
- a published event page 200

## Rollback

1. Restore the previous release (both `.htaccess` files and `bootstrap/app.php`).
2. Reload Apache.
3. Re-check `https://funnity.com/` returns 200.
4. If a redirect loop appears, the usual cause is `HTTPS` unset behind a proxy. Roll back first. Do not “fix” it by turning redirects off only for Googlebot.

## Do not mark done

- Search Console verification, sitemap submission, and indexing requests stay in `GOOGLE_SEARCH_CONSOLE_HANDOFF.md` and are still open.
- Do not enable HSTS until the four host checks above are one-hop 301s with no loop.
- Do not report that Google indexed the site unless URL inspection says so.
