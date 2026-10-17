# Search Console monitoring

Date: 17 October 2026.

## Evidence available today

Search Console was not opened. This environment has no Search Console API credential, no DNS verification token, and no `google-site-verification` file in `public/`.

Because of that, every comparison below is **unavailable**, not zero:

| Report | What this project can say |
| --- | --- |
| Indexed pages | Unknown. Do not treat a `site:` search as this number. |
| Excluded pages | Unknown. No coverage export. |
| Sitemap-discovered URLs | Unknown to Google. The origin sitemap itself was fetched and contains 562 URLs (7 static, 500 demo events, 55 other events, 0 profiles). That is the file Google would be asked to read. It is not a discovered-URL count from Search Console. |
| Branded search performance | Unknown. No query report. A public web search on 17 October 2026 associated the name Funnity with `funnityweb.com`, not with this site. That observation is not impressions or clicks. |
| Event page impressions | Unknown. |
| Event page clicks | Unknown. |

No specific event URL, including IDs 1–500, 501, 502, 503, 530, or 602, is claimed to be indexed or removed.

Verification and submission steps stay in `GOOGLE_SEARCH_CONSOLE_HANDOFF.md`. Use a Domain property for `funnity.com`. Submit `https://funnity.com/sitemap.xml` only after the property shows verified.

## What to record on the first successful session

Write the numbers into this file. Do not estimate them in advance.

1. Property type and the date verification succeeded.
2. Sitemap row: submitted URL, last read, status, discovered count.
3. Pages report: indexed count, and the excluded reasons with counts (especially “Crawled — currently not indexed”, “Discovered — currently not indexed”, “Excluded by ‘noindex’ tag”, “Blocked due to access forbidden (403)”, “Duplicate without user-selected canonical”, “Page with redirect”).
4. Performance, last 28 days, search type Web: total clicks and impressions.
5. The same report filtered to the query `funnity` and to URLs containing `/events/`.
6. URL inspection for `/`, for one demo event URL, for event 530, and for one `/u/` profile. Record “URL is on Google” or not, the user-declared canonical, and the indexing verdict. Inspection of one URL is not a sitewide count.

## How later changes should show up

Check these only after the matching owner approval is actually deployed. Until then, expect the current sitemap shape.

| Change | Search Console signal | When it can appear |
| --- | --- | --- |
| Demo events `noindex,follow` and removed from the sitemap | Sitemap discovered count drops by about 500. Excluded by `noindex` rises as Google recrawls. Indexed count of those URLs is the number to compare. It is unknown today. | Days to weeks after the flag is turned on and the sitemap is resubmitted. |
| Cancel 501, 502, 602 | Those URLs leave the sitemap. Guests, including Googlebot, receive 403, so the exclusion reason may be access forbidden rather than `noindex`. | After cache forget and recrawl. |
| Event 503 description edit | URL stays indexable. Inspection should show the new description. Status stays completed. | After recrawl. |
| `SEO_PUBLIC_PROFILES_INDEXABLE=false` | Profile URLs, if any were indexed, move toward excluded by `noindex`. The sitemap count does not change, because profiles are already absent. | After recrawl. |
| Canonical host redirects | “Page with redirect” for `http://` and `www` URLs. The indexed version should be `https://funnity.com/...`. | The redirects were already answering on 17 October 2026. |

## Weekly check after verification

- Sitemap status is Success and the discovered count matches a fresh fetch of `/sitemap.xml`.
- Indexed count is recorded, not replaced with a guess.
- No new “redirect error” or unexpected canonical on `/`.
- `/events?q=` and other filtered discover URLs stay out of the indexed set (`noindex,follow` is already their directive).
- Branded queries are listed with clicks and impressions beside the top pages. If `funnityweb.com` still outranks this site, that is a brand-collision note, not a ranking claim invented here.

## Rollback trigger from these reports

If a release that was supposed to change only demo events also drops the homepage, event 530, or the Egypt catalog URLs out of “URL is on Google” with a `noindex` or 403 verdict, revert that release. `DEMO_EVENT_INDEXING_STRATEGY.md` describes the data rollback for `search_indexable` and the fact that cancellation cannot be undone by a status transition.

## Approval required

The owner verifies the Domain property and submits the sitemap. This document does not do that.
