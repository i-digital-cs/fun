# P0 — Home Living Map regression recovery (STOP before production change)

**Date:** 2026-11-05  
**Project:** `/home/d14098/funnity.com`  
**Git:** `main` @ `c94fd5d` (dirty Phase 3 tree preserved; **no commit/push/build/restore**)

## VERDICT: RECOVERY READY

Root cause identified; two compatible recovery paths assessed. **No production recovery applied.** Owner approval required before Vite publish or asset restore.

---

## 1. Exact regression root cause

Phase **3E** Living Map popup positioning (`freeCardBand` + `applyCardBox(inset !important)` + `compactCardMedia` + `max-height`/`overflow:auto`) runs for **all** Living Map modes, including Home/public (`mode=public|home`), not only Discover.

Home stage height is ~**280px**. After carving loc/HUD, the free band is barely taller than the card (~242–254px). Effects:

| Symptom (owner) | Mechanism |
|-----------------|-----------|
| Popup stuck upper / clipped at top of stage | `clampIntoBand` / dock `y = band.bottom - h` collapses to `band.top` (~14px) on a short stage |
| Image/content clipped | `compactCardMedia` forces media to **64px** when `bandH < 280` |
| Unwanted internal scrollbar | When `rawH > band.height`, code sets `overflow: auto` + `max-height` |
| Camera moves but popup feels disconnected | Exact path still calls `placeCard`, but free-band clamp parks the card at the **top** of the stage while the pin sits mid-stage (`cardTop≈15`, `pin.bottom≈140` on live Home) |

Discover-oriented mobile control clearance was never scoped with `isDiscover` / `.lm-shell--discover`.

---

## 2. First affected release

| Release | living-map asset | Home impact |
|---------|------------------|-------------|
| Pre–Phase 3C / pre-QV backup | `living-map-v3-DWO3vC7D.js` | No `is-docked` / free-band |
| Phase 3D | `living-map-v3-bIe2ShPL.js` | City dock via simple `style.left/top` (no free-band crush / no overflow:auto) |
| **Phase 3E (first break)** | Informal `4i8FzXbu` then **`DD9oKZOa`** | Free-band + compact media + max-height/overflow globally |

**First affected production Living Map hash:** Phase 3E line (`4i8FzXbu` → current `DD9oKZOa`).

Quick View / loader live in **`app-DEdeXHxl.js`** (unchanged since Phase 3D formal publish) — not the living-map regression vehicle.

---

## 3. Current published asset hashes

| Entry | File | SHA-256 |
|-------|------|---------|
| living-map-v3 | `assets/living-map-v3-DD9oKZOa.js` | `645bda6b7deb831976fc58ee035ca365debad7b2a8b7b6e0cd8a1dbe1386ccff` |
| living-map-v35.css | `assets/living-map-v35-Bg6lwJsi.css` | `b1fdd05b4e7746b660055165a1b0b5efdc51e4235bf55a479184025931e73516` |
| app.js | `assets/app-DEdeXHxl.js` | `370765fed32a5f5c42e984ee3fa5d68213c0c101dfdd13975a31bee3b3b40b1c` |
| app.css | `assets/app-CS3Q1gnS.css` | `f8babb4764b93da25257495420afb425bd7034a37b25516a0055270b1df216aa` |

Manifest SHA-256: `de84b9c8200c7ecbca3de9b98ceb971cd2230377589ba37510f7405de09e8ba5`

Live Home confirmed loading: `https://funnity.com/build/assets/living-map-v3-DD9oKZOa.js` (`data-mode=public`).

---

## 4. Recommended recovery method

### Preferred — **B: Mode-scoped hotfix** (Home restore + Discover isolation)

In `create-living-map.js` (then Vite publish after approval):

1. **Home / public / embedded (`!isDiscover`):** restore Phase 3D-style `placeCard` / city dock using simple `left`/`top` near the projected marker (or Home’s prior dock), **without** `freeCardBand` early-return, **without** `compactCardMedia`, **without** `max-height` + `overflow:auto`.
2. **Discover (`isDiscover`):** keep Phase 3E free-band + `inset !important` + mobile control clearance.
3. Scope any CSS that forces dock/anchored layout to `.lm-shell--discover` (not bare `.lm-card`).

Preserves: centered Event Quick View + page-loader (`app-DEdeXHxl.js` untouched).

### Emergency — **A: Living Map–only rollback to Phase 3D**

Restore from `/home/d14098/backups/funnity-public-build-pre-phase3d-publish-20261105T101830Z`:

| Restore | Keep live |
|---------|-----------|
| `living-map-v3-bIe2ShPL.js` | `app-DEdeXHxl.js` (QV + loader) |
| `living-map-v35-C4IbwIQg.css` | `app-CS3Q1gnS.css` (QV chrome CSS) |
| Update `manifest.json` entries for living-map-v3 + living-map-v35 only | |

**Compatibility:** `app-DEdeXHxl.js` is **identical** in pre-3D backup and live — safe to pair with `bIe2ShPL`.

**Tradeoff:** Discover loses Phase 3E mobile dock/clip fixes until hotfix B ships.

Do **not** restore full pre-3C/`pre-qv-centered` build — would risk Quick View/loader assets.

---

## 5. Files that would change (after approval)

### Path B (hotfix + publish)
- `resources/js/living-map/create-living-map.js`
- Possibly `resources/css/living-map-v35.css`
- `docs/FLUTTER_WEB_PARITY.md` / this QA doc
- New hashed `public/build/assets/living-map-v3-*.js` (+ CSS if touched)

### Path A (asset restore only)
- `public/build/manifest.json`
- Copy `living-map-v3-bIe2ShPL.js`, `living-map-v35-C4IbwIQg.css` into `public/build/assets/`
- **No** Blade/PHP/DB; backups left intact

---

## 6. Home before/after evidence (production baseline — published 3E)

Client screenshots: `/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

| File | Notes |
|------|-------|
| `phase3e-home-regression-city-dock-top.png` | **Production** Home — city carousel selection; popup clipped at top of short stage; “map location unavailable” |

Geometry SSOT (published `DD9oKZOa`, Home `mode=public`, stage **280px**):

| Selection | Classes | cardTop | pinBottom | Notes |
|-----------|---------|---------|-----------|-------|
| Exact `605` | `is-anchored` | **15** | **140** | Clamped to free-band top; mediaMax **64px** |
| City `465` | `is-docked is-approx` | **15** | n/a | Dock collapsed to top; nearPin false |

After recovery (not yet applied): expect marker-adjacent exact popup, full media, no internal scrollbar, city preview not crushed into the top clip zone.

---

## 7. Discover compatibility

- Path B: Discover keeps 3E free-band behavior behind `isDiscover`.
- Path A: Discover reverts to Phase 3D dock (`bIe2ShPL`) — city dock + exact placeCard without 3E mobile free-band; still no fabricated coords; QV CTAs remain via `app.js`.

---

## 8. Quick View / loader preservation

| Asset | Role | Action |
|-------|------|--------|
| `app-DEdeXHxl.js` | Event Quick View + `data-no-loader` wiring | **Keep** under A or B |
| `app-CS3Q1gnS.css` | QV / app chrome | **Keep** under A or B |
| living-map only | Map popup placement | Replace / hotfix |

Evidence: same `app-DEdeXHxl.js` hash in pre-3D backup and live; Discover QV still opened in Phase 3E publish QA.

---

## 9. Production activation plan (awaiting owner approval)

1. Choose **B** (preferred) or **A** (emergency).
2. Backup current `public/build` again (new timestamped dir; do not overwrite existing backups).
3. Apply chosen recovery.
4. Smoke: Home exact + city + carousel; Discover exact + city; QV open/close; loader not stuck; mobile Home + Discover.
5. If fail → restore the new pre-recovery backup (rollback-of-rollback).

---

## Freeze notes

- Uncommitted Phase 3 working tree **preserved**.
- Existing backups **preserved** (`pre-qv-centered`, `pre-phase3c`, `pre-phase3d`, `pre-phase3e`).
- **No** npm build, restore, Blade edit, commit, or push in this task.
