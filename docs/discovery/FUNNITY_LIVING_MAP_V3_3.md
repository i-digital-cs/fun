# Living Map V3.3 — Reusable component, layout, i18n, images

## Owner direction

Keep the **original Funnity shell** (navbar / community chrome). Embed a **reusable Living Map** with V3.2 cinematic visuals + **side-by-side event list**. Do **not** replace production Home/Discover until approval.

## Architecture

| Piece | Path |
|---|---|
| Blade component | `resources/views/components/living-map/stage.blade.php` |
| Factory | `resources/js/living-map/create-living-map.js` |
| Style | `resources/js/living-map/funnity-style.js` |
| I18n helper | `resources/js/living-map/i18n.js` |
| Vite entry | `resources/js/living-map-v3.js` |
| Strings | `lang/{en,ar}/living_map.php` |
| Config gate | `living_map.replace_home_mini_map` (`LIVING_MAP_REPLACE_HOME`, default **false**) |

### Modes

- `preview` — `/preview/living-map-v3` (full controls, banner)
- `home` / `public` — compact stage, no floating card / tour HUD (ready when flag flips)
- `discover` — reserved (same engine; Discover still Leaflet until approval)

## Layout

Desktop grid: map (~68%) + vertical event list (~32%).  
Logical CSS: in LTR map is start (left), list end (right); in RTL map is start (right), list end (left).  
Mobile: map stacked above list.

## Localization

All chrome strings via `__('living_map.*')` injected as `data-i18n-*`. Event titles are **not** auto-translated (`dir="auto"`). MapLibre RTL plugin preserved (`setRTLTextPlugin(url, false)`).

## Event images

**Root cause:** Discover map APIs used `Event::imageUrl()` → `null` for most seeded events.  
**Fix:** serialize `coverUrl('card')` (uploaded cover **or** branded category stock WebP), same as Home Blade cards. Applies to Explore V4 map + `city-events` payloads.

## Production safety

- Home Mini Map remains Leaflet while `replace_home_mini_map=false` (verified **OFF** at ship time).
- Discover Leaflet unchanged.
- Preview route path unchanged: `/preview/living-map-v3` (signed).

## Flutter

Parity item §10.26 — implement after owner approves web V3.3 visual + Home flag. Share tokens, category colors, selection semantics, Explore V4 rules; do not port JS 1:1.

---

## Visual QA (2026-10-29)

Evidence: `docs/discovery/v3_3_qa/`.

| # | File | Check |
|---|---|---|
| 01 | `01-en-desktop-side-by-side.jpg` | EN LTR: Funnity shell, map left / list right, real cover thumbs |
| 02 | `02-en-event-with-image.jpg` | Selected event floating card + list image (bowling cover) |
| 03 | `03-en-mobile-390.jpg` | EN 390px: map above list, covers intact |
| 04 | `04-ar-desktop-side-by-side.jpg` | AR RTL: map right / list left, chrome Arabic (`فتح`, title) |
| 05 | `05-ar-event-selected.jpg` | AR selected event: `عرض التفاصيل`, covers, RTL split |
| 06 | `06-ar-mobile-390.jpg` | AR 390px: RTL stack, `فتح الاستكشاف الإنتاجي` |

Runtime sample (`perf-sample.json`): `dir=rtl`, RTL plugin `loaded`, CTA `فتح`, **49** list cards with images, Home replace flag **false**.

### Pass / note

| Criterion | Result |
|---|---|
| Original shell (not standalone chrome) | Pass |
| Side-by-side map + list (logical CSS EN/AR) | Pass |
| Laravel `living_map` EN/AR chrome | Pass |
| Real event images via `coverUrl('card')` | Pass (~49 imgs in list) |
| Explore V4 SSOT (`/api/discovery/map`, city-events) | Pass |
| Home gated off (Leaflet remains) | Pass |
| MapLibre RTL plugin | Pass (`loaded`) |
| Marker emoji glyphs in headless screenshots | Note — some SVG emoji render as □ in headless Chromium; normal desktop Chrome has emoji fonts |

### Not flipped (owner approval required)

- `LIVING_MAP_REPLACE_HOME` / Home mini-map swap
- Discover production MapLibre cutover
- Flutter Living Map screen
