# Explore V4 FINAL — Visual & Functional Acceptance (UI polish)

**Branch:** `feature/funnity-explore-v4-map-first`  
**Date:** 2026-10-29  
**Git:** dirty tree preserved — **no commit / push / Vite publish** pending owner approval

## §15 Verdict table

| Area | Verdict |
|---|---|
| Egypt Map Framing | **CONDITIONAL** |
| Marker Visibility | **PASS** |
| Toolbar Design | **PASS** |
| Results Panel | **PASS** |
| Responsive Web | **CONDITIONAL** |
| Arabic RTL | **CONDITIONAL** |
| Flutter Parity | **CONDITIONAL** |
| Performance | **CONDITIONAL** |

### Verdict notes

- **Egypt framing (CONDITIONAL):** Live fitBounds + `EGYPT_FOCUS` + pane height cap (`70vh`) improve Egypt dominance vs the owner’s prior Europe/MENA-heavy shot. Surrounding Libya/Saudi/Jordan tiles can still appear at zoom≈5 on a wide pane. Tighter deferred `invalidateSize` + padding lives in source / isolated Vite build and is **not** published until approval.
- **Markers (PASS):** Live DOM count at nationwide load: **15** purple city aggregates (readable counts) + **1** orange exact pin (`بولينج`). Popup + View details verified.
- **Toolbar (PASS):** Single coherent row (~50px): Search | Location chip | Radius | Category | Apply | Map/List. Detect / place search / All Egypt live inside the location modal (not a second oversized toolbar row).
- **Results (PASS):** Compact cards; independent scroll inside capped split (~66% map / ~33% list).
- **Responsive (CONDITIONAL):** 1440 layout + metrics verified in browser. 1280/768/390 exercised earlier; re-shot PNGs could not be copied from the IDE Windows temp path onto this Linux host this pass — see metrics JSON + live URL.
- **RTL (CONDITIONAL):** Prior AR screenshot exists under `docs/design/explore-v4-final/screenshots/web-1440-ar-rtl.png`; not re-captured this pass.
- **Flutter (CONDITIONAL):** Egypt fit + purple aggregates + orange exact pins in `discover_map_view.dart`; location tests green. Device FPS **not** measured.
- **Performance (CONDITIONAL):** Map API clamp + single boot fetch preserved. No device Profile Mode numbers.

## Root causes addressed

1. **Tall map / poor framing** — Results list stretched the grid (`min-height: auto`), so the Leaflet pane grew ~1400px tall and fitBounds showed excess N–S geography. Fixed with inline `discover-split-grid` height cap (works without Vite CSS publish).
2. **Hard-to-see markers** — Distinct DivIcon styles: purple `#7138F5` aggregates, dark `#4C1D95` clusters, orange `#FF762E` exact pins.
3. **Disconnected toolbar** — Replaced multi-row Detect + duplicate place field with one toolbar + location modal.
4. **Vertical waste** — Toolbar ~50px; split capped at `min(70vh, 40rem)`.

## Live verification (2026-10-29)

URL: `https://funnity.com/events?view=map&discovery_mode=nationwide&distance_km=0`

| Metric (1440×900) | Value |
|---|---|
| Toolbar height | 50px |
| Split height | 630px |
| Map width share | 66.4% |
| Map aspect (w/h) | 1.215 |
| Visible aggregates | 15 |
| Visible exact pins | 1 |
| Approx zoom | 5 |

Evidence files:

- `docs/design/explore-v4-final/screenshots/ui-polish-20261029/layout-metrics-1440.json`
- `docs/design/explore-v4-final/screenshots/ui-polish-20261029/map-api-evidence.json`
- Browser screenshots captured in IDE session (Windows temp); prior set remains under `docs/design/explore-v4-final/screenshots/`.

## Tests

| Suite | Result |
|---|---|
| `DiscoverMapApiTest` | 10 passed |
| `ReverseGeocodeApiTest` | 3 passed |
| Flutter `discovery_location_state_test` | passed (summaryLabel expectation updated for reverse-label chip) |
| Flutter analyze (map widgets) | info only |

## Isolated Vite build (not published)

`/tmp/funnity-explore-v4-ui-verify/build/`

Includes pending JS: deferred Egypt `fitEgypt` / `invalidateSize`, places `city_id` resolution in location modal.

**Current live assets (already on disk):** `discover-map-DbZCsh3f.js`, `app-oQK7tFKP.js` (UI polish from earlier build). Blade height-cap CSS is live via inline `<style>`.

## Deployment readiness

| Item | Status |
|---|---|
| Production DB untouched | Yes |
| Destructive migrations | None |
| Git commit / push | **Not done** (per rules) |
| Vite publish to `public/build` | **Awaiting owner approval** |
| Rollback backup | `~/funnity-backups/explore-v4-ui-polish-20261029` (+ prior map-init backup) |

**Ready for owner visual review on live Blade UI.** Say the word to publish the isolated Vite assets (`discover-map-Dz94GnnO.js` / `app-DIatUEZZ.js` family) after approval.

## Files touched this polish pass (high level)

- `resources/views/events/index.blade.php` — compact toolbar + capped split
- `resources/js/discover-map.js` — Egypt focus, marker DivIcons, deferred fit
- `resources/js/discovery-location.js` — places `city_id` resolution
- `resources/views/components/events/discover-card.blade.php` — compact cards
- `resources/views/components/location-modal.blade.php` — Detect / All Egypt / place search
- `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart` — marker parity
- `docs/FLUTTER_WEB_PARITY.md` — Explore V4 roadmap item
- Tests + acceptance docs under `docs/design/`

## Remaining limitations

1. Publish pending JS for best Egypt refit after layout.
2. Optional: further zoom-in Egypt framing may clip Sinai/Hurghada on very wide panes — keep balanced bounds.
3. Flutter device map FPS unverified.
4. Repo PNG copy from IDE browser temp failed on this host; metrics + live URL are authoritative for this pass.
