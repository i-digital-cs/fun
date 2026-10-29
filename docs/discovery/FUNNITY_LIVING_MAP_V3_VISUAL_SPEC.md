# Living Map V3.1 — Visual Spec

Primary approved reference: `docs/a_clean_modern_colorful_ui_web_dashboard_mockup.png`  
Rejected baseline: `docs/screencapture-funnity-preview-living-map-v3-2026-10-29-17_24_27.png`

## Brand tokens

| Token | Value |
|---|---|
| Primary purple | `#7138F5` |
| CTA orange | `#FF762E` |
| Canvas / land | `#FFF6EC` / `#F4F0FF` |
| Water | `#6EB6FF` |
| Parks | `#C8F0D8` |
| Labels | `#2A2150` |

## Basemap (Funnity style)

Reusable factory: `resources/js/living-map/funnity-style.js` remaps OpenFreeMap Liberty / OpenMapTiles paint properties — **not** a CSS filter over the canvas.

| Layer family | Treatment |
|---|---|
| Background / land | Soft lavender–ivory |
| Water | Vibrant sky blue |
| Roads | Warm white / peach hierarchy |
| Parks / wood | Pastel mint |
| Buildings | Pale lavender fill + decorative `building-3d` extrusion from ~z12 |
| Borders | Low-opacity purple; not visually noisy |
| Labels | Dark navy + light halo |

Camera: pitch ~42–55°, light bearing. Nationwide overview softens pitch; city scale increases depth.

## Markers

| Kind | Treatment |
|---|---|
| Exact event | Teardrop pin, category color + emoji, white rim, soft ground glow |
| Selected / tour | Scale + glow + float (disabled under `prefers-reduced-motion`) |
| City aggregate | Purple pill with count + city name |
| City cluster (API zoom) | Indigo cluster pill (“N cities”); not treated as a named venue |

### Category colors

Sports / Football → orange · Gaming / PlayStation → electric blue · Music → purple · Food → amber · Social → pink · Outdoor → emerald · Cinema → coral · Default → Funnity purple

## Cards

Frosted-glass floating preview: media or emoji fallback, category/city chip, title, meta, CTA arrow. City mode always says approximate / city-level. Clamped inside the map stage.

## Motion

- Ease-in-out cubic camera (≈2–2.4s).
- Exact tour (≥2 exact venues): highlight → card → curved light arc → next.
- **City Discovery Tour** when exact venues are insufficient: named city aggregates only.
- Pause on drag/zoom/click/hidden tab/offscreen/`prefers-reduced-motion`.

## What we intentionally do not fake

- Illustrated ships / palms / stadium meshes from Image A.
- Invented Live badges, distances, or participant faces.
- City centroids as exact venue pins.
- Fabricated building survey heights (extrusion is decorative OSM metadata).

## Layout

Keep Funnity dashboard sidebar + header. Map is a rounded stage inside the existing shell — English LTR left sidebar, Arabic RTL right sidebar.
