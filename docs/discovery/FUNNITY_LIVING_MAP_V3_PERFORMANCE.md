# Living Map V3.1 — Performance Notes

## Measured (headless Chromium, preview page)

Source: `docs/discovery/v3_1_qa/perf-sample.json` + Cairo extrusion query.

| Metric | Result | Status |
|---|---|---|
| Explore V4 map API | ~200–800 ms | Existing endpoint |
| OpenFreeMap style JSON + remap | CDN + local clone | VERIFIED |
| MapLibre GL JS | CDN, preview page only | VERIFIED |
| Vite chunk `living-map-v3-*.js` | Not in `app.js` | VERIFIED |
| HTML markers in featured frame | ~12 | VERIFIED |
| JS heap mid City Tour | ~36 MB | VERIFIED |
| `building-3d` features @ Cairo z14.3 | 145 | VERIFIED |
| Tour RAF + single arc GeoJSON | Cleared after hop | IMPLEMENTED |

## Guardrails

- Map constructs only on preview page.
- IntersectionObserver pauses tour offscreen.
- `visibilitychange` pauses tour.
- `prefers-reduced-motion` disables pitch tour + float.
- Cooperative gestures reduce scroll hijack on mobile.
- City Tour excludes `city_cluster` to avoid noisy hops.

## Bundle impact on production Home

**None** — `app.js` does not import `living-map-v3.js`. Home Mini Map remains Leaflet.

## Remaining before any production rollout

- Confirm OpenFreeMap capacity or self-host tiles.
- Lighthouse / Web Vitals on Home after integration.
- Flutter timeline FPS on mid-tier Android.
- Memory after 5-minute cinematic loop on mid-tier phones.
