# Phase 3D — Map event selection UX polish

**Date:** 2026-11-05  
**Project:** `/home/d14098/funnity.com`  
**Status:** Source ready — **stop before Vite publish**

## VERDICT: CONDITIONAL

Root cause fixed in source. Blade copy/markup for the precision badge is already live (harmless until JS toggles it). Docked no-coords positioning requires Vite publish of living-map JS (+ CSS). Dashboard QV while authenticated was **not** verified (`/dashboard` redirected to login in this browser session despite Discover showing member chrome).

---

## Exact root cause

Selecting a Discover list row always called `openEventCard()`, which un-hides the floating `[data-lm-card]`.

`placeCard(map.project([lng, lat]))` ran **only** when the event had trusted exact coordinates.

For `data-geo="city"` / missing lat-lng (e.g. Evening hoops session):

1. List row correctly highlighted (`is-active`).
2. Camera correctly did **not** invent a move (Phase 3C safety).
3. Floating card opened with **no position update** → default absolute coords ≈ `(0,0)` / upper-left of `.lm-stage`.
4. Result felt disconnected from the selected event.

Discover Living Map shell is **not** `lm-shell--v35` (`isV35=false`), so the existing mobile V3.5 bottom-dock CSS did not apply.

---

## Event location accuracy classification

| Class | Source of truth | Map behavior (source) |
|-------|-----------------|------------------------|
| **exact** | `data-geo="exact"` + finite `data-lat`/`data-lng` from `hasPlacePin()` / event lat-lng | Focus camera to event coords; select `pinByEventId[id]` only; marker-anchored popup via improved `placeCard` clamp |
| **city** | `data-geo="city"` (city has centroid; event lacks place pin) | No camera invention; no event pin; docked in-panel preview + “Precise map location unavailable” + View details → Quick View |
| **none** | No exact pin and no city centroid attrs | Same as city for selection UX |

Live sample (Discover SSR): most list rows are `geo=city` without `data-lat`; event `605` is `geo=exact` (`29.9803057`, `30.9532648`). City aggregates (e.g. Zagazig collection) remain city-level markers, not event pins.

**Database location values were not modified.**

---

## Fix (source)

1. `dockCardInPanel()` — positions preview fully inside the map stage; avoids loc panel + HUD via `clampCardBox`.
2. `selectEvent` — exact → `placeCard`; otherwise → `dockCardInPanel` (never invent coords).
3. Precision badge + CTA copy (`precise_location_unavailable`, `view_details`) via living_map i18n + `[data-lm-card-precision]`.
4. Stronger `placeCard` collision avoidance (loc panel, HUD, city panel).
5. Mobile/short-stage media compacting so tall non-v35 cards fit inside the stage.
6. Flutter parity notes updated (`docs/FLUTTER_WEB_PARITY.md` §27 + gap #42).

---

## Verified map selection behavior

| Check | Result |
|-------|--------|
| City event (Evening hoops / Casual recovery) — production **before** | PASS evidence: card at stage `(1,1)` / mobile `(10,49)`, row active, no exact pin |
| City event — **after** source polyfill (session-only) | PASS: docked inside stage, precision copy visible in a11y tree, no exact pin, loader hidden |
| Exact event `605` — published | PASS: selected pin, card near pin (`nearPin=true`), camera focus |
| City collection Zagazig → event `483` | PASS: panel rows, discover row active, geo=city, **no** exact pin invented |
| Filters / list remain mounted | PASS (no full navigation on title select) |
| No global loader on select | PASS |
| Dashboard event cards | **NOT VERIFIED** (`/dashboard` → `/login` in this session) |

---

## Screenshots (Cursor session)

Client path: `/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

| File | Meaning |
|------|---------|
| `phase3d-before-city-desktop.png` | Production disconnect — upper-left card |
| `phase3d-after-city-desktop.png` / `phase3d-after-city-card.png` | Session polyfill of docked preview + precision label |
| `phase3d-exact-desktop.png` | Exact venue selection |
| `phase3d-before-city-mobile.png` | Mobile upper-left disconnect |
| `phase3d-after-city-mobile.png` | Mobile docked preview (session polyfill) |

After screenshots for docked UX use an **in-session polyfill mirroring source** — published Vite assets are unchanged until owner approval.

---

## Changed files (Phase 3D scope)

- `resources/js/living-map/create-living-map.js`
- `resources/js/living-map/i18n.js`
- `resources/css/living-map-v35.css`
- `resources/views/components/living-map/stage.blade.php` (i18n attrs + precision node + dock CSS)
- `lang/en/living_map.php` / `lang/ar/living_map.php`
- `docs/FLUTTER_WEB_PARITY.md`
- `docs/phase3_event_qv_qa/PHASE3D_MAP_SELECTION_UX.md` (this file)

---

## Production publish requirements

1. Owner-approved Vite production build (backup `public/build` first; do **not** overwrite prior Phase 3B/3C backups blindly — create a new timestamped backup).
2. Publish includes at least: `living-map-v3` (or current Discover entry) + linked CSS if hashed separately.
3. Re-QA city dock + exact anchor + Zagazig collection + authenticated Dashboard cards on **published** hashes.
4. No migrations, no DB location edits, no cron/queue/Brevo changes, no commit/push unless separately requested.

**Stopped before publishing.**
