# Living Map V3 — Implementation

## Preview access

| Mode | How |
|---|---|
| Authenticated member | `/preview/living-map-v3` |
| Signed link | `php artisan tinker` → `URL::temporarySignedRoute('preview.living-map-v3', now()->addDays(7))` |
| Shared secret | `LIVING_MAP_PREVIEW_TOKEN` + `?preview_token=` |
| Local debug | `APP_DEBUG=true` |

## Files

| Path | Role |
|---|---|
| `config/living_map.php` | Style URL, attribution, preview token |
| `app/Http/Controllers/Web/Preview/LivingMapV3PreviewController.php` | Access gate |
| `resources/views/preview/living-map-v3.blade.php` | UI shell (dashboard layout) |
| `resources/js/living-map-v3.js` | MapLibre prototype logic |
| `routes/web.php` | Preview route |
| `vite.config.js` | Adds prototype entry (not imported by `app.js`) |

## Intentionally untouched (production)

- `resources/js/home-mini-map.js`
- `resources/js/discover-map.js`
- Home / Discover Blade map wiring

## Data rules

1. Load Explore V4 `/api/discovery/map` with `include_approximate=1`.
2. Exact markers → cinematic pins + optional tour.
3. Approximate markers → strip cards / city aggregates only (never fake venue pins).
4. Tour requires **≥ 2 exact** events; otherwise ambient map only.

## Flutter (Phase E — after approval)

1. Keep Home layout; restyle `HomeExploreMapCard` markers to match color system.
2. Camera showcase via `MapController.move` + `Ticker` (respect reduce-motion).
3. Re-evaluate MapLibre Flutter only if Web MapLibre is approved and plugin stability is verified.

## Rollback

Delete or unroute preview; production unaffected. Prototype Vite asset unused if Blade not served.
