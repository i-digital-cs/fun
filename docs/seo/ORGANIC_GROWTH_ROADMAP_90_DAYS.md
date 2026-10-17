# Organic growth roadmap, 90 days

Start: 17 October 2026. End: 15 January 2027.

Scope is real Egyptian events on `https://funnity.com`. This roadmap does not create pages, change event rows, or enable flags. Demo search exclusion and the profile flag are separate approvals in `DEMO_EVENT_INDEXING_STRATEGY.md` and `PROFILE_INDEXING_IMPACT.md`.

## Inventory that the plan has to respect

Fetched from the database on 17 October 2026. Search Console coverage is unknown (`SEARCH_CONSOLE_MONITORING.md`).

| Set | Count | Role |
| --- | --- | --- |
| Demo seed, IDs 1–500 | 500 | Keep in the app. Proposed later: out of the sitemap and `noindex,follow`. Not the growth target. |
| Egypt catalog, owner `egypt.catalog@funnity.com`, IDs 531–580 | 50 `published`, all already ended, all started inside the last 90 days | Real descriptions, venues, and images. Historical pages. Still in the sitemap and in text search. Not in the upcoming browse feed. |
| Event 530 | 1 `published`, 6th of October, 22 October 2026, unique description, uploaded image, `@gmail.com` organizer | The only real upcoming event. |
| Placeholders 501, 502, 602 | 3 | Cancel only under the separate procedure. 502 is still in the browse feed until then. |
| Event 503 | 1 `completed` | Keep the status. Replace the description “Description” only after the organizer writes it. |
| Active cities | 16 | 15 are Egyptian. `south-trenton-024` (South Trenton) has one event, ID 602. Do not build a landing page for it. |
| Active categories | 11 | `iste-quas-704` is used only by event 602. Do not build a landing page for it. |

Cities that have at least one non-demo public event: Cairo (33), New Cairo (6), Giza (6), 6th of October (4, including the placeholders and event 530), Suez (3), Sheikh Zayed (1), Alexandria (1).

Cities whose public events are only the demo seed: Obour, Mansoura, Tanta, Ismailia, Port Said, Zagazig, Damietta, Hurghada. A landing page for those cities would be empty of real events. Do not create it.

Upcoming real supply is one event. A city or category page that pretends otherwise would be keyword stuffing. Pages wait until they have something true to list.

## Days 1–30 — measure and stop indexing the seed

1. Verify Search Console and record the baseline in `SEARCH_CONSOLE_MONITORING.md`. No growth claim before that.
2. After approval, exclude IDs 1–500 from the sitemap and mark them `noindex,follow`, leaving them in Discover. That is the largest index-quality change available. It is not authorized yet.
3. Cancel 501, 502, and 602 only under the revalidation procedure. Leave 503 completed and ask the organizer for a real description.
4. Decide the profile flag. Demo profile pages are an extra path to seed event URLs.
5. Do not add city URLs this month.

Success for this month is an honest baseline plus, if approved, a sitemap that no longer advertises the 500 seed URLs. It is not a traffic target.

## Days 31–60 — pages only where real events exist

Build these only after the seed URLs are out of the sitemap, so the new pages do not sit next to hundreds of template events.

**Event pages first.** Event 530 and the next real events members publish need a unique description, a real place, and the Event JSON-LD already produced by `Seo::forEvent` (name, time, place, organizer). Do not generate a description from the title plus the city name. That is the demo seeder’s pattern.

**City pages, second, and only for the seven cities with non-demo events.** Each page needs:

- The city’s existing `name_en` and `name_ar`.
- A short intro written from the events that are actually in that city, in Arabic and English.
- A list of real upcoming events, then real past events. Demo IDs 1–500 stay out of the list even if they are still `published` in the app.
- A self-canonical URL such as `/events/city/{slug}`.
- `index,follow` only when the list is non-empty. If the real list is empty, do not publish the URL.
- No page for South Trenton.

6th of October can list event 530 as upcoming and the catalog events that are actually in that city as past. Cairo has the most past catalog events and no real upcoming event yet, so its page is a past-events page until someone publishes a real one. Say that in the copy.

**Category pages, same rule.** Football can be first if event 530 and the catalog football rows are real. Do not publish `iste-quas-704`. Filtered `/events?category_id=` stays `noindex,follow` so it does not compete with a future clean category URL.

Internal links, in this order: homepage and Discover cards to the event, event breadcrumbs back to Discover (already in the JSON-LD), then city and category pages linking to those events and to each other only when both pages exist. Do not add a footer block of every city.

## Days 61–90 — language, history, and the next real events

- Write Arabic and English for the same URL. The product locale is a session switch, not a separate `/ar/` path, and there is no `hreflang` setup. Do not invent a second URL per language in this quarter unless that routing work is approved on its own. Put both languages on the page only if the layout already shows the active locale’s strings. The catalog titles are already Arabic. Keep them.
- Review the 50 catalog descriptions for ones that are still specific (place, time, what to bring). Leave them indexable when they are. Do not noindex an ended event only because the date has passed.
- Ask organizers of new events for a description that states the neighborhood, the activity, and who it is for. The publish flow already warns when the description is empty. That warning is not a substitute for a unique paragraph.
- Track, in Search Console once it exists: impressions and clicks for `/`, `/events`, event 530, and any city page that was actually published. Compare to the day-30 baseline. Absence of data stays “not verified”.
- Revisit empty cities only after a real event exists there. Obour through Hurghada stay off the landing-page list until then.

## Structured data

Keep using the existing Event schema on event pages: title, description, start, end, place, image, organizer. Do not add ratings, offers, or attendance counts that are not on the row. Demo events, once `noindex`, should not be expected to earn rich results. City pages, when they exist, can use `ItemList` of the real events on that page and a `BreadcrumbList`. No fake `AggregateRating`.

## Risks

| Risk | Guard |
| --- | --- |
| City page with only seed events | Do not create it. Eight Egyptian cities are in that state today. |
| South Trenton and `iste-quas-704` look like real taxonomy | They each have one row, event 602. No landing page. Deactivating those taxonomy rows is a separate data approval. |
| Catalog pages are past-only | Say so. Do not label them upcoming. |
| One real upcoming event | Do not write “events this week in every city”. |
| New URLs before the sitemap is cleaned | Seed URLs would still be the majority of the sitemap (500 of 562). Clean that first. |
| Language URLs | Do not split EN/AR into indexable duplicates without `hreflang`. |
| Empty states and keyword lists | A page without a real event list is not published. |

## Approvals this roadmap still needs

1. Search Console verification.
2. Implement `search_indexable` for the 500 seed rows.
3. Cancel 501, 502, and 602, one at a time, after a fresh dependency check.
4. Organizer-written description for 503, status left `completed`.
5. Optional: `SEO_PUBLIC_PROFILES_INDEXABLE=false`.
6. A later build approval for city and category pages, starting with 6th of October and Cairo, and only with real events on the page.

Nothing in this file is a deploy.
