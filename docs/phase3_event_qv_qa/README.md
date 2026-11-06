# Phase 3 — Event Quick View + SEO + Security Headers

Evidence for owner review. **Phase 3E published** — see `PHASE3E_PUBLISH_2026-11-05.md`.

Also: `PHASE3E_MAP_POPUP_POSITIONING.md` (mobile dock overlap + exact popup clip).

## Screenshots

Captured in Cursor browser against live Discover (`/events?view=list`) with CSS injected for preview (built assets not published):

- Desktop right drawer: conversation artifact `phase3-qv-desktop.png`
- Mobile sheet preview: conversation artifact `phase3-qv-mobile.png`

Re-capture after `npm run build` (owner-approved) for production-faithful chrome.

## Automated tests

```bash
/usr/local/bin/php ./vendor/bin/phpunit --filter 'EventQuickViewTest|SecurityHeadersTest|EventSlugRoutingTest'
# OK — 17 tests, 64 assertions
```

## Live checks (already on PHP runtime)

- `GET /events/{id}` → **301** to `/events/{id}/{slug}`
- Canonical + `og:url` match slug URL
- Security headers present (CSP Report-Only; HSTS off)
