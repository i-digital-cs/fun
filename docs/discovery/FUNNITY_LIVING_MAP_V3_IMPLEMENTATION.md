# Living Map V3.2 — Implementation

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
| `resources/views/preview/living-map-v3.blade.php` | Shell + early RTL plugin bootstrap |
| `resources/js/living-map/funnity-style.js` | Funnity MapLibre palette + `building-3d` |
| `resources/js/living-map-v3.js` | Event-first map, city panel, tours, RTL wait |
| `app/Services/Events/DiscoverMapService.php` | `cityEvents()` enriched with category/image/members |
| `routes/web.php` | Preview route only |
| `vite.config.js` | Prototype entry (not in `app.js`) |

## Arabic / MapLibre RTL

MapLibre **4.7** API: `setRTLTextPlugin(url, lazy)` → Promise.

Must call with **`lazy = false`** and wait until `getRTLTextPluginStatus() === 'loaded'` before relying on Arabic basemap labels. Passing a callback as the 2nd argument is treated as truthy lazy and leaves Arabic reversed.

## Event-first flow

1. Overview Explore V4 → featured framing.
2. Paint exact pins (primary) + compact city collections (tertiary).
3. Strip lists **events only**.
4. City collection → `GET /api/discovery/city-events` → in-map panel of real events → shared `selectEvent` state with strip/card.
5. Tour: exact venue hops, else city-events tour (camera on city, cycle real event cards).

## Intentionally untouched (production)

- `resources/js/home-mini-map.js`
- `resources/js/discover-map.js`
- Home / Discover Blade map wiring

## Flutter

Deferred until owner approves V3.2 visual direction. Tracked in `docs/FLUTTER_WEB_PARITY.md` §10 item 26. Additive `city-events` fields (`category_name`, `image_url`, `members_count`) are backward compatible.
