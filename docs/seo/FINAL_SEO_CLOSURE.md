# Final SEO closure

Date: 17 October 2026. Engineering work for this SEO pass is closed. No demo event was marked non-indexable, no placeholder was cancelled, and `SEO_PUBLIC_PROFILES_INDEXABLE` was not turned off.

**Verdict: PASS for the approved activation.** Search Console has not confirmed removal of any URL.

Activated 17 October 2026 on database `d14098_fun` at `127.0.0.1`, application URL `https://funnity.com`.

- Backup `storage/app/backups/d14098_fun-seo-activation-20261017.sql.gz` (220,270 bytes, sha256 `93f63c3ac1fc4dd232d3a42575df93739448c5743379267264851168a185262f`). `gzip -t` passed. The archive contains 555 `events` rows. A second database could not be created with this account, so the archive was not restored onto a scratch schema.
- The demo guard matched 500 rows and no payment rows. `seo:exclude-demo-events` then set those 500 to `search_indexable=false`. Their status was not changed.
- Placeholder validation passed. Admin moderation cancelled 501, 502, and 602. Memberships and messages were unchanged. Event 503 stayed `completed` with the same description and `updated_at`.
- `SEO_PUBLIC_PROFILES_INDEXABLE=false` is set in production `.env`. Config and `seo.sitemap.xml` caches were cleared.

Live evidence after that: homepage 200 `index,follow`; canonical redirects one hop; demo event 409 and event 1 return 200 `noindex,follow`; event 530 returns 200 `index,follow`; sitemap has 59 URLs, 0 demo IDs, and does not list 501, 502, or 602; 503 and 530 remain listed; a public profile returns 200 `noindex,follow` with no Person JSON-LD. Guests receive 403 for the cancelled events, which is the existing visibility rule. `php artisan test`: 240 passed, 1019 assertions.

## 1. Implemented changes

- `events.search_indexable` defaults to `true`. Discover, bookings, chat, tickets, and the API resource do not read it.
- The sitemap omits events with `search_indexable = false`. Their public URLs stay HTTP 200 with `noindex,follow` and keep Event JSON-LD. They are not redirected.
- `php artisan seo:exclude-demo-events` marks IDs 1–500 only when every row has a description starting `Join us for `, an `@example.com` creator, the ID set is exactly 1–500, and no payment, order, or charge table has rows for those IDs. Any miss aborts inside a transaction with no partial update. A second run is safe. The command clears `seo.sitemap.xml` only after a successful update. **It was run on 17 October 2026 after the backup above.**
- `php artisan seo:validate-placeholder-cancellations` is read-only. On 17 October 2026 it passed for 501, 502, and 602, and it left 503 `completed`. **Nothing was cancelled.**
- Homepage title is `Funnity — local events and neighborhood meetups`. The description is `Funnity helps neighbors discover local events, meetups, and fun neighborhood activities nearby.` Arabic strings match that meaning. There is no meta keywords tag.
- Genuine event pages use the event title, a meta description built from the stored description plus the stored city and date, the canonical event URL, Event JSON-LD with `startDate` and `addressLocality` when a city exists, and `index,follow` while `search_indexable` is true. No event row’s title, description, date, or status was edited.
- Production `.env` has `SEO_PUBLIC_PROFILES_INDEXABLE=false`. Public profiles emit `noindex,follow` and omit Person JSON-LD. The page, API, login, and event links are unchanged. The test suite forces the flag back to `true` so it does not follow production.

The additive migration is applied. IDs 1–500 are `search_indexable = 0`. The other 55 events, including 503 and 530, stay `search_indexable = 1`.

## 2. Files changed

New in this closure:

- `database/migrations/2026_10_17_171900_add_search_indexable_to_events_table.php`
- `app/Models/Event.php`
- `app/Support/Seo.php`
- `app/Http/Controllers/Web/SitemapController.php`
- `app/Services/Seo/EventFinancialLinks.php`
- `app/Services/Seo/ExcludeDemoEventsFromSearch.php`
- `app/Services/Seo/ValidatePlaceholderCancellations.php`
- `app/Console/Commands/ExcludeDemoEventsFromSearchCommand.php`
- `app/Console/Commands/ValidatePlaceholderCancellationsCommand.php`
- `lang/en/design.php`
- `lang/ar/design.php`
- `tests/Feature/DemoEventSearchExclusionTest.php`
- `tests/Feature/PlaceholderCancellationValidationTest.php`
- `tests/Feature/SeoFoundationTest.php`
- `docs/FLUTTER_WEB_PARITY.md` (§13 only: the flag is web HTML, not an API field)
- `docs/seo/FINAL_SEO_CLOSURE.md`

Already accepted technical SEO (canonical redirects, brand JSON-LD, sitemap `lastmod`, profile flag defaulting on) remains in the working tree. It was not reworked.

## 3. Test results

`php artisan test`: **240 passed, 1019 assertions**, 17 October 2026.

Covered here: demo exclusion and abort cases, sitemap omission, public 200 with `noindex,follow`, profile flag on and off, homepage metadata, event schema, canonical redirects, auth registration, and event join/booking.

Live checks the same day:

| Check | Result |
| --- | --- |
| `https://funnity.com/` | 200, title and description as above, `index,follow`, canonical `https://funnity.com`, no keywords tag |
| `http://funnity.com/events?q=1` | 301 to `https://funnity.com/events?q=1` |
| `https://www.funnity.com/events?q=1` | 301 to the apex, query kept |
| `https://funnity.com/events/` | 301 to `https://funnity.com/events` |
| POST `http://www.funnity.com/login` | 419, no redirect |
| Event 530 | 200, `index,follow`, canonical self, Event JSON-LD, `startDate`, `addressLocality` |
| Event 1 (demo) | 200, still `index,follow`, still in the sitemap |
| Sitemap | 562 URLs: 500 demo, 55 other events, 0 profiles |

## 4. Remaining owner actions

1. The organizer replaces event 503’s description. Leave the status `completed`.
2. Verify the Domain property in Google Search Console and submit `https://funnity.com/sitemap.xml`. Until that report exists, do not claim pages were indexed or dropped.

## 5. Deployment commands

This application directory is already the live tree. The schema migration has been applied. No further deploy is required for the code that is on disk.

When the owner approves the demo exclusion:

```bash
php artisan seo:exclude-demo-events
php artisan cache:forget seo.sitemap.xml
```

The command forgets the sitemap cache itself. The second call is harmless.

Do not run that command as part of a routine deploy. Do not set the profile flag in the same step unless that separate approval is explicit.

## 6. Rollback

Demo exclusion, after it has been run:

```sql
UPDATE events SET search_indexable = 1 WHERE id BETWEEN 1 AND 500;
```

Then `php artisan cache:forget seo.sitemap.xml`. That restores sitemap eligibility. It does not change event status.

Profile flag: set `SEO_PUBLIC_PROFILES_INDEXABLE=true` or remove the line, then reload PHP.

Placeholder cancels cannot be reversed. `cancelled` cannot transition back to `published`. Do not cancel if the row should stay public.

Schema rollback, only together with reverting the sitemap and `Seo` code that reads the column:

```bash
php artisan migrate:rollback --path=database/migrations/2026_10_17_171900_add_search_indexable_to_events_table.php --force
```

Rolling back the column while the new PHP is still deployed makes `/sitemap.xml` and event pages fail.

Redirect rollback remains the previous `.htaccess` files. Those redirects were already live before this closure and were rechecked above.

## 7. Verdict

**PASS** for the approved activation. Demo events stay in the app and are out of the sitemap. Events 501, 502, and 602 are cancelled with their related rows kept. Event 503 is still completed. Profiles are `noindex,follow`. Google’s index has not been confirmed by Search Console.
