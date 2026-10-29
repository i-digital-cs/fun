# Funnity — Geographic Data Audit (Explore V4)

**Branch:** `feature/funnity-explore-v4-map-first`  
**Baseline:** `main` @ `7f1e0bf` + uncommitted location-state repair preserved  
**Audit date:** 2026-10-28  
**Environment:** local/dev database (not production)

## Summary

| Metric | Count |
|--------|------:|
| Total events | 557 |
| Published | 153 |
| Events with lat+lng | 55 |
| **Published with coordinates** | **52** |
| **Published without coordinates** | **101** |
| Published city-only (no event pin) | 101 |
| Published with non-empty venue | 153 |
| `search_indexable = false` (all) | 500 |
| Published + `search_indexable = false` | 100 |
| Invalid lat/lng bounds | 0 |
| Exact `(0,0)` placeholders | 0 |
| Exact Cairo centroid `30.0444, 31.2357` | 2 |
| Published duplicate-coordinate groups | 1 (2 events) |

## Interpretation

- **Map-ready (exact pin):** 52 published events with event-level coordinates.
- **List/approximate only:** 101 published events have city association + venue text but **no event lat/lng**. These must not be plotted as exact venue pins. Map may optionally show them as **approximate** city-centroid markers when the client requests `include_approximate=1`, always labeled approximate.
- SEO `search_indexable` is independent of discovery visibility; map discovery follows the same published/visibility rules as Option B discover, not sitemap indexing.

## Rules for Explore V4

1. Never fabricate coordinates.
2. Event pin → `location_precision = exact`.
3. City centroid fallback → `location_precision = approximate` only when explicitly included.
4. Events without trustworthy coords remain discoverable in List View.

## Schema notes

- `events.latitude` / `events.longitude` nullable decimals (migration `2026_09_23_204000`).
- `events.city_id` required FK; every city-only event can resolve a city center.
- Cities table already has coordinates for Egyptian catalog cities.
