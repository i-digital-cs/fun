# Funnity Explore V4 — Implementation Notes (FINAL UNIFIED)

## Git

- Feature branch: `feature/funnity-explore-v4-map-first`
- Baseline: `main` @ `7f1e0bf`
- Uncommitted work preserved (no reset/clean/commit)

## Backend

| Component | Path |
|---|---|
| Map discovery | `app/Services/Events/DiscoverMapService.php` |
| Map API | `app/Http/Controllers/Api/Discovery/DiscoverMapController.php` |
| Place search | `app/Http/Controllers/Api/Discovery/PlaceSearchController.php` |
| Reverse geocode | `app/Services/Location/ResolveLocationLabelService.php` |
| Reverse API | `app/Http/Controllers/Api/Discovery/ReverseGeocodeController.php` |
| Origin resolver | `app/Services/Location/ResolveDiscoveryOriginService.php` |

### Map payload

```
GET /api/discovery/map?north=&south=&east=&west=&zoom=
→ markers (exact venues)
→ clusters (exact-pin grid clusters at low zoom)
→ city_aggregates (city-only events at city centroid; location_precision=city_level)
→ meta.point_count / meta.city_aggregate_count
```

City-only events are **never** silently assigned as venue pins. Legacy `include_approximate=1` still emits per-event approximate markers for compatibility.

### Reverse geocode

```
GET /api/places/reverse?latitude=&longitude=
→ data.display_name, short_name, locality, district, governorate, country, provider, available
```

Provider: Nominatim (OSM). GPS accuracy is independent of reverse-geocode precision. On failure: coords preserved; `available=false`.

### Places

```
GET /api/places/search?q=
```

Cities table first, then Photon/Nominatim. No paid geocoder.

## Web

- Compact toolbar + dominant map (~66%) + compact results (~33%)
- `resources/views/events/index.blade.php`
- Compact cards: `resources/views/components/events/discover-card.blade.php`
- `resources/js/discovery-location.js` — reverse geocode, visible status, place search, Map/List
- `resources/js/discover-map.js` — user marker, city aggregates, card↔marker sync, Search this area

**Asset note:** Prefer isolated Vite build for verification when the checkout serves production (`public/build`). Source of truth is `resources/js/*`.

## Flutter

- Reverse geocode via `/places/reverse` after device detect
- Chip shows `Current location · {shortLabel}`
- City aggregate markers (orange rounded) distinct from exact pins
- User location marker + optional accuracy circle
- Map default + List toggle unchanged

## Geographic data (revalidated)

Active upcoming published events (dev DB at acceptance time):

- Exact coordinates: **1**
- City-only: **59**

Earlier audit (52 exact / 101 city-only) was a broader snapshot — counts change with content.

## Tests

- `DiscoverMapApiTest` — exact markers, bbox, clusters, city_aggregates, approximate opt-in
- `ReverseGeocodeApiTest` — success, validation, provider failure

## Remaining limitations

- Live GPS accuracy / Flutter Profile FPS: **not verified on physical device** this pass
- Friends-attending on map preview still deferred to event show SoT
- Nominatim/Photon rate limits apply — not unlimited production capacity
- Web asset rebuild required on the served environment before UI JS ships
