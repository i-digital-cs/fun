# Living Map V4.0 — Visual QA

**Status:** Visual prototype complete — **stopped for owner approval**  
**Preview:** `/preview/living-map-v4` (auth, signed URL, or preview token)  
**Architecture:** Option C Hybrid — approved for prototype only  
**Production Home / Discover / V3.4 / V3.5.x:** unchanged  

Signed example:

```bash
php artisan tinker --execute="echo URL::temporarySignedRoute('preview.living-map-v4', now()->addDays(7));"
```

---

## 1. Screenshots

| File | Viewport | What it shows |
|---|---|---|
| `01-desktop-discover.jpg` | 1440×1200 full page | Discover mode — isometric Port Said scene, pins, glass filter, spotlight, carousel |
| `02-desktop-scene-stage.jpg` | Stage crop | Scene fidelity, arcs, pins, disclaimer, spotlight |
| `03-desktop-carousel.jpg` | Carousel crop | Real event cards + approximate labels |
| `04-desktop-navigate.jpg` | 1440 full page | Navigate mode — MapLibre geographic map, same events/chrome |
| `05-mobile-discover.jpg` | 390×844 | Basic mobile web adaptation |
| `capture_report.json` | — | Automated checks + Discovery API evidence |

Reference (design target, not a basemap): `docs/Explore Around You Events Dashboard.png`

---

## 2. Reference comparison

| Reference trait | V4.0 Discover | Notes |
|---|---|---|
| Isometric city perspective | **Yes** | Original Port Said–class plate (not MapLibre) |
| Rich environmental detail | **Yes** | Harbor, ships, lighthouse, stadium, palms, urban blocks |
| Vibrant controlled palette | **Yes** | Mediterranean water + purple/orange brand accents in UI |
| Premium lighting | **Yes** | Soft sunny / golden cinematic plate |
| Glowing category pins | **Yes** | Multicolor emoji teardrops + purple aggregate count |
| Floating event spotlight | **Yes** | Real event fields only; approximate disclaimer |
| Glass location filter | **Yes** | City + GPS + distance slider |
| Event carousel | **Yes** | Shared Discovery data |
| Discover ≠ geographic map | **Yes** | Persistent illustrated disclaimer |
| Precise geography available | **Yes** | Navigate mode (MapLibre) |

**Not pixel-identical to the mock** (expected): mock invents denser pins, “Live Now,” participant avatars, and decorative arc density. V4 binds **only live API events** onto a finite hotspot layout.

---

## 3. Asset / license inventory

| Asset | Path | License / source |
|---|---|---|
| Port Said cinematic plate | `public/images/living-map/v4/port-said-cinematic.jpg` (1920×1080, ~391 KB) | **Original** generated for Funnity V4 (2026-11-02). No stock watermark. Product use. See `public/images/living-map/v4/ASSETS.md` |
| Design reference | `docs/Explore Around You Events Dashboard.png` | Owner-provided mockup (design only) |
| MapLibre GL 4.7.1 | CDN unpkg | BSD-3 |
| OpenFreeMap / OpenMapTiles style | Navigate mode via existing `living_map.style_url` | Free vector tiles + attribution (existing V3 path) |
| RTL text plugin | `@mapbox/mapbox-gl-rtl-text` | Existing Living Map dependency |
| Discovery APIs | `/api/discovery/map`, `/api/discovery/city-events` | First-party |

**Paid assets / paid map APIs:** none used.

---

## 4. Functional evidence (capture run)

From `capture_report.json`:

- Discover stage art loaded (`naturalWidth` 1920×1080).
- Pins: 1 aggregate + 3 event pins (matches live Port Said city-events count at capture time).
- Spotlight title from API: “Console night with friends”.
- Disclaimer visible in Discover; hidden in Navigate.
- API `200`: discovery map (Port Said bbox + origin) + `city_events?city_id=11`.
- Navigate: scene hidden, MapLibre canvas present, geographic markers painted.

Integrity rules observed:

- No invented events, images, or participant counts.
- “Live Now” only if `starts_at`/`ends_at` window is truly live.
- Scene pins labeled approximate / illustrated — not exact venue coordinates.
- GPS “Use my location” switches to **Navigate** (honest geography when leaving the hero city plate).

---

## 5. Performance observations

| Item | Observation |
|---|---|
| Hero JPG | ~391 KB @ 1920×1080 — acceptable for prototype; WebP encode recommended next |
| V4 JS chunk | ~20 KB gzip ~7 KB (`living-map-v4-*.js`) |
| V4 CSS | ~14 KB gzip ~3.5 KB |
| 3D framework | **None** — 2.5D plate + DOM/SVG overlays |
| Motion | Pin float + spotlight; respects `prefers-reduced-motion` |
| MapLibre | Lazy-init on Navigate only |
| Production Home bundle | Unaffected (separate Vite entry) |

---

## 6. Functional limitations (prototype)

- Single hero city art (Port Said). Other cities do not have scene kits yet.
- Hotspot positions are artistic slots, not geocoded rooftops.
- Decorative arcs are visual only (not routes).
- Navigate marker chrome is lighter than V3.5.x (shared state; not a full V3.5 skin port).
- List / Nearby modes reuse carousel/list patterns; Nearby does not yet deep-link a separate nearby page.
- No parallax layers / day-night cycle yet.
- Flutter not implemented (preview-only; tracked in `docs/FLUTTER_WEB_PARITY.md` §10 item 37).

---

## 7. Next implementation recommendations (after owner visual OK)

1. Commission or refine hero art to closer match mock density/composition if desired (still original / licensed).
2. Add WebP/AVIF srcset + blur-up placeholder.
3. Hotspot editor / JSON tooling for additional cities; fallback → Navigate when no scene.
4. Align Navigate markers with V3.5.x premium pin language while keeping Discover distinct.
5. Stronger mobile sheet for spotlight (match V3.5.7 bottom sheet pattern).
6. Accessibility pass: pin keyboard order, focus traps, reduced-motion QA video.
7. Only after written approval: consider Home cutover plan (separate gate from this prototype).

---

## Acceptance gate

**Stop.** Do not expand into full V4 production rollout without owner visual approval of these screenshots and the hybrid Discover/Navigate behavior.
