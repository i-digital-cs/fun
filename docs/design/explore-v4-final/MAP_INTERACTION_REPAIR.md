# Explore V4 — Map interaction & selection repair

**Date:** 2026-10-29  
**Git:** dirty tree preserved — no commit / push  
**Vite publish:** verified in isolation; **not left as permanent LIVE publish** (assets restored after check)

## Confirmed root causes

1. **Card → map** only looked up exact markers in `markerById`. City-only events had no geo attributes and no aggregate index → silent no-op.
2. **City aggregates were not clustered** at nationwide zoom → overlapping Cairo/Delta count bubbles.
3. **Nationwide chip** always appended radius (`All Egypt · Within 25 km`).
4. **Heading** always used `Nearby Events` regardless of mode.
5. **Aggregate click** only showed a static popup — no real city event list.

## Fixes

| Area | Change |
|---|---|
| Selection | Card `data-geo` / lat / city_* attrs; event delegation; exact → flyTo+popup; city → aggregate + city panel |
| Clustering | `DiscoverMapService::clusterCityAggregates` at zoom &lt; 10 (`city_cluster`, summed counts, no double-count) |
| City drill-down | `GET /api/discovery/city-events?city_id=` + results-panel city list + Show all |
| Labels | Nationwide chip = `All Egypt`; radius control hidden in nationwide; headings contextual |
| Flutter | Nationwide chip without radius; aggregate tap → city sheet; `focusListEvent` |

## Interactive verification (temporary verify build)

Evidence: `docs/design/explore-v4-final/screenshots/map-interaction-20261029/interaction-evidence.json`

| Check | Result |
|---|---|
| Chip `All Egypt` | PASS |
| Heading `Events in Egypt (58)` | PASS |
| Zoom≈5 markers | 5 city aggregates + 3 city clusters + 1 exact (sum 57) |
| Exact card → popup | PASS (`بولينج`) |
| City card → city panel | PASS (`Events in 6th of October (5)`) |
| City-events API | PASS HTTP 200 |

Isolated assets: `/tmp/funnity-map-interaction-verify/build/`  
(`discover-map-lgValr-e.js`, `app-CO8cTFGB.js`)

## Tests

- `DiscoverMapApiTest` — **12 passed** (incl. city cluster + city-events)
- Flutter `analyze` discover — info only

## Remaining limitations

1. Owner must approve Vite publish for permanent LIVE JS (card/panel interaction).
2. Blade/API (labels, clustering, city-events) already apply on PHP side without Vite.
3. Paginated list: cards not on the current page still select via temp pin / city API when geo attrs exist after navigation; unloaded pages are not pretended present.
4. Flutter device FPS still unverified.
5. IDE browser PNGs remain on client temp path; JSON evidence is in-repo.

## Deployment readiness

**Ready pending owner approval** to publish `/tmp/funnity-map-interaction-verify/build` → `public/build`.
