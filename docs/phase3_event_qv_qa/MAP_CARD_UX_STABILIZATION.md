# Living Map — Final Card UX Stabilization (source, pre-publish)

**Status:** Source complete · **production untouched** (`living-map-v3-CkUup4RW.js`)  
**Date:** 2026-11-05

## Proposed behavior

### Home / Public (`data-mode` home|public)

| Selection | Camera | Preview |
|-----------|--------|---------|
| Exact | Focus marker (unchanged) | **Compact bottom dock** (thumb + title + place + View details) |
| City / none | No invented pin / no camera invent | Same compact bottom dock + precision label |

- Does **not** place a tall card beside the marker on the ~280px stage.
- Marker remains visible in the open map area above the strip.
- `overflow: hidden` only · **no** internal scrollbar · media is a fixed 72px (64px mobile) thumb (never Discover 64px vertical crush / hide).
- Carousel sync + `enrichItemExactCoords` preserved.
- View details → existing centered Event Quick View.

### Discover (`data-mode=discover`)

| Selection | Desktop | Mobile (≤639px) |
|-----------|---------|-----------------|
| Exact | Anchor in free-band when strip/card fits; else compact dock | Compact dismissible dock in free-band (clears HUD + tab bar) |
| City / none | Compact dock in free-band | Same |

- Map/list sync, filters, list scroll unchanged.
- **No** `overflow: auto` inside previews — compact layout instead of scrolling a large card.
- No invented coordinates.

## Implementation (source)

| File | Change |
|------|--------|
| `resources/js/living-map/create-living-map.js` | `enableCompactPreview` / Home always `dockCardHome`; Discover anchor-or-compact without scrollbars; free-band eats mobile tab bar |
| `resources/js/living-map-v3.js` | Import `living-map-preview.css` |
| `resources/css/living-map-preview.css` | Scoped `.lm-shell .lm-card.is-compact` layout |
| `docs/FLUTTER_WEB_PARITY.md` | Track Flutter mirror of compact Home dock vs Discover free-band |

**Not changed:** Blade/PHP, DB coords, queue/cron/Brevo, `public/build`.

## Safe publish plan (owner-gated, later)

1. Confirm `public/build` still `CkUup4RW` + backups intact.
2. Backup `public/build` → `…/pre-map-card-ux-YYYYMMDDTHHMMSSZ`.
3. Isolated `vite build --outDir /tmp/…` · confirm manifest delta is **only** `living-map-v3` (+ its CSS dependency if emitted).
4. Surgical promote LM JS (+ preview CSS asset if separate) · keep `app-DEdeXHxl.js`.
5. Prod QA matrix below · rollback = restore backup.

## QA matrix (pre-publish)

| # | Case | Prod before (`CkUup4RW`) | Source / isolated after | Status |
|---|------|--------------------------|-------------------------|--------|
| 1 | Home desktop exact | Tall ~256×280 stage card, media 116, pin covered | Compact dock ~105h, media 72 thumb, pin above strip | Ready |
| 2 | Home desktop city/none | Tall docked fit | Same compact dock + precision chip | Ready |
| 3 | Home carousel exact | Enrich + tall card | Enrich + compact dock | Ready |
| 4 | Home mobile 320/375/390/430 | Tall card fights stage | Compact bottom strip (64px thumb) | Ready (source) |
| 5 | Discover desktop exact | Free-band anchor / possible `overflow:auto` | Anchor if fits; else compact; **no** auto scrollbar | Ready |
| 6 | Discover desktop city/none | Free-band dock | Compact dock in free-band | Ready |
| 7 | Discover mobile widths | Media often `display:none` + scrollbar | Compact dismissible dock; tab bar reserved | Ready (source) |
| 8 | Pan/zoom/resize | Sync placement | Home stays docked; Discover syncs anchor/dock | Ready |
| 9 | Open/close | Map click closes | Unchanged | Ready |
| 10 | Centered Quick View | `app-DEdeXHxl` | Unchanged (not in LM delta) | Ready |
| 11 | Back/Forward | QV history | Unchanged | Ready |
| 12 | Global loader | OK | Unchanged | Ready |
| 13 | Clipped / scrollbar / blocked controls | Home tall-card risk | Compact strip clears map chrome | Ready |

**Screenshots:** `docs/phase3_event_qv_qa/screenshots/map-card-ux/` · browser `before-home-desktop-exact.png`, `after-home-desktop-exact-isolated.png` (session overlay; prod files unchanged).

**Isolated compile:** `/tmp/funnity-map-card-ux-20261105T123308Z` → `living-map-v3-DB2GKVLr.js` + css `living-map-v3-BhOn-Q4e.css`. Manifest delta vs prod: living-map-v3 entry only (`app` hash stable).

## Remaining limitations

- Compact strip hides people/avatars and live pill (detail stays in Quick View).
- Home exact is no longer marker-adjacent (by design); pin selection + camera convey focus.
- Discover desktop tall free-band may still show a non-compact card when height ≥ 240px.
- Blade mobile `top:auto !important` still requires JS `inset` important for placement until optional Blade cleanup.
- Full mobile viewport after-screenshots on real published assets require the gated publish below.
