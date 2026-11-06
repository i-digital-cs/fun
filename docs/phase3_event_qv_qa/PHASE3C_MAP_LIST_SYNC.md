# Phase 3C — Map/List sync & unified Quick View (source ready)

## Verdict: CONDITIONAL — stop before Vite publish

Live production is unchanged until owner-approved `npm run build`.  
No Blade/PHP edits in this pass (would go live immediately).

## Architecture (production today)

| Surface | Map stack | List SSOT | View details |
|--|--|--|--|
| Discover (`LIVING_MAP_REPLACE_DISCOVER=true`) | MapLibre Living Map `create-living-map.js` mode=`discover` | `#discover-results` Blade cards `[data-discover-result-card]` | Was stolen by QV capture on **all** links |
| Discover Leaflet fallback | `discover-map.js` (skipped when living-map host present) | same | — |
| Home/Dashboard mini map | Living Map `LIVING_MAP_REPLACE_HOME` | Home carousel / social cards | Rich `.event-3d` already QV; dashboard social cards lack attrs |
| Marker “popup” | Floating `data-lm-card` + V3.5 spotlight (not Leaflet popup) | — | `data-lm-card-link` / spotlight button |

## Root causes fixed in source

1. **Map/list sync broken:** `event-quick-view.js` capture handler opened QV for every `[data-event-qv-open]` on Discover cards (title + image), so Living Map never received selection clicks.
2. **Invisible selection:** `[data-discover-result-card].is-active` had **no CSS**.
3. **Marker CTA → full page:** floating card / rail / spotlight lacked QV hooks.
4. **Scroll glitch:** `html { overflow:hidden }` removed scrollbar without gutter compensation; `scrollIntoView` could move the wrong scroller.

## Source changes (await Vite)

- `resources/js/event-quick-view.js` — Discover details-only QV; map CTAs; `openEventQuickViewById`; list scroll + scrollbar gap lock
- `resources/js/living-map/create-living-map.js` — list→map synthesize coords; selectGen; pane-local highlight scroll; card/rail/spotlight → QV
- `resources/js/page-loader.js` — ignore map QV CTAs
- `resources/css/app.css` — `.is-active` list highlight

## Publish requirements

1. Owner approve `npm run build` (backup `public/build` first, same gate as Phase 3B).
2. Optional later Blade: `data-event-qv-open` on dashboard `social-event-card` (JS already covers `article[class*="rounded-[1.15rem]"]`).
3. Do not rebuild QV modal chrome unless needed — this pass only wires sync + CTAs.

## Map/list sync status

**Implemented in source; NOT live until Vite.** Independent device QA still required after publish.
