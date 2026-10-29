# Living Map V3.1 — Visual QA

**Verdict for owner:** READY FOR OWNER VISUAL REVIEW (preview only — not production).

## References

| Image | Role |
|---|---|
| `docs/a_clean_modern_colorful_ui_web_dashboard_mockup.png` | Approved direction (Image A) |
| `docs/screencapture-funnity-preview-living-map-v3-2026-10-29-17_24_27.png` | Rejected V3 baseline (Image B) |
| `docs/discovery/v3_1_qa/*` | V3.1 browser evidence |

## Screenshot evidence (captured)

| File | State | Status |
|---|---|---|
| `01-featured-region-overview.png` | Featured activity region, pitch ~48°, aggregates | VERIFIED |
| `02-city-discovery-tour-card.png` | City tour card (e.g. Zagazig · 6 events) | VERIFIED |
| `03-cairo-3d-buildings.png` | Cairo z14.3 pitch 55°; **145** `building-3d` features | VERIFIED |
| `04-selected-city-aggregate.png` | Selected aggregate + frosted card | VERIFIED |
| `05-mobile-390.png` | 390×844 compact stage | VERIFIED |
| `06-arabic-rtl.png` | `dir=rtl` `lang=ar` after `/locale` | VERIFIED |
| `07-motion-tour-1.jpg` … `3.jpg` | Timed City Discovery Tour frames | VERIFIED (conditional video) |

## Comparison vs rejected V3

| Dimension | Required | Result |
|---|---|---|
| Color harmony | Major | IMPROVED — lavender land, aqua water, peach roads (`funnity-style.js`) |
| Map depth | Major | IMPROVED — pitch 42–55° + fill-extrusion at city zoom |
| Marker quality | Major | IMPROVED — category teardrops + named city pills (+ cluster variant) |
| Camera composition | Major | IMPROVED — activity-weighted featured region (not empty desert) |
| Floating cards | Major | IMPROVED — frosted glass, city vs event modes, CTA |
| Atmospheric styling | Major | IMPROVED — vignette/glow overlays, fog where supported |
| Overall premium feel | Major | IMPROVED vs Image B; still short of illustrated Image A art |

## Motion evidence

City Discovery Tour exercised in headless Chromium with three timed frames. Exact-event tour path is **IMPLEMENTED** but **CONDITIONAL** on ≥2 exact venues in the framed viewport (current Egypt seed data often has 0 exact in overview).

## Geographic integrity

| Rule | Status |
|---|---|
| Exact venues → category pins | IMPLEMENTED |
| City aggregates → approximate labeling | VERIFIED |
| `city_cluster` ≠ named city venue | IMPLEMENTED (indigo cluster pill; excluded from City Tour) |
| Focus labeled “Featured activity region” / not GPS | VERIFIED |
| No fabricated coordinates | VERIFIED |

## Performance sample (`perf-sample.json`)

| Metric | Value | Status |
|---|---|---|
| HTML markers | 12 | VERIFIED |
| JS heap (tour mid) | ~36 MB | VERIFIED |
| `building-3d` layer present | true | VERIFIED |
| Buildings queried at city zoom | 145 (Cairo shot) | VERIFIED |
| Production Home bundle impact | none (`living-map-v3` Vite entry only) | VERIFIED |

Mobile FPS / Lighthouse on Home: **CONDITIONAL** (not measured — preview-only).

## Honest gaps vs Image A

- No illustrated ships, palms, stadium meshes.
- Extrusion heights are decorative OSM/OpenMapTiles metadata, not surveyed architecture.
- Headless screenshots may show tofu boxes for emoji (font limitation); desktop Chrome in IDE showed category icons correctly.
- Guest preview uses marketing header (no member sidebar); authenticated members keep dashboard shell.

## Production safety

Home Mini Map + Discover Leaflet **unchanged**. Route remains signed/auth preview: `/preview/living-map-v3` (`noindex`). Flutter rollout **deferred** until owner approves visual direction.
