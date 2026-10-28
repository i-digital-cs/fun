# SEO final release gate

Date: 17 October 2026. This directory is the Apache document root for https://funnity.com. No deploy command was run for this gate, and no event, user, or notification row was updated.

**Verdict: CONDITIONAL**

Technical SEO code is tested and the live host already answers the canonical redirects from the working-tree `.htaccess` files. Content cleanup is a separate decision. Four placeholder events are not uniformly disposable, and the 500 near-duplicate events stay published.

## Technical SEO readiness

Ready to keep, with owner acknowledgement that the redirect files are already being served:

- One-hop host canonicalization to `https://funnity.com`, query string preserved, GET/HEAD only.
- Trailing slash no longer chains through `/public/`.
- Homepage document title, description, visible brand line, and Organization/WebSite JSON-LD use verified product facts only.
- Sitemap static URLs no longer emit `lastmod` equal to the request time. Event `lastmod` still comes from `updated_at`.
- Public profiles stay `index,follow` and still emit Person JSON-LD. `SEO_PUBLIC_PROFILES_INDEXABLE` defaults to `true`. Setting it to `false` later emits `noindex,follow` and omits Person JSON-LD. Profiles are not in the sitemap in either mode. The API profile payload is unchanged.
- Production `.env` was not edited.

Full suite after the profile flag: **233 passed, 985 assertions**, 17 October 2026.

## Content remediation readiness

Not approved for execution.

### Placeholder events

There is no unpublish-to-draft transition. A published event can be cancelled by an event manager or by admin moderation. Cancel removes it from the sitemap and from `index,follow`. It does not delete members, messages, ticket codes, or notifications. A completed event cannot be cancelled by `TransitionEventStatusService` or `ModerateEventService`. There is no payments table. `cost_per_person` is a display field. Ticket codes are membership strings.

| ID | Status | What the row is | Dependencies | Existing cancel path |
| --- | --- | --- | --- | --- |
| 501 | published | Title “Title”, description “Description”. Creator user 1, `@example.com`, platform super admin. | 1 owner membership. No messages, join requests, favorites, ratings, reports, or notifications. No cost. | Supported. Cancel leaves the owner membership in place. |
| 502 | published | Title “yyyy”, description “yttttt”. Creator `@example.com`. | 1 owner membership, 1 message and 1 chat read, both `@example.com`. No join requests, favorites, ratings, reports, notifications, or cost. | Supported. Cancel does not delete the message. |
| 503 | completed | Real title “Zamalek football match”, description is the token “Description”. Creator `@gmail.com`, not an example domain. | Owner plus one `@example.com` member. 22 messages (12 `@gmail.com`, 10 `@example.com`). 2 join requests (1 accepted `@example.com`, 1 pending `@gmail.com`). 12 join-request notifications. 3 chat reads. No favorites, ratings, reports, or cost. | **Not supported.** Completed cannot transition to cancelled. |
| 602 | published | Latin filler title and description. Creator `@example.org` (documentation domain). `cost_per_person` 80.00 and expected-cost text are display fields, not charges. | 1 owner membership. No messages, join requests, favorites, ratings, reports, or notifications. | Supported. |

All four are still `index,follow` and sitemap-eligible. None were modified.

**Unpublish plan, not executed**

1. Do not delete any of these rows.
2. After a separate owner approval, an admin may cancel **501, 502, and 602** through the existing moderation cancel action. That is the supported way to drop them from the sitemap.
3. Do not cancel **503** with the current rules. The description is placeholder text, but the event has a non-example organizer, chat, join requests, and notifications. Improving or replacing the description is a content edit, and a completed-to-cancelled override would be a new product rule. Neither is part of this release.

### The 500 near-duplicates

These are demo seed rows, not 500 independent events that happen to share titles.

Evidence, read-only:

- 500 events whose description starts with `Join us for `, IDs 1–500.
- 400 `completed` and 100 `published`, matching `DemoCommunitySeeder`.
- Every creator is `@example.com`.
- No member, message author, or join-request user outside `@example.com`.
- Messages: 2. Join requests: 4 (3 cancelled, 1 pending). Favorites: 1. Ratings: 0. Uploaded images: 0.
- 309 rows have `cost_per_person` set. That matches the seeder’s display cost. There is no payment ledger.
- 60 distinct titles. `created_at` is spread from March through November 2026 because the seeder backdates it; the ID range is one contiguous block.
- 82 are still current or upcoming by end date, 418 have ended. Expiry is not the reason they look alike.

Classification:

| Group | Count | Recommended action |
| --- | --- | --- |
| Demo / seeded (`Join us for `, `@example.com` creators, IDs 1–500) | 500 | Leave published. Do not mass-cancel or noindex. A later cleanup can cancel the seed block only after the owner accepts that demo members, the two messages, four join requests, and one favorite disappear from public discovery. |
| Egypt catalog (`EgyptManualEventsSeeder`, owner `egypt.catalog@funnity.com`) | 50 published | Historical catalog with its own descriptions. Keep indexable. |
| Other non-template published/completed | 5 | 501, 502, 503, 602 above, plus one other published event with a `@gmail.com` organizer. Treat that last event as a real event. Do not fold it into the seed cleanup. |

Duplicate titles inside the seed block are the seeder’s title pool reused across cities and dates. They are separate rows with separate memberships. They are still demo content.

## Production redirect preflight

Checked 17 October 2026 against the live host. Apache returned the first response. No `cf-ray`, no `Via`, no HSTS. `apachectl` and the vhost files were not readable from this account, so the TLS vhost itself was not inspected. LiteSpeed was not indicated.

| Check | Result |
| --- | --- |
| `https://funnity.com/` | 200, no Location |
| `http://funnity.com/events?q=preflight` | 301 to `https://funnity.com/events?q=preflight` |
| `https://www.funnity.com/events?q=preflight` | 301 to the apex, query kept |
| `http://www.funnity.com/safety?x=1` | 301 to `https://funnity.com/safety?x=1` |
| `https://funnity.com/events/` | 301 to `https://funnity.com/events` (not `/public/events`) |
| POST `http://www.funnity.com/login` | 419, no Location. Login POST is not redirected. |
| Loop | Apex HTTPS stays 200, so Apache is seeing TLS. A proxy that clears `HTTPS` without a trusted `X-Forwarded-Proto` would make the root rule loop. No such proxy is visible. |

These 301s are already live because the edited `.htaccess` files sit in the document root. Middleware is the safety net for `php artisan serve` and tests. Apache answers first on this host.

## Production risks

| Risk | Level | Note |
| --- | --- | --- |
| Redirect files already live | Medium | Rollback is restoring the previous `.htaccess` and `public/.htaccess`. No Apache reload is required for `.htaccess`. |
| Proxy added later | High | Root rule treats `%{HTTPS} !=on` as “redirect to https”. Do not put a TLS-terminating proxy in front without updating that condition. |
| Placeholder 501, 502, 602 still indexable | Medium | Cancel only after the approval below. |
| Event 503 still indexable | Medium | Description is a placeholder; the activity is not. |
| 500 seed events in the sitemap | Medium | Accepted for this release. Do not bulk-cancel. |
| Profiles remain indexable | Low | Flag exists and is off (meaning indexing stays on). |
| Google has not been given the sitemap by this project | High for discovery | Search Console was not accessed. |

## Approval requests

Answer these separately. This document does not apply any of them.

1. **Acknowledge the technical SEO tree**, including the `.htaccess` rules already being served. No further file deploy is proposed.
2. **Cancel events 501, 502, and 602** through admin moderation. Do not delete them.
3. **Leave event 503** as completed, or approve a content edit of its description outside this gate. Do not force a status change.
4. **Leave the 500 seed events and the 50 Egypt catalog events** indexed.
5. **Leave public profiles indexable.** To switch later, set `SEO_PUBLIC_PROFILES_INDEXABLE=false` in the environment and reload PHP. Do not add those profiles to the sitemap.

## Release package

Included. These are the working-tree SEO and test-environment files. No bulk data change is in this set.

Application and Apache:

- `.htaccess`
- `public/.htaccess`
- `app/Http/Middleware/EnforceCanonicalUrl.php` (new)
- `app/Http/Middleware/RedirectPublicPrefix.php` (removed)
- `bootstrap/app.php`
- `app/Support/Seo.php`
- `config/seo.php` (new, default keeps profiles indexable)
- `app/Http/Controllers/Web/HomeController.php`
- `app/Http/Controllers/Web/SitemapController.php`
- `resources/views/components/seo/meta.blade.php`
- `resources/views/components/community/hero.blade.php`
- `resources/views/layouts/home.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/dashboard.blade.php`
- `resources/views/layouts/admin.blade.php`
- `lang/en/design.php`
- `lang/ar/design.php`
- `.env.example` (commented flag only)

Tests and docs:

- `phpunit.xml` (forces the test suite off Pusher and off R2; production `.env` still uses Pusher and R2)
- `tests/Feature/CanonicalUrlTest.php`
- `tests/Feature/RedirectPublicPrefixTest.php`
- `tests/Feature/SeoFoundationTest.php`
- `tests/Feature/PublicProfileTest.php`
- `tests/Feature/BroadcastingAuthApiTest.php`
- `tests/Unit/UserAuthMethodTest.php`
- `docs/SEO_OPS.md`
- `docs/FLUTTER_WEB_PARITY.md`
- `docs/seo/` (audit and this gate)

Excluded: any `UPDATE`/`DELETE` of events or users, sitemap removal of the seed block, profile noindex in production, and unrelated features.

### Commands that match this host

This app is already the live tree. Do not rsync it onto itself.

```bash
php artisan test
php artisan seo:health
php artisan cache:forget seo.sitemap.xml
```

Forget the sitemap cache only when the owner wants the new static-URL sitemap on the next request. PHP opcode cache may need a reload before `config/seo.php` and `Seo.php` are visible; the `.htaccess` rules are already visible.

### Rollback

Restore the previous `.htaccess`, `public/.htaccess`, `bootstrap/app.php`, `app/Support/Seo.php`, `SitemapController.php`, the layout/meta/lang files, and delete `EnforceCanonicalUrl.php` and `config/seo.php` if the release is rejected. Then:

```bash
php artisan cache:forget seo.sitemap.xml
```

Reload PHP-FPM or the host PHP workers if compiled views or config are cached. Apache does not need a restart for `.htaccess`.

Rollback triggers:

- `https://funnity.com/` is not 200, or a second request to that URL is a redirect.
- Any canonical redirect sends the browser to `/public/...`.
- A redirect drops the query string or issues more than one hop.
- POST `/login` returns 301 or 302.
- Homepage title, canonical, or JSON-LD disappears.

### Post-change checks

```bash
curl -sI https://funnity.com/ | head -n 15
curl -sI "http://funnity.com/events?q=1" | awk 'BEGIN{IGNORECASE=1} /^HTTP|^location:/'
curl -sI "https://www.funnity.com/events?q=1" | awk 'BEGIN{IGNORECASE=1} /^HTTP|^location:/'
curl -sI https://funnity.com/events/ | awk 'BEGIN{IGNORECASE=1} /^HTTP|^location:/'
curl -sI -X POST http://www.funnity.com/login | awk 'BEGIN{IGNORECASE=1} /^HTTP|^location:/'
curl -s https://funnity.com/robots.txt
curl -s https://funnity.com/sitemap.xml | head
```

Confirm the homepage title is `Funnity — neighborhood meetups`, robots is `index,follow`, and a public profile is still `index,follow` while the flag stays at its default.

### Search Console follow-up

Not done. When the owner is ready, use `docs/seo/GOOGLE_SEARCH_CONSOLE_HANDOFF.md`: Domain property for `funnity.com`, DNS TXT verification, then submit `https://funnity.com/sitemap.xml`. Do not treat a tool’s empty `site:` result as an index count.

## Tests and evidence

- `php artisan test`: 233 passed, 985 assertions.
- `php artisan test --filter=PublicProfileTest`: default page is `index,follow` with Person JSON-LD; with the flag forced off, the same URL is `noindex,follow`, has no Person JSON-LD, still shows the name, and the sitemap has no `/u/` URL.
- Database reads above were `SELECT` only.
- Live header preflight is in the table above.
