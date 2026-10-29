# Map initial-load repair (2026-10-29)

## Backup

`/home/d14098/funnity-backups/explore-v4-map-init-repair-20261029/`

- `git-HEAD.txt` → `458165bc42c88d16fb23224a953744a352845aa9`
- `git-branch.txt` → `feature/funnity-explore-v4-map-first`
- `git-status-short.txt`, source tree, `public-build/` snapshot
- `hashes-before.txt` / `hashes-after.txt`

Live `public/build` **not** modified (hash of `discover-map-GZmVERnv.js` unchanged).

## Root cause (refined)

1. **HTTP 422** on oversized low-zoom viewports (nationwide open).
2. Even when 200: short map pane + center `26.8` clipped Nile Delta → only southern aggregates (e.g. Hurghada).
3. Error UI `setAttribute` bug aborted Leaflet init in the new error-handling path.

## Fixes (source only; await publish approval)

| File | Change |
|---|---|
| `DiscoverMapService.php` | Zoom ≤7 oversized bbox → clamp to Egypt envelope (`bounds_clamped` / `nationwide_mode`) |
| `discover-map.js` | `fitBounds(Egypt)` on nationwide; Retry error panel; fixed `setAttribute` |
| Flutter `map_discovery.dart` / `discover_map_view.dart` | Client clamp + Egypt `CameraFit` + Retry banner |

## Tests

`DiscoverMapApiTest` — **10 passed** (incl. zoom 4/5/6 Egypt-wide clamp, past exclusion, no double-count).

## Isolated verify evidence

- Screenshot: `docs/design/explore-v4-final/screenshots/web-1440-initial-load-fixed.png`
- API: `docs/design/explore-v4-final/screenshots/map-init-api-evidence.json`  
  → HTTP **200**, `aggs: 15`, `clusters: 1`, `clamped: true`, `interactive: 16`

## Live today without asset publish

- **PHP clamp is already active** on this production checkout (oversized zoom-6 → 200).
- **JS fitBounds + error UI** require Vite publish of new `discover-map-*.js` (built under `/tmp/funnity-map-init-verify`, artifact also in backup folder).

## Release (owner approval required)

1. Preserve/commit Explore V4 + this repair on the feature branch.
2. Isolated `npm run build` → atomic swap `public/build`.
3. Smoke `/events?view=map` → aggregates on first paint, no console errors.
4. Rollback: restore backup `public-build/` + previous `DiscoverMapService.php`.
