# Sitemap content audit

**Date:** 17 October 2026  
**Method:** Read-only query of the application database for every published or completed event, plus the seven static routes hardcoded in `SitemapController`. No row was updated. The live sitemap was not regenerated, because this change is not deployed.

## Coverage

| Set | Count | In sitemap after this code deploys |
|---|---|---|
| Static routes (`/`, `/events`, `/safety`, `/privacy-policy`, `/terms`, `/support`, `/account-deletion`) | 7 | Yes. `lastmod` omitted. |
| Published events | 154 | Yes, when a slug exists |
| Completed events | 401 | Yes, when a slug exists |
| Eligible events total | 555 | Matches the live sitemap event count from Phase 0 |
| Missing slug | 0 | — |
| Public profiles `/u/{publicId}` | 672 active accounts with a public id | Still omitted. Indexability unchanged. |

Phase 0 fetched the seven static pages. Each returned 200 with a unique title, a description, and visible copy (privacy policy about 11k characters of text; safety and support are shorter but real). None is an empty template. `/account-deletion` is in the sitemap and not in the footer.

## Quality findings

Nothing below was deleted, unpublished, or marked `noindex`.

### Placeholder, test, and thin copy

| ID | URL | Evidence | Recommended action |
|---|---|---|---|
| 602 | https://funnity.com/events/602/modi-praesentium-itaque-dolor | Published. Title `Modi praesentium itaque dolor.` Description `Laudantium dolorum iste at. Adipisci quod laborum pariatur impedit.` Starts 2026-09-30 (already ended). | Owner: unpublish or noindex. This is filler. |
| 501 | https://funnity.com/events/501/title | Published. Title `Title`. Description `Description` (11 characters). Starts 2026-09-23 (already ended). | Owner: unpublish or noindex. Placeholder fields. |
| 502 | https://funnity.com/events/502/yyyy | Published. Title `yyyy`. Description `yttttt` (6 characters). Starts 2026-10-21 (still in the future as of this audit). | Owner: unpublish or noindex. Test strings. |
| 503 | https://funnity.com/events/503/zamalek-football-match | Completed. Title is a real match name. Description is the single word `Description`. Starts 2026-09-25. | Owner: add a real description or leave it. Do not delete on description length alone. |

These four are the only eligible events whose title or description matched Latin filler or a description shorter than 40 characters.

### Repeated titles (not duplicate URLs)

60 titles are used by more than one event (500 of the 555 rows). A sample of `New game learn & play` (ids 11, 18, 67, 76, 93) shows different dates and cities, with descriptions of the form `Join us for "…" in {city}`. They are separate URLs with template copy, not the same page twice.

**Recommended action:** leave them indexable. Do not bulk-delete. If they were generated as demo data rather than member events, the owner can unpublish that set later. This audit did not classify authorship.

| Count | Title | Event IDs |
|---|---|---|
| 15 | coffee & conversation | 58, 72, 110, 234, 235, 263, 280, 296, 376, 380, 387, 399, 461, 470, 481 |
| 15 | new game learn & play | 11, 18, 67, 76, 93, 109, 128, 159, 180, 189, 268, 281, 360, 477, 500 |
| 14 | evening hoops session | 27, 43, 154, 183, 232, 286, 301, 363, 365, 412, 465, 467, 472, 474 |
| 14 | morning football drills | 33, 80, 90, 131, 137, 185, 243, 255, 289, 291, 350, 375, 403, 498 |
| 13 | card games meetup | 45, 92, 102, 103, 148, 151, 230, 273, 311, 348, 415, 464, 499 |
| 13 | evening social hangout | 4, 61, 85, 91, 184, 203, 256, 275, 278, 305, 336, 349, 450 |
| 13 | photography walk | 66, 70, 112, 157, 161, 199, 216, 299, 310, 354, 384, 437, 452 |
| 13 | playstation party at the lounge | 1, 40, 139, 176, 239, 285, 298, 323, 371, 408, 431, 436, 440 |
| 12 | courtside rally night | 31, 101, 147, 162, 277, 346, 352, 358, 368, 416, 482, 494 |
| 11 | arabic film night | 16, 42, 46, 77, 130, 257, 309, 341, 486, 491, 492 |
| 11 | friendly football at the pitch | 6, 25, 113, 167, 186, 316, 325, 382, 433, 463, 495 |
| 11 | post-movie coffee chat | 65, 81, 127, 169, 217, 241, 242, 297, 324, 422, 427 |
| 10 | 5-a-side evening kickabout | 138, 149, 164, 188, 236, 377, 420, 439, 479, 484 |
| 10 | 5k neighborhood run | 39, 47, 56, 63, 119, 210, 215, 251, 373, 483 |
| 10 | catan + strategy night | 193, 229, 326, 338, 347, 357, 396, 485, 488, 497 |
| 10 | esports watch + play | 78, 168, 201, 214, 222, 237, 248, 282, 284, 454 |
| 10 | local fifa cup | 7, 14, 71, 97, 118, 120, 276, 342, 414, 419 |
| 10 | weekend basketball run | 15, 41, 295, 308, 327, 329, 330, 369, 404, 409 |
| 10 | weekend hiking day trip | 5, 62, 94, 245, 261, 266, 331, 345, 385, 473 |
| 9 | book club discussion | 13, 59, 105, 171, 195, 218, 362, 379, 448 |
| 9 | lan party meetup | 22, 44, 124, 181, 302, 317, 318, 411, 432 |
| 9 | neighborhood potluck | 177, 208, 244, 249, 378, 457, 458, 478, 496 |
| 9 | sunrise group run | 2, 10, 36, 198, 226, 231, 279, 306, 394 |
| 9 | sunset outdoor hangout | 75, 88, 116, 228, 246, 320, 359, 366, 480 |
| 8 | beginner tennis session | 107, 114, 166, 178, 202, 265, 435, 451 |
| 8 | board game café night | 3, 24, 52, 57, 64, 68, 312, 441 |
| 8 | console night with friends | 38, 48, 74, 253, 267, 328, 423, 468 |
| 8 | court practice + scrimmage | 29, 173, 190, 250, 271, 290, 389, 475 |
| 8 | family board game evening | 32, 146, 155, 182, 219, 220, 233, 393 |
| 8 | friendly tennis match | 23, 35, 69, 144, 194, 314, 361, 421 |
| 8 | indoor football session | 28, 187, 227, 343, 351, 390, 487, 490 |
| 8 | open-air cinema hangout | 54, 142, 207, 304, 319, 410, 429, 460 |
| 8 | split-screen challenge | 50, 87, 141, 153, 170, 294, 303, 428 |
| 8 | strategy game evening | 108, 206, 240, 258, 430, 438, 456, 466 |
| 8 | weekend football meetup | 34, 129, 143, 191, 192, 270, 344, 395 |
| 8 | weekend long run club | 86, 204, 224, 292, 293, 313, 386, 391 |
| 7 | 3v3 basketball challenge | 60, 209, 213, 315, 402, 407, 417 |
| 7 | casual recovery run | 21, 26, 126, 300, 332, 400, 489 |
| 7 | co-op gaming evening | 30, 111, 238, 283, 322, 388, 397 |
| 7 | morning tennis drills | 260, 355, 367, 372, 398, 434, 453 |
| 7 | park loop running meetup | 19, 115, 121, 163, 254, 405, 459 |
| 7 | street basketball meetup | 17, 98, 122, 165, 200, 353, 469 |
| 6 | bike ride around town | 99, 211, 287, 374, 442, 493 |
| 6 | blockbuster movie outing | 51, 100, 133, 179, 443, 445 |
| 6 | casual online co-op | 20, 212, 370, 424, 447, 449 |
| 6 | competitive gaming session | 172, 252, 337, 339, 444, 471 |
| 6 | evening jog around the lake | 49, 156, 333, 383, 413, 425 |
| 6 | friends of friends mixer | 9, 84, 135, 196, 288, 406 |
| 6 | park picnic meetup | 83, 125, 140, 145, 335, 455 |
| 6 | weekend tennis meetup | 158, 269, 340, 401, 462, 476 |
| 5 | fifa tournament night | 95, 123, 307, 321, 381 |
| 5 | neighborhood football match | 82, 152, 221, 364, 418 |
| 5 | newcomers welcome meetup | 132, 136, 272, 274, 446 |
| 5 | pc gaming night | 8, 53, 197, 223, 247 |
| 5 | pickup basketball game | 89, 104, 106, 225, 262 |
| 5 | tennis doubles practice | 12, 96, 175, 264, 392 |
| 4 | cinema night downtown | 73, 117, 174, 205 |
| 4 | puzzle & board games night | 150, 160, 259, 356 |
| 3 | indie film screening meetup | 37, 79, 426 |
| 3 | outdoor yoga in the park | 55, 134, 334 |

### Expired events

471 of 555 eligible events have `ends_at` or, when that is empty, `starts_at` before the audit timestamp. 84 eligible events still start in the future. Completed events stay in the sitemap because the product treats them as public history (`index,follow` in `Seo::forEvent`).

**Recommended action:** do not mass-remove ended events. If Search Console later shows “Crawled — currently not indexed” on old events, consider a later policy that `noindex`es events ended more than a chosen number of days ago. That policy is not in this change.

### Broken URLs

No eligible event lacked a slug. Sitemap `loc` values are built with `event_show_url()`, which is the canonical `/events/{id}/{slug}` path. This audit did not HTTP-check all 555 URLs. Phase 0 checked a spread of 11 plus the placeholder URL; each returned 200 with a self canonical.

## Public profile policy (not applied)

`/u/{publicId}` is `index,follow` via `Seo::forPublicProfile`. It is not in the sitemap. Deactivated accounts 404. The public payload (`PublicProfileService::toPublicArray`) includes display name, public id, profile photo, city name, hosted and joined counts, and rating summary. The web page also lists up to six hosted event titles. The feature test asserts the email is absent. There is no biography field on the user model, so most profiles are a name, an id, and stats.

| Question | Finding |
|---|---|
| Meaningful public content? | Thin, unless the person hosts events. No free-text bio. |
| Personal information? | Display name, photo, city, and public id are visible. Email, identity documents, and private settings are not on this page. A name in Google is still personal data. |
| Unique titles? | `{Name} · {public_id} · Funnity`. Unique per person, not descriptive. |
| Duplicate or thin pages? | 672 near-identical layouts. Easy for Google to treat as thin. |
| Privacy expectation? | A public profile URL is shareable inside the product. Indexing every member by name was not an explicit product decision. |

**Recommendation:** `noindex,follow` on `/u/{publicId}` until the owner wants members to be findable by name. Keep the pages crawlable so event links still pass equity. Do not add profiles to the sitemap. This was **not** implemented.
