# Funnity Explore V4 — Map-First Architecture

**Branch:** `feature/funnity-explore-v4-map-first`  
**Preserves:** Location Discovery V4 + location-state repair (dirty tree carried forward)

## Provider selection (no paid keys)

| Layer | Choice | Rationale |
|-------|--------|-----------|
| Flutter map | `flutter_map` + OSM raster tiles | Already in app; attribution + User-Agent |
| Web map | Leaflet (lazy CDN) + OSM raster | Matches existing event place picker; MapLibre/OpenFreeMap recommended upgrade when vector styles are prioritized |
| Geocoding | Cities table + Photon (`SuggestPlacesService`) + Nominatim fallback | Existing production path; debounce + throttle; no API keys |
| Clustering | Server-side grid clustering in `DiscoverMapService` | Compact payloads; zoom-dependent |

**Not used for production unlimited OSM tile scraping alternatives without evaluation.** OpenFreeMap (MapLibre vector) is free with attribution and is the preferred next step for Web vector tiles — not blocking this release.

## Single origin

`ResolveDiscoveryOriginService`:

| Mode | Origin |
|------|--------|
| `manual` + lat/lng | `origin_type=place` (no cities-table requirement) |
| `manual` + city_id only | `origin_type=city` |
| `device` + lat/lng | `origin_type=device` |
| `nationwide` | null |

## Map API

`GET /api/discovery/map`

Query: `north,south,east,west,zoom,category_id?,q?,when?,include_approximate?,limit?`

Response:

- `markers[]` — exact (or opt-in approximate) event pins, compact fields
- `clusters[]` — grid clusters with `count` + `expansion_zoom`
- `meta` — zoom, clustered, bounds, point_count

Rules:

- Exact pins = event lat/lng only
- Approximate city centroids only when `include_approximate=1`
- BBox size capped; Egypt-region guard
- Max 250 markers

## Place search

`GET /api/places/search?q=` — cities first, then Photon places (throttled).

## Clients

- Flutter: Discover defaults to Map; List toggle preserved; Search this area; marker preview sheet
- Web: split desktop Map+List; stacked mobile; Search this area; lazy Leaflet

## Docs

- Geographic audit: `FUNNITY_GEOGRAPHIC_DATA_AUDIT.md`
- Implementation: `FUNNITY_EXPLORE_MAP_FIRST_IMPLEMENTATION.md`
- Performance: `docs/performance/FUNNITY_MAP_PERFORMANCE_REPORT.md`
