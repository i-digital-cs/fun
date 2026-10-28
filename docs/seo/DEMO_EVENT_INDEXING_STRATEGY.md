# Demo event indexing strategy

Date: 17 October 2026. Read-only. No event was cancelled, hidden, or edited. No flag was enabled.

The 500 rows are the `DemoCommunitySeeder` block: IDs 1–500, every description starts with `Join us for `, every creator is `@example.com`, 400 `completed`, 100 `published`. Nothing else sits in that ID range.

## Search Console

No Search Console property is connected to this environment. There is no verification token in the repo or in non-secret environment keys, and no HTML verification file under `public/`.

**Which of these 500 URLs Google has indexed is unknown.** This report does not claim that any demo URL is indexed or excluded.

## Sitemap

The live file `https://funnity.com/sitemap.xml` was fetched on 17 October 2026.

| Cohort | URLs in the live sitemap |
| --- | --- |
| Static pages | 7 |
| Demo events, IDs 1–500 | 500 |
| Every other event URL | 55 |
| Public profiles | 0 |

`SitemapController` includes every `published` or `completed` event, up to 5,000, with no demo exclusion. All 500 match that rule, so all 500 are eligible again on the next cache rebuild (`seo.sitemap.xml`, 3600 seconds).

## Public internal links

Application visibility and sitemap eligibility are different.

| Surface | Rule in code | Demo events on 17 October 2026 |
| --- | --- | --- |
| Sitemap | `published` or `completed` | 500 |
| Discover and the homepage feed | `published` and `starts_at` within the last day or later | 82 |
| Text search and suggest | `published` or `completed`, and start within 90 days or still ongoing | 236 |
| Homepage “this week” count | `published` and start this week | 5 |
| Homepage highlight | `published`, starts today, still in the future | 0 |
| Public profile pages | Up to 6 `published` events hosted by that user | Demo creators are `@example.com` users with public IDs. Their profile pages link to their published demo events. `robots.txt` allows `/u/`. |
| City or category landing pages | None exist | Category chips link to `/events?category_id=`, which is already `noindex,follow`. |

Direct URLs stay `200` for guests while the status is `published` or `completed` (`EventPolicy::view`). Cancelled and draft events are not public: a guest receives **403**, not a 200 `noindex` page and not a redirect home.

## Activity and dependencies

Counted on IDs 1–500:

| Relation | Rows | Outside `@example.com` |
| --- | --- | --- |
| Memberships | 1,718, including extra members on 418 events | 0 |
| Messages | 2 | 0 |
| Chat reads | 4 | — |
| Join requests | 4 (3 cancelled, 1 pending) | 0 |
| Favorites | 1 | — |
| Ratings | 0 | — |
| Reports | 0 | — |

There is no payments table. `cost_per_person` on many of these rows is the seeder’s display amount.

The memberships, ticket codes, and the small amount of chat exist so the demo accounts can open the product. They are not a reason to delete the rows. They are also not a reason to keep the URLs in Google’s sitemap.

## What can be hidden from search

All 500 can be removed from the sitemap and marked `noindex,follow` without cancelling them. The identification rule is all three of the following, not a shared title:

1. ID is between 1 and 500.
2. Description starts with `Join us for `.
3. Creator email ends with `@example.com`.

On 17 October 2026 that rule matches 500 rows and no others. A future change must abort if the count is not 500 or if any match has a non-example creator.

Do not hide events 501, 502, 503, 530, 602, or the Egypt catalog (IDs 531–580, owner `egypt.catalog@funnity.com`) with this rule. They are outside the block.

## What demonstrations still need

Keep the rows `published` or `completed`.

- The 82 events in the current browse window are what fill Discover and the homepage for a guest. Removing them from the app would leave one real upcoming event in 6th of October (event 530) plus placeholder 502.
- The 400 completed demo events are already absent from the browse feed. They still occupy sitemap and search.
- `admin@example.com` and the other `@example.com` accounts are the login fixture (`DemoCommunitySeeder` documents the shared password). Search exclusion does not remove those accounts.

## Proposed mechanism (do not build yet)

Add a boolean on `events`, default `true`, name `search_indexable`. A reviewed command sets it to `false` only for rows that match the three-part rule above, prints the IDs it will change, and stops unless the owner confirms the count is 500. It does not change `status`, members, messages, or ticket codes.

Then, and only then:

- `SitemapController` skips `search_indexable = false`.
- `Seo::forEvent` emits `noindex,follow` for those rows when they are `published` or `completed`.
- The show URL stays the same and stays **200** for guests.
- Discover, the homepage, the API, chat, tickets, and favorites keep using `status`. They do not read the new flag.
- Event JSON-LD can stay. Google does not build rich results from a `noindex` URL. The page content is unchanged.
- Do not answer these URLs with 404.
- Do not redirect them to `/` or to another event.
- Clear `seo.sitemap.xml` when the flag is written.

`noindex,follow` is deliberate. Profile and discover links can still be crawled. The event URL itself should not be indexed.

Rollback is setting `search_indexable` back to `true` for those IDs and clearing the sitemap cache. No status repair is required, because status never changed.

## Why this is not cancellation

Cancellation is the wrong tool for this block.

- Guest requests become 403, so the demo URLs disappear from the product, not only from Google.
- `ModerateEventService::cancel` does not clear the sitemap cache. The URL can remain in `sitemap.xml` for up to an hour.
- Cancelled pages that a manager can still open use `noindex,nofollow`, which is stricter than needed and is not what Googlebot sees anyway, because Googlebot is a guest and receives 403.
- The 1,718 demo memberships would sit on cancelled events.

Use cancellation only for the separate placeholder procedure in this same file.

## Placeholder procedure (not executed)

Re-run the dependency check in the same session as the action. Abort if any row is not `published`, if any member email is outside `@example.com` / `@example.org`, or if event 503 is included.

| ID | Status on 17 October 2026 | Still true immediately before acting? |
| --- | --- | --- |
| 501 | `published`. Owner `@example.com`. One membership. No messages, joins, favorites, ratings, reports, or notifications. Ended 23 September 2026. In the sitemap. Not in the browse feed. | Required |
| 502 | `published`. Owner `@example.com`. One membership, one `@example.com` message, one chat read. Starts 21 October 2026, so it is in the browse feed and the sitemap. | Required |
| 602 | `published`. Owner `@example.org`. One membership. Display cost only. Ended 30 September 2026. City slug `south-trenton-024`. Category on that row is the only use of `iste-quas-704`. In the sitemap. Not in the browse feed. | Required |

Action, after a separate approval:

1. Admin moderation cancel for that one ID. This is `POST /admin/events/{id}/cancel` through `ModerateEventService::cancel`. It does not delete members or messages.
2. `php artisan cache:forget seo.sitemap.xml` immediately. The admin cancel path does not forget that key. `TransitionEventStatusService::cancel` does, but the admin screen does not call it.
3. Confirm the guest URL returns 403, the sitemap no longer lists the URL, and event 503 is still `completed`.

Rollback of a cancel is not a supported status transition. `cancelled` cannot move back to `published`. Do not cancel if there is any chance the row should stay public.

## Event 503

Leave the status `completed`. Do not cancel it. The current rules reject that transition. It has a `@gmail.com` organizer, 22 messages, 2 join requests, and 12 notifications.

The description field is the single word `Description`. That is the content defect.

Correction, after the organizer or an admin writes it:

- Change `description` only, through admin event edit (`ModerateEventService::update`).
- Keep `status` as `completed`. The update method will save a status if the form sends one, so the saved row must be checked afterwards.
- Do not edit messages, memberships, dates, or the title.
- The replacement text has to come from someone who knows the event. Use the title, venue, city, and start time already stored on the row. Do not invent attendance, a score, or a place that is not already on the event.
- Clear `seo.sitemap.xml` after the edit so `lastmod` can refresh.

## Prioritized plan

| Order | Action | Now |
| --- | --- | --- |
| 1 | Owner verifies Search Console. Until then, do not claim any URL is indexed. See `SEARCH_CONSOLE_MONITORING.md`. | Not started |
| 2 | Approve the `search_indexable` design for IDs 1–500. Implementation is a later change. | Waiting |
| 3 | After that approval, run the guarded command, then clear the sitemap cache. | Not authorized |
| 4 | Separately approve cancel for 501, then 502, then 602, each revalidated first. | Not authorized |
| 5 | Organizer writes a real description for 503. Status stays `completed`. | Not authorized |
| 6 | Decide the profile flag in `PROFILE_INDEXING_IMPACT.md`. | Not enabled |
| 7 | Follow `ORGANIC_GROWTH_ROADMAP_90_DAYS.md` for real events. | Not started |

## Risks

| Risk | What to watch |
| --- | --- |
| The three-part rule matches a real event later | Command aborts unless the match count is exactly 500 and every creator is `@example.com`. |
| Sitemap cache | Forget `seo.sitemap.xml` or the old 500 URLs remain for up to an hour. |
| Demo discover looks empty if someone cancels IDs 1–500 instead of using the flag | Do not cancel this block. |
| Placeholder cancel is irreversible | Revalidate, then cancel one ID at a time. |
| 403 after cancel is not a clean `noindex` | Expected for 501, 502, and 602. It is not the plan for IDs 1–500. |
| Google index state | Unknown until Search Console is verified. |
