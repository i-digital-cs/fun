# Event content quality audit

**Generated:** 17 October 2026  
**Rows read:** 555 published or completed events. No row was updated.  
**Machine-readable file:** [`event-content-quality-audit.json`](event-content-quality-audit.json)  
Each object has `id`, `url`, `classification`, `title`, and `evidence`.

Draft, cancelled, and other statuses are not in the sitemap and were not classified.

## How an event was classed

An ended event is not low quality by default. Classes are exclusive, in this order:

1. **placeholder_or_test** — Latin filler, or the title or description is only a junk token (`Title`, `yyyy`, `Description`, `ytttt`).
2. **duplicate_or_near_duplicate** — the exact description is shared with another eligible event, or the same title is reused with the `Join us for …` template.
3. **historical_meaningful** — the event has ended, the description is at least 40 characters, and it has a start time and a place (city, meaningful venue, or map pin). Not a template duplicate.
4. **historical_little_value** — the event has ended and fails that bar.
5. **high_quality_unique** — it has not ended, the title is unique, and the description, date, and place meet the same bar without the shared template.
6. **valid_insufficient** — it has not ended and is not placeholder or duplicate, but the text or place is still too thin.

Structured data and links were taken from the application, not from 555 live HTTP fetches. `Seo::forEvent` emits `Event` and `BreadcrumbList` JSON-LD. Published and completed pages are `index,follow`. The breadcrumb links the homepage and `/events`. `SitemapController` includes these statuses. Covers are an uploaded image or a stock activity image. Organizer evidence is only whether a public profile id exists. Emails were not exported.

## Counts

| Classification | Events | Of which already ended |
|---|---|---|
| duplicate_or_near_duplicate | 500 | 418 |
| historical_meaningful | 50 | 50 |
| historical_little_value | 0 | 0 |
| high_quality_unique | 1 | 0 |
| valid_insufficient | 0 | 0 |
| placeholder_or_test | 4 | 3 |

The 500 near-duplicates are separate URLs (different ids, usually different cities and dates) whose titles repeat and whose descriptions use one template, for example `Join us for "New game learn & play" in {city}`. They are not the same canonical URL. 82 of them have not ended.

The 50 historical events are specific meetups (Arabic titles such as museum walks, camping, horseback riding) with their own descriptions, venues, and in many cases map pins. They stay candidates for the sitemap.

Event **530**, `حجز كورة خماسي`, is the only current unique event that clears the bar: published, starts 2026-10-22, 97-character description, meaningful venue, uploaded image, public organizer profile. URL: `https://funnity.com/events/530/hgz-kor-khmasy`.

## Events 501, 502, 503, and 602

| ID | URL | Class | What the row actually contains |
|---|---|---|---|
| 501 | https://funnity.com/events/501/title | placeholder_or_test | Published. Title `Title`. Description `Description` (11 characters), shared with 503. Ended 2026-09-23. Venue and pin are present and an image was uploaded, so the shell looks like an event. The words are placeholders. |
| 502 | https://funnity.com/events/502/yyyy | placeholder_or_test | Published. Title `yyyy`. Description `yttttt` (6 characters). Starts 2026-10-21, so it is still current. Stock cover. This is test text on a future date. |
| 503 | https://funnity.com/events/503/zamalek-football-match | placeholder_or_test | Completed. The title `Zamalek football match` is a real phrase, not Latin. The description is the single word `Description`, the same token as 501. Ended 2026-09-25. Uploaded image, venue, and pin exist. Classed from the description token, not from the title. |
| 602 | https://funnity.com/events/602/modi-praesentium-itaque-dolor | placeholder_or_test | Published. Title `Modi praesentium itaque dolor.` Description `Laudantium dolorum iste at. Adipisci quod laborum pariatur impedit.` Ended 2026-09-30. Stock cover. Latin filler. |

All four are `index,follow` and would be listed in the sitemap. **No change was made.** Recommended owner action: unpublish these four, or accept them explicitly. Do not noindex the other 551 events to hide these four.

## Sitemap eligibility

Keep historical events that have their own description and a place. That is the 50 `historical_meaningful` rows.

Do not drop the 418 ended template events only because the date passed. Do not noindex them as a group. They are near-duplicate template pages. If the owner confirms they are demo data, unpublish that set in a later, reviewed change. Until then they stay eligible.

Do not add a blanket “ended events out of the sitemap” rule.

The four placeholder rows should leave the indexable set only after the owner says so.
