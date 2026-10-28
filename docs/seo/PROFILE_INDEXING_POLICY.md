# Public profile indexing policy

**Date:** 17 October 2026  
**Behavior today:** unchanged. `/u/{publicId}` is `index,follow` via `Seo::forPublicProfile`. Profiles are not in the sitemap.  
**Recommendation:** **`noindex,follow`**. Do not apply it until the owner agrees.

## What was counted

Read-only aggregate on accounts that are not deactivated and have a public id. Names and emails were not exported.

| Measure | Count |
|---|---|
| Active public profiles | 672 |
| With a profile photo | 6 |
| With a city | 103 |
| With at least one published event they created | 69 |
| Display names shared by more than one account | 8 name groups, 16 people |

`PublicProfileService::toPublicArray` exposes display name, public id, photo, city, hosted and joined counts, and a rating summary. The page can list up to six hosted event titles. The feature test asserts the email is absent. The user model has no biography field. Identity documents are not on this route. Deactivated accounts 404.

## Privacy

The URL is a product feature, so a member can open another member’s card. That is not the same as asking Google to keep the name in search results. A name plus city plus photo is personal data. Only 6 of 672 profiles have a photo, so most indexed URLs would be a name and an id.

## Uniqueness and search value

The title pattern is `{Name} · {public id} · Funnity`. It is unique per id and does not describe a topic someone searches for. 603 profiles have never published an event, so the page is a name, an id, and zero hosted events. Sixteen people share a display name with someone else. There is no free-text bio that would make the page a distinct document.

The 69 hosts are the only profiles with a clear public artifact (their events). Those events already have their own URLs. The profile URL does not add a search document beyond the person’s name.

## Recommendation

Use `noindex,follow` on `/u/{publicId}`:

- Crawlers can still follow links from the profile to public events.
- Member names drop out of the indexable set.
- Profiles stay available to anyone with the link.
- Do not add `/u/` URLs to the sitemap.

Do not use `nofollow`. That would waste links from hosts to their events.

`index,follow` would be reasonable only for profiles the member explicitly publishes as a public page, with a bio or a stable list of events. That switch does not exist today, so it should not be the default.

This recommendation is not implemented.
