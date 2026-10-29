# Living Map V3 — Performance Notes

## Prototype measurements (browser CDP / Network, public API)

| Metric | Result | Notes |
|---|---|---|
| Explore V4 map API | ~200–800 ms | Existing endpoint |
| OpenFreeMap style JSON | CDN cached | Liberty style |
| MapLibre GL JS (CDN) | ~gzip ~200KB class | Loaded only on preview page |
| `living-map-v3.js` Vite chunk | Small (custom logic only) | Not in Home/Discover bundles |
| Marker strategy | HTML markers, limited set | Avoid animating all pins |
| Tour | Single RAF camera + one arc layer | Stopped when offscreen / hidden |

## Guardrails

- Lazy: map constructs only on preview page.
- IntersectionObserver pauses tour offscreen.
- `visibilitychange` pauses tour.
- `prefers-reduced-motion` disables pitch tour + float.
- Cooperative gestures reduce scroll hijack on mobile.

## Bundle impact on production Home

**None** — `app.js` does not import `living-map-v3.js`. Home Mini Map remains Leaflet.

## Remaining work before production rollout

- Self-host or confirm OpenFreeMap capacity for Funnity traffic.
- Lighthouse / Web Vitals on Home after integration.
- Flutter timeline FPS on mid-tier Android (device required).
- Memory after 5-minute cinematic loop.
