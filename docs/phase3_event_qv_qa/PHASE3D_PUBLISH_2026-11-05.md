# Phase 3D — Safe production publish

**Date:** 2026-11-05  
**Git:** `main` @ `c94fd5d` (uncommitted Phase 3/3D work; no commit/push)

## VERDICT: CONDITIONAL

City-level docked preview and exact-marker selection verified on published assets without injection. Dashboard authenticated QV **NOT VERIFIED**. Mobile dock can overlap loc panel/HUD on short stages.

---

## Preflight

| Check | Result |
|-------|--------|
| Project `/home/d14098/funnity.com`, branch `main` | OK |
| Blade Phase 3D markup already live | OK (`data-lm-card-precision`, i18n attrs) |
| Old JS compatible with new Blade | OK (optional precision node) |
| Vite inputs dirty only Phase 3/3D-related | OK (no living-world/mapbox/chat source dirt) |
| Queue worker healthy | OK (cron-fallback exit 0) |
| Scheduler disabled for Funnity | OK (`schedule:run` not enabled) |
| Event Quick View | OK (`/events/{id}/quick-view` 200) |

Note: living-map `bIe2ShPL` was already serving Phase 3D JS before this formal rebuild (prior informal build ~10:15Z). Authorized rebuild kept the same living-map hash; `app.css` → new `app-Dq9Eiop_.css`.

---

## Coordinate handling: Exact-event only

- `exact` → camera + `placeCard` + `pinByEventId`
- `city` / `none` → `dockCardInPanel`, no fabricated pin/camera
- Live: city `483` docked + precision copy; exact `605` pin selected

---

## Backup paths

| Backup | Path |
|--------|------|
| Build (pre this publish rebuild) | `/home/d14098/backups/funnity-public-build-pre-phase3d-publish-20261105T101830Z` |
| Blade/lang | `/home/d14098/backups/funnity-phase3d-blade-lang-20261105T101830Z` |
| Prior preserved | `…/pre-phase3c-20261105T094136Z`, `…/pre-qv-centered-20261105T085235Z` |

Manifest (pre-rebuild) SHA-256: `8708636533666f6b27852b7e873738e8620ec5c4ed711adc60547bc77e67a8b7`

---

## Build result

`npm run build` succeeded (~2s). No migrations / cron / queue / Brevo / commit / push.

## Published asset hashes

| Entry | File | SHA-256 (prefix) |
|-------|------|------------------|
| app.js | `assets/app-DEdeXHxl.js` | `370765fed32a5f5c…` |
| app.css | `assets/app-Dq9Eiop_.css` | `e960b6eacf4e205d…` |
| living-map-v3 | `assets/living-map-v3-bIe2ShPL.js` | `3967da4fe39eb954…` |
| living-map-v35.css | `assets/living-map-v35-C4IbwIQg.css` | `02fc7d5038803e96…` |

Post-build manifest SHA-256: `60d1d734017cffd273ed3f9c5dd673b5f39538fc0eea70f5feb8be5aa8282359`

---

## Production QA (no injection)

| # | Scenario | Result |
|---|----------|--------|
| 1 | City event → docked preview | **PASS** (`is-docked`, precision text) |
| 2 | No fabricated marker/camera | **PASS** |
| 3 | Preview inside map viewport (desktop) | **PASS** |
| 4 | Does not cover controls (desktop) | **PASS**; mobile overlaps loc/HUD → residual risk |
| 5 | Exact → focus + anchored popup | **PASS** (`605`, `is-anchored`, pin selected) |
| 6 | Correct event ID | **PASS** |
| 7 | View Details → centered QV | **PASS** |
| 8 | Close QV restores context | **PASS** |
| 9 | Rapid city/exact switch | **PASS** |
| 10 | Discover Map desktop | **PASS** |
| 11 | Discover Map mobile | **PASS** (dock + precision; control overlap residual) |
| 12 | Home event QV | **PASS** |
| 13 | Discover List QV | **PASS** |
| 14 | Back/Forward | **PASS** |
| 15 | Loading/error | **PASS** (loader stayed hidden; no new console errors observed) |
| 16 | Clustered markers (Zagazig) | **PASS** (panel → event `483` docked, no exact pin) |
| 17 | Authenticated Dashboard | **NOT VERIFIED** (`/dashboard` → `/login`) |
| 18 | No stuck loader / console errors | **PASS** (session) |

---

## Screenshots

`/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

- `phase3d-prod-city-desktop.png`
- `phase3d-prod-city-docked-desktop.png`
- `phase3d-prod-exact-desktop.png`
- `phase3d-prod-city-mobile.png`

---

## Rollback readiness: READY

```bash
BACKUP=/home/d14098/backups/funnity-public-build-pre-phase3d-publish-20261105T101830Z
rsync -a --delete "$BACKUP/" /home/d14098/funnity.com/public/build/
# Blade/lang if needed:
# BLADE=/home/d14098/backups/funnity-phase3d-blade-lang-20261105T101830Z
```

Note: Phase 3C-only living-map hash `B5mvOhzD` was not retained on disk (404). Rolling past this snapshot falls back to pre-phase3c backup (`DWO3vC7D`) and loses 3C+3D JS.

---

## Production changes

- Authorized Vite rebuild published (`app-Dq9Eiop_.css`; living-map hash unchanged `bIe2ShPL`).
- Blade/lang Phase 3D strings already live from source tree.
- No DB/migrations/cron/queue/Brevo/commit/push.

## Remaining risks

1. Dashboard QV untested (auth redirect).
2. Mobile short-stage dock can overlap location panel / HUD.
3. Exact anchored card can slightly overflow stage bottom (~40px observed once).
4. No preserved mid-state backup of Phase 3C-only living-map asset.
