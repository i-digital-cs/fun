# Home Mini Map — Explore Around You

## Web

- Component: `resources/views/components/dashboard/home-mini-map.blade.php`
- JS: `resources/js/home-mini-map.js` (lazy IntersectionObserver + Leaflet CDN)
- API: `GET /api/discovery/map` (Explore V4)
- Inserted on authenticated `/dashboard` above Nearby

## Behavior

- Uses saved city coordinates when available (`manual` origin); otherwise nationwide Egypt framing.
- Does **not** auto-prompt for GPS.
- Map failure is soft (status text only).
- Suggested event strip reuses nearby event cards already loaded for Home.

## Flutter

Pending: compact `flutter_map` strip on `home_page.dart` (~220–260px) calling the same discovery map API. Documented in Chat V2 Flutter handoff.
