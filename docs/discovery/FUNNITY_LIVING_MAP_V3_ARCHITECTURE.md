# Funnity Living Map V3 — Architecture

**Status:** Prototype (preview only) · Production Home/Discover maps **unchanged**  
**Git baseline:** `7bbfee2` → Living Map V3 prototype commit  
**Reference:** `docs/Explore Around You Events Dashboard.png`

## Decision summary

| Topic | Choice | Rationale |
|---|---|---|
| Web engine | **MapLibre GL JS 4.x** (CDN on preview only) | Pitch/bearing, custom vector styles, fog/depth, smooth camera — not achievable with Leaflet raster alone |
| Keep Leaflet | **Yes for production** until owner approval | Avoids shipping unfinished cinematic UX to `/`, `/dashboard`, Discover |
| Flutter (Phase E) | Prefer enhanced `flutter_map` + custom overlays first; evaluate `maplibre` Flutter plugin after Web sign-off | Engine parity ≠ pixel parity; avoid unstable plugin risk before visual approval |
| Geographic SSOT | Existing `GET /api/discovery/map` (Explore V4) | Exact markers vs city aggregates; no fabricated coordinates |
| Preview isolation | `/preview/living-map-v3` | Auth, signed URL, or `LIVING_MAP_PREVIEW_TOKEN`; `noindex`; not linked from nav |

## Engine evaluation

### Leaflet (current production)

- Strengths: already shipped, small, OSM raster familiar.
- Gaps vs reference: no pitch/bearing, weak custom basemap, limited cinematic camera, default pin language.

### MapLibre GL JS (prototype)

- Strengths: vector styles, pitch, bearing, fog, GeoJSON arcs, performant GPU path.
- Costs: MapLibre itself is free (BSD). **Tiles are separate.**
- Flutter: not 1:1; plan custom markers on `flutter_map` for parity of *identity*, not identical GL.

### Other

- Self-hosted Protomaps / OpenMapTiles: best long-term cost control if traffic grows.
- Paid Mapbox/Google: **rejected without owner approval.**

## Tile infrastructure

| Option | License / cost | Prototype use | Production recommendation |
|---|---|---|---|
| OpenFreeMap `styles/liberty` | Free vector tiles + OSM/OpenMapTiles attribution | **Yes** | OK for early traffic; monitor fair-use |
| OSM public raster (`tile.openstreetmap.org`) | Usage policy restricts heavy production | No (policy) | Do not use as primary |
| Self-hosted Protomaps | Infra cost only | Later | Preferred at scale |
| Mapbox / paid | API keys + usage fees | No | Requires explicit owner approval |

**Attribution shown in UI:** OpenMapTiles · OpenStreetMap · OpenFreeMap.

## Rendering architecture (prototype)

```
Blade preview (dashboard chrome preserved)
  → MapLibre GL (CDN)
  → OpenFreeMap vector style
  → Explore V4 /api/discovery/map
  → HTML markers (category color + emoji)
  → City aggregate badges
  → Floating preview card
  → Optional cinematic camera (exact venues only)
```

## Layout preservation

Preview embeds inside existing `layouts/dashboard` (auth) or `layouts.app` (signed guest).  
Sidebar / topbar / RTL direction remain Laravel layout concerns — map does not go full-viewport-home.

## Security

- No private coordinates beyond existing discovery rules.
- Preview `noindex,nofollow`.
- Production map JS paths (`home-mini-map.js`, `discover-map.js`) untouched by this prototype entry.
- Vite entry `living-map-v3.js` loaded **only** from the preview Blade `@vite` call.

## Next gate

Owner reviews prototype screenshots + signed preview URL → approve → Phase E production Home / Discover / Flutter rollout.
