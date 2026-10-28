# Funnity — Location State Consistency Repair

**Branch:** `fix/funnity-performance-location-discovery`  
**Date:** 2026-10-28

## Confirmed root cause

The Discover chip reflected **active discovery query state** (e.g. device GPS + `distance_km=0` → “Device location · All distances”), while the location modal always hydrated from **profile preferences** (`users.city_id` = Port Said, `users.distance_km` = 5).

Those are different layers:

| Layer | Before | After |
|-------|--------|-------|
| Profile settings | Port Said / 5 km | Unchanged until user Saves manual city |
| Active discovery | Device coords / All distances | Single session + `discovery_mode` |
| Modal UI | Always profile | Hydrates from active discovery state |
| Chip / topbar | Mixed | Same discovery state labels |

GPS coordinates were not proven wrong. The UI lied by showing Port Said as “Selected” during an active device-mode session.

Secondary issues fixed:

- Stale `city_id` could still ride along with device URLs; backend city-precedence then overrode GPS.
- Device detect did not clear incompatible city params.
- Radius in the modal did not track the Discover radius.
- Cancel closed without restoring a snapshot of the opened state.

## State flow

### Before

```
Profile city (Port Said) ──► Modal “Selected”
Discover ?latitude&longitude&distance_km=0 ──► Chip “Device · All distances”
Backend: city_id key wins over lat/lng if both present
```

### After

```
sessionStorage funnity_discovery_v4  ◄── synced from server boot JSON
        │
        ├── mode=manual  → city_id + distance_km only
        ├── mode=device  → lat/lng (+ accuracy_m) + distance_km only
        └── mode=nationwide → city_id= + no coords
        │
        ▼
Discover request includes discovery_mode=
        │
        ▼
ResolveDiscoveryOriginService (single Laravel origin)
```

## API request examples

Manual:

```
GET /events?discovery_mode=manual&city_id=6&distance_km=25
GET /api/events?discovery_mode=manual&city_id=6&distance_km=25
```

Device:

```
GET /events?discovery_mode=device&latitude=…&longitude=…&distance_km=25&accuracy_m=120
```

(Coordinates omitted from this doc intentionally.)

Nationwide:

```
GET /events?discovery_mode=nationwide&city_id=&distance_km=0
```

Mode exclusivity is enforced in `DiscoverEventsRequest::filters()` — device mode strips `city_id`; manual mode strips lat/lng.

## Exact files changed

### Backend
- `app/Services/Location/ResolveDiscoveryOriginService.php` (new SSoT)
- `app/Services/Events/DiscoverNearbyEventsService.php`
- `app/Services/Events/EventDiscoveryService.php`
- `app/Http/Requests/Events/DiscoverEventsRequest.php`
- `app/Http/Controllers/Web/Events/EventController.php`
- `app/Http/Controllers/Web/Profile/LocationPreferenceController.php`
- `app/Http/Requests/Profile/UpdateLocationPreferenceRequest.php`
- `config/location.php` (التجمع الخامس → New Cairo alias)

### Web
- `resources/js/discovery-location.js` (new)
- `resources/js/app.js`
- `resources/views/components/location-modal.blade.php`
- `resources/views/events/index.blade.php`
- `resources/views/components/dashboard/topbar.blade.php`
- `lang/en/location.php`, `lang/ar/location.php`

### Flutter
- `mobile/lib/core/location/discovery_location_service.dart` (`nationwide` mode, high-accuracy one-shot)
- `mobile/lib/features/discover/presentation/discovery_location_controller.dart`
- `mobile/lib/features/events/domain/events_repository.dart` (`discovery_mode` query)
- `mobile/lib/features/home/presentation/home_nearby_controller.dart`
- `mobile/lib/features/discover/presentation/discover_page.dart`
- `mobile/lib/features/discover/presentation/widgets/location_picker_sheet.dart`

### Tests / docs
- `tests/Unit/ResolveDiscoveryOriginServiceTest.php`
- `tests/Feature/CitySearchAliasCoverageTest.php`
- `tests/Feature/DiscoverNearbyEventsTest.php` (mode exclusivity cases)
- `mobile/test/features/discover/discovery_location_state_test.dart`
- this file

## Chip / modal labels

| Mode | Chip |
|------|------|
| Manual | `{City} · Within {n} km` |
| Device | `Current location (±Nm) · Within {n} km` |
| Nationwide | `All Egypt · All distances` (or chosen radius) |

Opening the modal snapshots state; Cancel restores the snapshot; Save/Detect/Nationwide writes exclusive params and navigates Discover.

## Location search dataset support

Active cities include 6th of October, Sheikh Zayed, New Cairo, Port Said, Cairo, Giza, etc.

| Query | Supported? |
|-------|------------|
| `6 October` | Yes → 6th of October |
| `6th of October` | Yes |
| `٦ أكتوبر` | Yes (alias) |
| `الشيخ زايد` | Yes |
| `New Cairo` | Yes |
| `التجمع الخامس` | Yes via alias → **New Cairo** (no separate “5th Settlement” city row) |

## Tests

- PHPUnit: ResolveDiscoveryOrigin + DiscoverNearby mode exclusivity + city search aliases — PASS
- Flutter unit: discovery filter query exclusivity / labels — PASS
- `flutter analyze` on touched paths — clean
- Vite production build includes `discovery-location.js` — PASS

## Browser verification

CODE + Vite build PASS. Interactive browser GPS permission / accuracy UI still needs owner secure-context check (desktop network location is often approximate >500 m and now surfaces an explicit warning).

## Flutter verification

CODE PASS (unit + analyze). DEVICE PASS still requires hardware GPS run.

## Remaining limitations

- Profile `/settings/location` classic form still edits saved city; Discover session state is authoritative for discovery until Save manual from the modal.
- `التجمع الخامس` maps to New Cairo centroid — not a distinct district record.
- Desktop browsers frequently return coarse network positions; UI warns when accuracy > 500 m.
- Precise coordinates are kept in sessionStorage / query for the active device session; they are not written to production logs by this change.

## Verdict

| Gate | Result |
|------|--------|
| CODE PASS | PASS |
| BROWSER PASS | CONDITIONAL (needs owner interactive confirm) |
| DEVICE PASS | NOT PASS without hardware |
