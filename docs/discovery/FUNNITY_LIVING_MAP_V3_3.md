# Living Map V3.3 — Reusable component + Home/Dashboard activation

## Owner direction

Approved visual design. Activate the reusable Living Map on **public `/`** and **authenticated `/dashboard`** (and authenticated `/` which shares the Home mini-map component). Keep Discover and Flutter unchanged. Preserve Funnity shell/layout; geographic SSOT via Explore V4.

## Architecture

| Piece | Path |
|---|---|
| Blade component | `resources/views/components/living-map/stage.blade.php` |
| Factory | `resources/js/living-map/create-living-map.js` |
| Style | `resources/js/living-map/funnity-style.js` |
| I18n helper | `resources/js/living-map/i18n.js` |
| Vite entry | `resources/js/living-map-v3.js` |
| Strings | `lang/{en,ar}/living_map.php` + dashboard explore chrome |
| Home gate | `living_map.replace_home_mini_map` ← `LIVING_MAP_REPLACE_HOME` |
| Swap point | `resources/views/components/dashboard/home-mini-map.blade.php` |

### Modes

| Mode | Used on | Behavior |
|---|---|---|
| `public` | Guest `/` | Compact stage, nationwide Explore V4, no GPS prompt |
| `home` | Auth `/` + `/dashboard` | Compact stage; passes selected city lat/lng + `distance_km` + discovery mode |
| `preview` | `/preview/living-map-v3` | Full cinematic HUD + banner |
| `discover` | reserved | **Not activated** — Discover stays Leaflet |

## Location SSOT

Home controllers already compute `miniMapLat` / `miniMapLng` / `miniMapMode` / radius. Living Map stage receives those as `data-lat`, `data-lng`, `data-distance-km`, `data-discovery-mode`.

`createLivingMap()` mirrors Leaflet mini-map fetch rules:

- Origin present + mode ≠ nationwide → `discovery_mode=manual|device` + lat/lng + `distance_km`
- Otherwise → `discovery_mode=nationwide`
- City-level events remain approximate (`include_approximate=1`); no fabricated coordinates

## Layout

Desktop: logical CSS map start / list end (EN left map; AR right map).  
Mobile: map above list; `overflow-x: clip` on shell.

## Production safety / rollback

1. Set `LIVING_MAP_REPLACE_HOME=false` in `.env`
2. `php artisan config:clear` (or `config:cache` rebuild)
3. Hard-refresh Home — Leaflet mini map returns (asset `home-mini-map.js` unchanged)

Discover is **not** gated by this flag.

## Flutter

Parity §10.26 — web Home activated; Flutter Home map still pending soak + port.

---

## Integration repair (2026-10-29)

Preview is the visual SSOT. Home/Dashboard no longer use a “compact” feature fork:

- Same stage height as preview (`min(58vh, 520px)`), floating event card, tour HUD.
- Radius selector (`config('location.distances_km')`) on embedded surfaces; persists via `/settings/location`.
- Origin/radius from user city + `distance_km` (requested, not expanded); listens to `funnity:discovery-location`.
- Port Said in earlier QA was the **demo user’s saved city** + optional `?distance_km=5` — not a hardcoded default.

Evidence: `docs/discovery/v3_3_repair_qa/`.

### Rollback

```bash
# in .env
LIVING_MAP_REPLACE_HOME=false
php artisan config:clear
```

Hard-refresh `/` and `/dashboard` → Leaflet mini map returns.
