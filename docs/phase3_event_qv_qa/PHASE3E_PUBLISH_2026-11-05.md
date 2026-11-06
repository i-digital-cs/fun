# Phase 3E — Safe production publish

**Date:** 2026-11-05  
**Git:** `main` @ `c94fd5d` (uncommitted Phase 3/3E work; **no commit/push**)

## VERDICT: CONDITIONAL

Published asset `living-map-v3-DD9oKZOa.js` verified on production Discover map for mobile dock clearance (320–430) and exact anchored popup (desktop). Authenticated Dashboard QV **NOT VERIFIED**. Mid-publish hotfixes: `inset` shorthand (browser was wiping `top`/`left` via `removeProperty('inset')`), SSR exact-coord enrich when list API omits lat/lng, and `!important` width measure against Blade `width:auto !important`.

---

## Preflight

| Check | Result |
|-------|--------|
| Project `/home/d14098/funnity.com`, branch `main` | OK |
| Phase 3D baseline previously healthy | OK (`bIe2ShPL` in pre-3D backup) |
| Vite inputs Phase 3/3E only (no living-world/chat source dirt) | OK |
| No new Blade/PHP required for this Vite publish | OK (Blade already live from earlier phases) |
| Queue worker healthy | OK (cron-fallback exit 0) |
| Funnity `schedule:run` disabled | OK |
| Disk / Node / npm | OK (~162G free, Node 20.20.2, npm 10.8.2) |

Note: An informal rebuild at ~11:33Z already had broken `removeProperty('inset')` 3E code on disk. Formal publish backed that up, then shipped corrected hashes.

---

## Backup

| Backup | Path |
|--------|------|
| Pre–Phase 3E formal publish | `/home/d14098/backups/funnity-public-build-pre-phase3e-publish-20261105T113435Z` |
| Prior preserved | `…/pre-phase3d-publish-…`, `…/pre-phase3c-…`, `…/pre-qv-centered-…` |

Pre-3E backup manifest SHA-256: `a6c9520ae7591c8ac0bcce2331e8cadde257d1324f93db23408de47c928d72b7`  
(living-map then: `living-map-v3-4i8FzXbu.js`)

---

## Build

`npm run build` succeeded (final ~1.8s). No migrations / cron / queue / Brevo / commit / push.

Publish-time source fixes (same task, before final QA pass):

1. `applyCardBox` → set `inset` shorthand with `!important` (do not `removeProperty('inset')`).
2. Discover click enrich: copy SSR `data-lat`/`data-lng` onto list API rows missing coords (exact only).
3. `measureCardSize` uses `width` `!important` and caps to preferred width (Blade mobile `width:auto !important`).

---

## Published asset hashes

| Entry | File | SHA-256 |
|-------|------|---------|
| living-map-v3 | `assets/living-map-v3-DD9oKZOa.js` | `645bda6b7deb831976fc58ee035ca365debad7b2a8b7b6e0cd8a1dbe1386ccff` |
| living-map-v35.css | `assets/living-map-v35-Bg6lwJsi.css` | `b1fdd05b4e7746b660055165a1b0b5efdc51e4235bf55a479184025931e73516` |
| app.js | `assets/app-DEdeXHxl.js` | `370765fed32a5f5c42e984ee3fa5d68213c0c101dfdd13975a31bee3b3b40b1c` |
| app.css | `assets/app-CS3Q1gnS.css` | `f8babb4764b93da25257495420afb425bd7034a37b25516a0055270b1df216aa` |

Manifest SHA-256: `de84b9c8200c7ecbca3de9b98ceb971cd2230377589ba37510f7405de09e8ba5`  
Served: `https://funnity.com/build/assets/living-map-v3-DD9oKZOa.js` **200**

---

## Production QA (published assets, no injection)

| # | Check | Result |
|---|-------|--------|
| 1–4 | Mobile city dock 320 / 375 / 390 / 430 | **PASS** — fully inside stage; no HUD/loc/search overlap; precision copy; no fabricated pin |
| 5 | Controls remain clear | **PASS** (geometry SSOT) |
| 6–8 | Exact popup inside stage; edge/zoom/resize | **PASS** — `is-anchored`, pin selected (`605`), `fullyInside`, survives zoom+resize |
| 9 | No fabricated coords | **PASS** |
| 10 | Map/list sync | **PASS** (list active + pin/dock) |
| 11–13 | View Details → QV; close; no stuck loader | **PASS** |
| 14 | Home / Discover list HTTP | **PASS** (200) |
| 15 | Console | No living-map errors observed during QA scripts |
| 16 | Dashboard authenticated | **NOT VERIFIED** (`/dashboard` → `/login`) |

---

## Screenshots

Client: `/c:/Users/hamad/AppData/Local/Temp/cursor/screenshots/`

| File | Meaning |
|------|---------|
| `phase3e-prod-mobile-375-city-dock.png` | Production mobile city dock after publish |
| `phase3e-prod-desktop-exact-anchored.png` | Production desktop exact anchored |

---

## Rollback

Not required. Rollback path if needed:

```bash
SRC=/home/d14098/backups/funnity-public-build-pre-phase3e-publish-20261105T113435Z
# or Phase 3D: .../funnity-public-build-pre-phase3d-publish-20261105T101830Z
cp -a "$SRC/manifest.json" /home/d14098/funnity.com/public/build/
cp -a "$SRC/assets/." /home/d14098/funnity.com/public/build/assets/
```

---

## Production changes

- `public/build/manifest.json` + hashed assets (Vite only)
- Source already updated under `resources/js/living-map/create-living-map.js` (uncommitted)

## Remaining issues

- Dashboard QV **NOT VERIFIED**
- Optional Blade cleanup: remove mobile `.lm-card { top:auto !important; width:auto !important }` after soak
- No git commit/push performed
