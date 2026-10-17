# Public profile indexing impact

Date: 17 October 2026. The flag exists and is **not** switched off. Production `.env` does not set it. The default in `config/seo.php` is `true`.

```php
'public_profiles_indexable' => env('SEO_PUBLIC_PROFILES_INDEXABLE', true),
```

`true` means `/u/{publicId}` stays `index,follow` and emits Person JSON-LD. That is the live behavior.

## What `false` changes

`Seo::forPublicProfile` is the only reader. The public profile controller, the API, and the sitemap do not read the flag.

| Behavior | `true` (current) | `false` (not activated) |
| --- | --- | --- |
| HTTP status of `/u/{publicId}` | 200 for an active user | 200 |
| Deactivated user | 404 | 404 |
| Robots meta | `index,follow` | `noindex,follow` |
| Person JSON-LD | `@type` Person with `name`, `url`, and `image` when a photo exists | Omitted. No Person object is emitted. |
| Email, phone, documents | Not rendered | Not rendered |
| Name, public id, photo, city, hosted and joined counts | Visible | Visible |
| Links to up to six hosted events | Present, and `follow` is unchanged | Present. `noindex,follow` still allows those links to be crawled. |
| Sitemap | Profiles are not listed | Profiles stay unlisted |
| `robots.txt` | `Allow: /u/` | Unchanged. `noindex` is the page directive, not a `Disallow`. |
| Login, session, privacy, blocks, friendship | Unchanged | Unchanged |
| `GET /api` public profile payload | Unchanged. The flag is HTML only. | Unchanged |

Person data today is the display name, the profile URL, and an optional photo. It does not include email. That payload is accurate for a public page. Turning the flag off removes it rather than publishing a smaller Person object. A `noindex` URL should not also ask for a Person rich result. Event pages are separate: their Event JSON-LD can still name the organizer and link to `/u/{publicId}`. Those event pages are not changed by this flag.

## What does not break

- Existing profile URLs keep working. Nothing redirects and nothing starts returning 404 because of the flag.
- Navigation that points at `/u/{publicId}` still resolves.
- Event cards on the profile still link to `/events/{id}/{slug}`.
- Authentication is not consulted by `Seo::forPublicProfile`. A guest and a logged-in member see the same robots directive.
- Demo profile pages that link to seed events stay reachable. Hiding those events from search is the separate `search_indexable` proposal in `DEMO_EVENT_INDEXING_STRATEGY.md`.

## Production change, prepared and not applied

Add this only after an owner approval, in the production environment, not in the repo’s `.env.example` as a default flip:

```dotenv
SEO_PUBLIC_PROFILES_INDEXABLE=false
```

Then reload PHP. If config is cached on the host, run `php artisan config:clear` so the new value is read. Do not run `config:cache` until the rest of the environment is known to be complete.

Check before and after, without changing data:

```bash
php artisan config:show seo.public_profiles_indexable
```

Expected before the edit: `true`. Expected after: `false`.

Fetch one known public profile (replace the id):

```bash
curl -s https://funnity.com/u/PUBLIC_ID | tr '\n' ' ' | grep -o 'noindex,follow\|index,follow'
```

Confirm the HTML contains the person’s name and does not contain `"@type":"Person"` or an email address. Confirm `https://funnity.com/sitemap.xml` still has no `/u/` URL.

## Rollback

Set the variable back to `true`, or delete the line so the default applies, then reload PHP and clear config cache if it is used.

```dotenv
SEO_PUBLIC_PROFILES_INDEXABLE=true
```

The next profile response should be `index,follow` and include Person JSON-LD again. No database rollback is required.

## Tests already covering this

`tests/Feature/PublicProfileTest.php` asserts the default page is `index,follow` with Person JSON-LD, and that forcing the config to `false` yields `noindex,follow`, no Person JSON-LD, the name still visible, the email absent, and the sitemap without `/u/`. Full suite on 17 October 2026: 233 passed. Those tests do not change production.

## Approval required

Approve or reject this single environment assignment. It is not part of the demo-event work and it is not enabled by this document.

## Risk

Low for access and privacy. The page becomes harder for Google to index, which is the point. Internal links and event links remain crawlable because the directive is `follow`, not `nofollow`. If the owner later wants profiles in Google, set the flag back to `true`. There is no Search Console measurement yet of how many `/u/` URLs are indexed, so the size of the index change is unknown until `SEARCH_CONSOLE_MONITORING.md` has a verified property.
