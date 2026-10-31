# Discover Completion QA — Map/List SSOT & Web UX Parity

**Date:** 2026-10-31  
**Repo:** `C:\xampp\htdocs\Funity\fun`  
**Flutter:** `mobile/` (`com.funnity.app`)  
**Branch:** `main`  
**Emulator:** `bidex_api34` → `emulator-5554`  
**API:** `https://funnity.com/api`  
**Build proof:** `discover_completion_20261031`

---

## 1. Git baseline

| Item | Value |
|------|-------|
| Branch | `main` |
| HEAD / `origin/main` | `350c7ba` — *Add WEB ↔ FLUTTER UI PARITY AUDIT…* |
| Working tree | **Dirty** — prior Public Profile repair + this Discover completion (uncommitted; not pushed) |
| Reset/clean | **Not performed** |
| AAB / versionCode / signing | **Unchanged** |

---

## 2. Previous fixes preserved

| Area | Status |
|------|--------|
| Public profile `UserPublicId` + safe errors | Preserved (`user_public_id.dart`, `error_mapper.dart`, profile remote/UI) |
| Discover header / Egypt `initialCameraFit` | Preserved and extended |
| Prior reports | Still valid references; Discover New Cairo gap closed in this pass |

---

## 3. Active web source files (UX reference)

| Role | Path |
|------|------|
| Discover map JS | `resources/js/discover-map.js` |
| Discovery location JS | `resources/js/discovery-location.js` |
| Discover card | `resources/views/components/events/discover-card.blade.php` |
| Map service | `app/Services/Events/DiscoverMapService.php` |
| City events API | `app/Http/Controllers/Api/Discovery/DiscoverCityEventsController.php` |
| Map API | `app/Http/Controllers/Api/Discovery/DiscoverMapController.php` |

---

## 4. Flutter source files (primary)

| Role | Path |
|------|------|
| Discover page (Map/List chrome) | `mobile/lib/features/discover/presentation/discover_page.dart` |
| Shared query SSOT | `…/discovery_query_controller.dart` |
| Location / GPS / city | `…/discovery_location_controller.dart` |
| List results | `…/discover_controller.dart` |
| Map results | `…/discover_map_controller.dart` |
| Map UI | `…/widgets/discover_map_view.dart` |
| Location picker | `…/widgets/location_picker_sheet.dart` |
| Geo envelope | `…/domain/discovery_geo.dart` |
| Models / remote | `…/domain/map_discovery.dart`, `…/data/discover_map_remote.dart` |

---

## 5. Map/List root cause

**Two competing sources of truth:**

1. **List** used `discoverControllerProvider` ← `eventsRepository.discover(DiscoverFilters)` with city/GPS from `discoveryLocationController`.
2. **Map** used `discoverMapControllerProvider` ← `GET /discovery/map` with `q`/`category`/`when` passed only as widget props from draft fields that lived mainly on the **List** filter panel.
3. **Manual city** (`selectManualCity`) cleared lat/lng, so Map never became `origin_aware`. Camera stayed on Egypt nationwide; markers/aggregates included distant cities (e.g. Mansoura) while the chip said `New Cairo · Within 100 km`.

**Fix:** Shared `discoveryQueryControllerProvider` for `q` / category / when; search field on both Map and List; city selection persists centroid coords (New Cairo `30.03, 31.47`) so Map and List share origin-aware radius semantics.

---

## 6. Missing event images root cause

API `GET /discovery/city-events` already returns `image_url`, `starts_at`, `category_name`, `members_count`.

Flutter `MapCityEvent` and `_CityEventsSheet` **omitted** those fields and rendered text-only `ListTile`s.

**Fix:** Parse full payload; show thumbnails via `AppNetworkImage` with placeholder when missing (same for exact marker preview).

---

## 7. Navigation root cause

- Event open already used `context.push('/events/{id}')` (back returns to Discover).
- Sheet selection was wiped aggressively / weak city-events race handling.
- Map did not refresh when filters changed on List.

**Fix:** Query listen refreshes map; city-events uses request-id gating; push navigation unchanged; return-to-origin control added.

---

## 8. Location inconsistency root cause

Nationwide map aggregates (Mansoura, etc.) were shown under a New Cairo chip because Map search lacked origin. With origin-aware New Cairo @ 100 km, API returns nearby cities (Zagazig, Giza, Cairo, New Cairo, Obour, 6th of October, Sheikh Zayed) — **not** Mansoura.

Tapping a **city aggregate** still opens “Events in {city}” for that aggregate — intentional drill-down, labeled city-level (not “in-radius match” of the filter chip). Progressive suggestion banners use `meta.is_suggestion`.

---

## 9. Out-of-region GPS behavior

Emulator GPS (Mountain View) is outside Egypt. Prior UX: camera jumped to California → `Bounds are outside the supported discovery region.` with Retry-only.

**Fix:**

- `DiscoveryGeo.contains` / `outsideDiscoveryRegion` flag.
- Device outside Egypt → clear message + **Choose city** / **Explore Egypt**; list filters fall back to nationwide; map stays on Egypt envelope.
- Client rejects viewports that do not intersect Egypt before calling the API.
- Manual Egyptian city overrides prior GPS.

---

## 10. Exact fixes (summary)

1. `DiscoveryQueryController` SSOT for search filters (Map + List).
2. City picker / apply pass city lat/lng → origin-aware map.
3. Shared search field above Map/List.
4. Out-of-region GPS recovery UI + geo gate.
5. Rich map event cards with images + dates.
6. Map suggestion banner from API meta.
7. Return-to-selected-origin control; safer error codes.
8. `discoverController.applyFilters` preserves `discoveryMode`.
9. EN/AR l10n for discovery recovery / suggestions.
10. Tests in `test/features/discover/discovery_ssot_test.dart`.

---

## 11. API evidence

| Call | Result |
|------|--------|
| `GET /cities/search?q=New%20Cairo` | id **3**, lat **30.03**, lng **31.47** |
| Nationwide map `zoom=6` Egypt bounds | Includes Mansoura aggregate among others; `origin_aware: false` |
| Origin-aware New Cairo `distance_km=100` | `origin_aware: true`; cities within radius; **Mansoura absent** |
| `GET /discovery/city-events?city_id=3` | Events include `image_url` (e.g. football-640.webp) |

---

## 12. Automated test results

```text
flutter test test/features/discover test/features/events/discover_filters_test.dart
→ 25 tests passed
```

Includes geo, query SSOT, out-of-region filters, city event image parsing, bounds clamp.

`flutter analyze lib/features/discover` → infos only (null-aware style); no errors.

Full-suite / golden: not overwritten; any pre-existing golden failure remains separate.

---

## 13. Emulator test matrix

Device: `emulator-5554` (bidex_api34). Build: `FUNNITY_BUILD_PROOF=discover_completion_20261031`.

| Flow | Status | Notes |
|------|--------|-------|
| 1 New Cairo → Map → Search → Results → Event → Back | **PENDING runtime** | Exercised after install; see §14 |
| 2 New Cairo → List → Search → Map | **PENDING runtime** | Shared query SSOT in code |
| 3 All Egypt → Map → Marker → list → details → Back | **PENDING runtime** | |
| 4 Pan → Search this area → Return to city | **PENDING runtime** | |
| 5 GPS outside Egypt → Choose City → New Cairo | **PENDING runtime** | Code path covered by unit tests |
| 6 Arabic Discover → Map → Event → Back | **UNVERIFIED** | Needs locale switch on device |

Flows marked PENDING are completed in the same session once the app finishes installing; update checkboxes in §14 screenshots.

---

## 14. Screenshot evidence

Directory: `docs/flutter/parity_evidence/`

| File | Subject |
|------|---------|
| `08_discover_new_cairo_map.png` | New Cairo map (to capture) |
| `09_discover_city_events_thumbs.png` | City sheet with thumbs (to capture) |
| `10_discover_out_of_region.png` | Out-of-region banner (to capture) |
| Prior `03`–`07_*` | Earlier Discover / profile evidence |

---

## 15. Remaining issues

| Issue | Severity |
|-------|----------|
| OSM tile latency on emulator (gray map briefly) | Low |
| Some SnackBars still English `failure.message` defaults | Low |
| Progressive radius may still surface nearby *other* cities inside 100 km (expected geography) | Info |
| Arabic flow not yet device-verified | Medium |
| Full `flutter test` golden suite not re-baselined | Known |

---

## 16. Backend handoff

**None required** for these defects. Contracts already return images and origin-aware map meta. Optional later: ensure notification deep links never emit non-`Funity-F` public ids (prior profile task).

---

## 17. Files changed (this completion)

- `mobile/lib/features/discover/domain/discovery_geo.dart` **(new)**
- `mobile/lib/features/discover/presentation/discovery_query_controller.dart` **(new)**
- `mobile/lib/features/discover/presentation/discovery_location_controller.dart`
- `mobile/lib/features/discover/presentation/discover_controller.dart`
- `mobile/lib/features/discover/presentation/discover_map_controller.dart`
- `mobile/lib/features/discover/presentation/discover_page.dart`
- `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart`
- `mobile/lib/features/discover/presentation/widgets/location_picker_sheet.dart`
- `mobile/lib/features/discover/domain/map_discovery.dart`
- `mobile/lib/features/discover/data/discover_map_remote.dart`
- `mobile/lib/l10n/app_en.arb` / `app_ar.arb` (+ generated)
- `mobile/test/features/discover/discovery_ssot_test.dart` **(new)**
- `docs/flutter/DISCOVER_COMPLETION_QA.md` **(this file)**

Public Profile files from the prior task remain in the working tree and were not reverted.

---

## 18. Final verdict

**CONDITIONAL** until emulator flows 1–5 are screenshot-confirmed in §14.

Criteria for upgrading to **PASS**:

- New Cairo camera centers near 30.03/31.47 (not Africa / California).
- Map search uses same `q` as List.
- City event sheet shows thumbnails when API provides `image_url`.
- Out-of-region GPS offers Choose city / Explore Egypt (not Laravel bounds text).
- Back from event details preserves Discover filters.

**BLOCKED** only if install fails or Discover cannot load any events after origin-aware fix.
