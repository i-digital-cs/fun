# Funnity — Location Discovery V4

**Branch:** `fix/funnity-performance-location-discovery`  
**Baseline:** `4bd980e` (V3 Social Home + Flutter stab)  
**Option B:** preserved (`EventDiscoveryService` home bundle + Worth the Trip)

## 1. Location architecture

Single Laravel origin resolution (no parallel JS/Dart distance math):

1. Explicit `city_id` (including empty = nationwide) **wins**
2. Else ephemeral `latitude` + `longitude` (device) if valid bounds
3. Else authenticated user’s saved `city_id`
4. Else nationwide (no geo filter)

Shared by:

- `DiscoverNearbyEventsService::resolveOrigin`
- `EventDiscoveryService::resolveOrigin`
- Request: `DiscoverEventsRequest` (web + API)

City search (no third-party geocoder):

- `App\Services\Location\SearchCitiesService`
- `GET /api/cities/search?q=&limit=`
- Aliases in `config/location.search_aliases`

## 2. Manual / device state behavior

| Field | Manual | Device |
|-------|--------|--------|
| Mode | `manual` | `device` |
| Center | Selected city lat/lng | One-shot GPS |
| Persisted | city id, name, radius, mode | mode + radius only (coords never stored) |
| Override | GPS must not replace selection | Manual pick clears device fix |

On GPS failure: show error + offer manual; **do not** silently switch modes or label a city as “detected”.

## 3. Flutter implementation

- `DiscoveryLocationService` — SharedPreferences + Geolocator one-shot
- `DiscoveryLocationController` — authoritative Riverpod state + `toDiscoverFilters()`
- Discover chip → `showLocationPickerSheet` (search + radius chips + Use device + Nationwide)
- Home nearby uses discovery prefs; device failure → nationwide + error string (no silent city swap)

## 4. Web implementation

- Discover location chip (opens location modal)
- Searchable city filter via `/api/cities/search`
- Explicit “Use device location” → query `latitude`/`longitude` (secure context)
- Location modal: searchable picker + save city/radius to settings; device button opens Discover with ephemeral coords
- While device origin active, `city_id` input is disabled so empty `city_id=` cannot override GPS

## 5. API changes

| Endpoint | Change |
|----------|--------|
| `GET /api/cities/search` | New — EN/AR + aliases, returns id/name/lat/lng/subtitle/source |
| `GET /api/events` / discover home | Existing lat/lng + city_id precedence clarified |

No new location tables. No paid geocoder.

## 6. Database migrations

None.

## 7. Tests

- `tests/Feature/CitySearchApiTest` — EN alias, AR alias, empty query
- `tests/Feature/DiscoverNearbyEventsTest` — device origin, city wins over GPS, invalid coords
- `mobile/test/features/discover/discovery_location_state_test.dart` — filter mapping / labels

## 8. Remaining limitations

- City catalog is the searchable corpus (districts must exist as cities or aliases)
- Reverse-geocoded “place name” for device mode is not claimed — UI shows “Device · ±Nm”
- DEVICE PASS for incorrect-GPS owner reports still needs hardware verification of permission + accuracy UI
- Profile city settings page still uses classic select; Discover/modal use searchable V4 UX

## 9. Final verdict — location discovery

| Gate | Verdict |
|------|---------|
| **CODE PASS** | PASS |
| **BROWSER PASS** | CONDITIONAL (searchable UX + GPS wiring landed; full interactive QA pending) |
| **DEVICE PASS** | NOT PASS without hardware GPS/permission run |

**Final location discovery verdict:** CODE PASS; manual searchable centers + explicit device origin share one Laravel discovery path.
