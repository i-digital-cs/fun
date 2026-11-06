# Phase 3C — Final safety review & conditional production publish

**Date:** 2026-11-05  
**Project:** `/home/d14098/funnity.com`  
**Git:** `main` @ `c94fd5d` (uncommitted Phase 3 / 3C work present; no commit/push)

## VERDICT: CONDITIONAL

Exact-event coordinate handling verified and Vite assets published. Guest QA covered map/list sync, centered Quick View (Home / Discover map+list / map popup), history, loader, and no-coords safety. **Dashboard authenticated cards (#7)** and **cluster drill-down (#12)** were **not verified** in this session (login wall / no aggregate markers in viewport). Per gate rules those are not marked PASS → overall **CONDITIONAL**.

---

## Coordinate handling: Exact-event only

Mandatory correction in `resources/js/living-map/create-living-map.js`:

- `itemFromDiscoverCard` accepts lat/lng only when `data-geo="exact"` with finite coordinates; otherwise `latitude/longitude = null`, `location_precision = 'none'`.
- `selectEvent` flies camera / places floating card **only** when `hasExactCoords` (exact precision + finite lat/lng).
- Selected pin resolved via `pinByEventId.get(String(item.id))`; aggregate markers (`lm-marker--agg`) never stand in as the event pin.
- Missing coordinates: list row still highlights, details/floating card available, **no invented camera move**, **no approximate event marker**.
- Database untouched.

Live check: event `605` (`geo=exact`) → exact pin selected. Event `489` (`geo=city`, no lat) → row `is-active`, **no** exact pin selected.

---

## Backup

| Item | Value |
|------|--------|
| Path | `/home/d14098/backups/funnity-public-build-pre-phase3c-20261105T094136Z` |
| Contents | `manifest.json` + `assets/` (25 files) |
| Manifest SHA-256 | `486fbee1337b66f7b1cf48b9f8f50c633f027187b6e7d4a79de8626075a3e13b` |
| Phase 3B preserved | `/home/d14098/backups/funnity-public-build-pre-qv-centered-20261105T085235Z` (not overwritten) |
| Post-publish current manifest | `6ae7c188da6d6cd19ad20cdbe797aaf940d05793aa854ca9cf2cdb000ec54e77` (differs from backup — publish applied) |

---

## Vite build

| Asset | File | SHA-256 |
|-------|------|---------|
| app.js | `assets/app-DEdeXHxl.js` | `370765fed32a5f5c42e984ee3fa5d68213c0c101dfdd13975a31bee3b3b40b1c` |
| app.css | `assets/app-B3gHQ2xR.css` | `121256e4979bd1ff474243a91073f043f365ae37f524f237adf408ed22bd07e8` |
| living-map | `assets/living-map-v3-B5mvOhzD.js` | `bb5a36c517e55066a02e7fd6922e1ae5ad8c3250ba8a4c4b56c941063f6da643` |

Live HTML references match. Built `2026-11-05 09:41`. No migrations, no unrelated cache clears, no DB writes.

---

## Map/list QA (17 scenarios)

| # | Scenario | Result |
|---|----------|--------|
| 1 | Discover Map: row → camera focuses exact event coords | **PASS** (event 605 → 29.9803057, 30.9532648) |
| 2 | Correct event marker + floating popup | **PASS** |
| 3 | Marker → corresponding list row highlight | **PASS** |
| 4 | View Details in right list → centered QV | **PASS** (desktop ~980px centered @ 1440vw) |
| 5 | View Details in map popup → centered QV | **PASS** |
| 6 | Home event cards → centered QV | **PASS** |
| 7 | Dashboard event cards → centered QV | **NOT VERIFIED** (guest → `/login`) |
| 8 | Discover List cards → centered QV | **PASS** |
| 9 | Close modal → scroll/filters preserved | **PASS** |
| 10 | Browser Back / Forward | **PASS** |
| 11 | Rapid switching → no stale popups | **PASS** (`selectGen` + loader hidden) |
| 12 | Clustered markers → correct event | **NOT VERIFIED** (no agg pins in viewport this run) |
| 13 | Events without coords never fabricate camera | **PASS** (489 city/no lat: active, no exact pin) |
| 14 | Mobile map/list + modal | **PASS** (full-width QV @ 390px) |
| 15 | Global page loader never stuck | **PASS** |
| 16 | No new JS console errors | **PASS** (observed session clean) |
| 17 | Join/request intact | **PASS** (full show + QV fragment still expose Request to Join / Open full page) |

---

## Home / Discover / Dashboard

- **Home:** centered Quick View confirmed.
- **Discover Map + List:** sync + QV confirmed on published assets.
- **Dashboard:** not exercised while authenticated.

---

## Desktop / mobile screenshots

Captured via production browser session (Cursor IDE browser):

- `phase3c-discover-map-desktop.png` — map focus + exact venue floating card
- `phase3c-qv-desktop.png` — centered Quick View overlay
- `phase3c-qv-mobile.png` — mobile full-width Quick View

Client save location (session):  
`/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

---

## Regression findings

None blocking on verified scenarios. Residual gaps: authenticated dashboard QV; cluster-marker resolution under zoomed-out aggregates.

---

## Rollback: READY

Restore command (do not run unless needed):

```bash
BACKUP=/home/d14098/backups/funnity-public-build-pre-phase3c-20261105T094136Z
rsync -a --delete "$BACKUP/" /home/d14098/funnity.com/public/build/
```

Verify: `sha256sum public/build/manifest.json` → `486fbee1…` and live pages reference pre-3C hashes.

---

## Git status (summary)

Modified (not committed): Phase 3 QV/loader/security/living-map sources, Blade cards/layouts, routes, lang, FLUTTER_WEB_PARITY, etc.  
Untracked: `event-quick-view.js`, QV blades, security middleware/config, tests, QA docs.

No commit / no push performed.

---

## Production changes — exact scope

1. Corrected exact-event-only coordinate handling in living-map source.
2. New verified backup of prior `public/build` (Phase 3B backup kept).
3. Vite production build published under `public/build` (app JS/CSS + living-map-v3).
4. Blade/PHP QV surfaces already live from earlier Phase 3 work; this publish ships the JS/CSS map sync + exact-coord fix.

**Not changed:** DB, migrations, cron (Funnity queue cron only; `schedule:run` still inactive for Funnity), Brevo, queue worker config, branches/worktrees.
