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
| Discover gate | `living_map.replace_discover_map` ← `LIVING_MAP_REPLACE_DISCOVER` (independent) |
| Home swap | `resources/views/components/dashboard/home-mini-map.blade.php` |
| Discover swap | `resources/views/events/index.blade.php` (`data-discover-living-map-host`) |

### Modes

| Mode | Used on | Behavior |
|---|---|---|
| `public` | Guest `/` | Stage + nationwide Explore V4, no GPS prompt |
| `home` | Auth `/` + `/dashboard` | Stage; passes selected city lat/lng + `distance_km` + discovery mode |
| `preview` | `/preview/living-map-v3` | Full cinematic HUD + banner |
| `discover` | `GET /events` when Discover gate on | Map-only stage (Discover `#discover-results` list SSOT); Search This Area; filters via Explore V4 |

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

**Home**

1. Set `LIVING_MAP_REPLACE_HOME=false` in `.env`
2. `php artisan config:clear` (or `config:cache` rebuild)
3. Hard-refresh Home — Leaflet mini map returns (`home-mini-map.js` unchanged)

**Discover**

1. Set `LIVING_MAP_REPLACE_DISCOVER=false` in `.env`
2. `php artisan config:clear`
3. Hard-refresh `/events` — Leaflet Discover map returns (`discover-map.js` via `[data-discover-map]`)

Flags are independent: Home can stay Living Map while Discover rolls back (or the reverse).

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

---

## Discover cutover (2026-10-29)

Independent flag `LIVING_MAP_REPLACE_DISCOVER` mounts `x-living-map.stage` (`mode=discover`) on `GET /events`:

- Map-only Living Map (Discover `#discover-results` remains the list SSOT; Map/List toggle unchanged).
- Explore V4 `/api/discovery/map` with Discover toolbar filters (`q`, `category_id`).
- **Search This Area** = viewport-only fetch (`viewportOnly`); does **not** rewrite saved origin / profile location.
- Programmatic camera moves suppress the Search This Area chip until the user pans/zooms.
- Card ↔ pin sync via `[data-discover-result-card]` / `data-event-id`.
- Leaflet `discover-map.js` skips boot when `[data-discover-living-map-host]` is present.

Evidence: `docs/discovery/v3_3_discover_qa/`.

### Rollback

```bash
LIVING_MAP_REPLACE_DISCOVER=false
php artisan config:clear
```

Hard-refresh `/events` → Leaflet Discover map returns.

### Auth `/events` redirect note

Guest `/events` returns **200**. Incomplete profiles (e.g. `sex=null` seed users such as `rania.saleh33@example.com`) can bounce on **other** protected routes / post-login gates — **not** caused by the Living Map Discover cutover.
