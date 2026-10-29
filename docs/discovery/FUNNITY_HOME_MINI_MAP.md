# Home Mini Map — Explore Around You

## Root cause (2026-10-29 blank map)

Two defects combined into a blank gray rectangle:

1. **Init never ran reliably** — Vite `type=module` scripts execute when `document.readyState !== 'loading'`, but older code only registered on `DOMContentLoaded` when state was still `loading`. Result: Leaflet never started.
2. **Zero-height Leaflet pane** — without an inline/min-height on the canvas, Leaflet reported `size.y = 0`, producing a degenerate bounding box (`north === south`) so `/api/discovery/map` returned **422 Invalid bounding box**. Tiles could not paint a useful viewport; markers never loaded.

## Fix

- Always boot (`loading` → listen, else run immediately) + IntersectionObserver + app.js progressive import.
- Explicit canvas height (230 / 250 / 300) + `invalidateSize()` + Egypt-bounds fallback for API queries.
- Loading / error / retry overlays; keep Explore Full Map + event strip.
- City-level events → aggregates (never fake exact pins); approximate distance labels on previews.
- Same component on `/dashboard` and public `/`.

## Web

- Component: `resources/views/components/dashboard/home-mini-map.blade.php`
- JS: `resources/js/home-mini-map.js`
- API: `GET /api/discovery/map` (Explore V4, guest-safe)
- Surfaces: authenticated `/dashboard`, public `/`

## Flutter

- `HomeExploreMapCard` — lazy `flutter_map`, Explore V4, no auto-GPS, ~240px height, soft fallback.

## Behavior

- No automatic GPS prompt.
- Guests: Egypt-wide framing.
- Auth: city origin when available (`manual`), else nationwide.
- Soft failure keeps list + full-map CTA.
