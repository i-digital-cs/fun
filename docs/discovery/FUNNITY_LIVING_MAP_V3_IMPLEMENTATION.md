# Living Map V3.1 — Implementation

## Preview access

| Mode | How |
|---|---|
| Authenticated member | `/preview/living-map-v3` |
| Signed link | `URL::temporarySignedRoute('preview.living-map-v3', now()->addDays(7))` |
| Shared secret | `LIVING_MAP_PREVIEW_TOKEN` + `?preview_token=` |
| Local debug | `APP_DEBUG=true` |

## Files

| Path | Role |
|---|---|
| `config/living_map.php` | Style URL, attribution, preview token |
| `app/Http/Controllers/Web/Preview/LivingMapV3PreviewController.php` | Access gate + `noindex` |
| `resources/views/preview/living-map-v3.blade.php` | Dashboard/marketing shell + atmosphere CSS |
| `resources/js/living-map/funnity-style.js` | Reusable Funnity MapLibre palette remap + `building-3d` |
| `resources/js/living-map-v3.js` | Framing, markers, cards, City/Exact tours, strip sync |
| `routes/web.php` | Preview route only |
| `vite.config.js` | Prototype entry (not imported by `app.js`) |

## Rendering architecture

1. Fetch OpenFreeMap Liberty JSON → `buildFunnityStyle()` remaps paints.
2. MapLibre init with cinematic pitch; fog when supported.
3. Overview Explore V4 query → `computeFeaturedFocus()` → ease camera.
4. Viewport refetch → exact pins + city aggregates (+ API city clusters).
5. Tour mode: exact (≥2) else City Discovery Tour (named aggregates only).

## Intentionally untouched (production)

- `resources/js/home-mini-map.js`
- `resources/js/discover-map.js`
- Home / Discover Blade map wiring

## Data rules

1. Explore V4 `/api/discovery/map` with `include_approximate=1`.
2. Exact markers → category pins + optional exact tour.
3. Approximate → strip / city aggregates only (never fake venue pins).
4. `city_cluster` shown as multi-city pill; not a City Tour stop.
5. Focus copy never claims user GPS unless authenticated home preference is used later.

## Flutter (after owner approval)

Tracked in `docs/FLUTTER_WEB_PARITY.md` — **not shipping** with V3.1 preview.

## Rollback

Unroute preview Blade; production unaffected. Prototype Vite asset unused if Blade not served.
