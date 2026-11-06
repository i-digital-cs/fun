# Phase 3E — Final map popup positioning & mobile UX

**Date:** 2026-11-05  
**Project:** `/home/d14098/funnity.com`  
**Status:** Published — see `PHASE3E_PUBLISH_2026-11-05.md`

## VERDICT: CONDITIONAL (publish)

Fixes shipped as `living-map-v3-DD9oKZOa.js`. Publish QA cleared mobile 320–430 dock overlap and desktop exact anchoring. Dashboard Quick View remains **NOT VERIFIED**.

---

## 1. Root cause — mobile dock overlap

**Published behavior (375px, city event, asset `living-map-v3-bIe2ShPL.js`):**

| Metric | Value |
|--------|-------|
| `cardRel.top` | **12** (near top of `.lm-stage`) |
| Overlaps HUD | **true** |
| Overlaps loc panel | **true** |
| Computed `top` / `bottom` | `0px` / `~55.6px` |

Live Blade rule in `stage.blade.php` `@media (max-width:639px)`:

```css
.lm-card {
  top: auto !important;
  bottom: .55rem;
  left: .55rem !important;
  width: auto !important;
}
```

Phase 3D JS set `style.top` / `style.left` **without** `!important`. Blade wins → with `bottom:auto` cleared inconsistently / dock math fighting `top:auto !important`, the open docked card sits at the **top** of the stage and covers loc + HUD controls. Search-area may clear or not depending on layout; buttons under the card are not reliably tappable.

**Fix (source):** `applyCardBox()` uses `element.style.setProperty(..., 'important')` for `top`/`left`/`right`/`bottom`/`width`. `dockCardInPanel()` places the card at the **bottom of `freeCardBand()`** (stage minus HUD / loc / city panel / search / MapLibre attribution). Compact media when the free band is short. `living-map-v35.css` no longer force-docks `.is-docked` / `.is-anchored` cards on mobile.

Blade was **not** edited (approval gate). Optional follow-up: remove or narrow the Blade `top:auto !important` rule after publish soak.

---

## 2. Root cause — exact marker popup clipping

`placeCard()` previously positioned with an **underestimated card height** (and weaker clamp). When the pin sat near the bottom of the free band, the real measured card taller than the estimate extended **past `.lm-stage` bottom**.

**Stress simulation (pin tip near bottom of free band):**

| Layout | `fullyInside` | `overflowBottom` |
|--------|---------------|------------------|
| Old estimate H≈160 vs actual H≈294 | **false** | **+37** |
| Phase 3E measure + free-band clamp | **true** | **−63** |

**Fix (source):** measure actual card size after preferred width; prefer **above** marker; flip below when room; `clampIntoBand` into `freeCardBand()`; `max-height` + overflow when content exceeds band; `syncCardPosition` on move / moveend / resize.

---

## 3. Location accuracy (unchanged)

| Class | Behavior |
|-------|----------|
| **exact** | Real marker + anchored preview + camera focus |
| **city** / **none** | Docked in-panel preview only; no fabricated coords / fake pins |
| DB | **Not modified** |

---

## 4. Files changed (source only)

| File | Change |
|------|--------|
| `resources/js/living-map/create-living-map.js` | `freeCardBand`, `applyCardBox` (`!important`), `placeCard`, `dockCardInPanel`, `syncCardPosition`, media compact |
| `resources/css/living-map-v35.css` | Mobile force-dock scoped to `:not(.is-docked):not(.is-anchored)` |
| `docs/FLUTTER_WEB_PARITY.md` | §27 + gap **#43** Phase 3E |
| `docs/phase3_event_qv_qa/PHASE3E_MAP_POPUP_POSITIONING.md` | This report |

**Not changed:** Blade/PHP (live), DB, cron/queue/Brevo, `public/build/*`.

---

## 5. Responsive QA (unpublished polyfill matching source)

| Width | City dock inside stage | Overlap HUD/loc/search |
|-------|------------------------|-------------------------|
| 320 | PASS | none |
| 375 | PASS | none |
| 390 | PASS | none |
| 430 | PASS | none |

Desktop exact: anchored inside stage after polyfill; stress case above shows clip → fix.

---

## 6. Regression notes

| Check | Status |
|-------|--------|
| Discover desktop / mobile map | Exercised |
| City selection → dock + precision copy | PASS (prod + polyfill) |
| Exact selection → pin + anchor | PASS |
| Marker popup clip (stress) | Fixed in source |
| List highlight | PASS |
| View details → Quick View | PASS on Discover map docked CTA (Close / Prev / Next present; Discover shell stayed mounted) |
| Stuck loader | PASS — no stuck overlay after QV open/close |
| Cluster / city collection | Prior Phase 3D behavior preserved (no coord invention) |
| Dashboard QV + scroll restore | **NOT VERIFIED** (`/dashboard` → `/login`) |

---

## 7. Screenshots

Client path: `/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

| File | Kind |
|------|------|
| `phase3e-prod-baseline-mobile-city-overlap.png` | **Production** published assets — city dock overlaps controls (`cardTop≈12`) |
| `phase3e-prod-baseline-desktop-exact-clip.png` | **Production** baseline (prior capture) |
| `phase3e-unpublished-mobile-320-city.png` | **Unpublished** session polyfill |
| `phase3e-unpublished-mobile-430-city-dock.png` | **Unpublished** polyfill |
| `phase3e-unpublished-desktop-city-dock.png` | **Unpublished** polyfill |
| `phase3e-unpublished-desktop-exact-anchored.png` | **Unpublished** polyfill |

Geometry metrics are SSOT; screenshot AI often mislabels the Focus spotlight as the event preview card.

---

## 8. Build / publish requirements (owner-gated)

1. Backup `public/build` (same gate pattern as Phase 3D).
2. `npm run build` in `/home/d14098/funnity.com` (Vite only — no Blade/PHP required for this fix).
3. Confirm new hashed `living-map-v3-*.js` (+ CSS if fingerprint changes) in `manifest.json`.
4. Smoke: mobile 375 city dock clears HUD/loc; exact pin near bottom stays inside stage; city vs exact accuracy unchanged.
5. Optional later: trim Blade mobile `.lm-card { top:auto !important }` once published JS owns placement.

**Do not publish until owner approval.**

---

## Flutter parity

Mirror free-band / safe-area preview placement and exact-vs-city accuracy on Discover map. No new API. Tracked in `FLUTTER_WEB_PARITY.md` gap **#43**.
