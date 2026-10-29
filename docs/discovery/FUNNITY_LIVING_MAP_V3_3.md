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

## Home activation QA (2026-10-29)

Evidence: `docs/discovery/v3_3_home_qa/` + `smoke-auth.json`.

| Surface | Evidence | Result |
|---|---|---|
| Public `/` EN/AR desktop+mobile | `01`–`04` | Living Map `mode=public`, nationwide, covers, no Leaflet |
| Auth `/dashboard?distance_km=5` Port Said | `05`, `09` | `discovery_mode=manual`, lat/lng Port Said, focus `5 km`, radius ring |
| City expand approx labels | `06` | “Approximate city location” / Arabic equivalent |
| Auth `/` EN/AR | `07`–`08` | `mode=home`, RTL chrome, covers |
| Discover guest `/events` | curl | `data-discover-map` present; no Living Map in events Blade |
| API | smoke | discovery map **200**, **0× 422** |
| Dual init | smoke | 1× `[data-living-map]`, 0× Leaflet mini map |
| GPS | smoke | no geolocation permission / no auto prompt |

### Rollback

```bash
# in .env
LIVING_MAP_REPLACE_HOME=false
php artisan config:clear
```

Hard-refresh `/` and `/dashboard` → Leaflet mini map returns.
