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

Device: `emulator-5554` (bidex_api34).  
Builds: `discover_completion_20261031` → `discover_completion_20261031_b` (catalog lat/lng + `_apply` wipe fix).

| Flow | Status | Evidence |
|------|--------|----------|
| 1 New Cairo → Map results (no Mansoura nationwide dump) | **VERIFIED** | Chip `New Cairo · All distances`; aggregates New Cairo / Obour / 6th of October / Sheikh Zayed; **Mansoura=false**; `08_*.png`, `ui_e5.xml` |
| 2 New Cairo → List preserves city | **VERIFIED** | List filter `City · New Cairo`; `11_*.png`, `ui_e7.xml` |
| 3 All Egypt → Map markers | **VERIFIED** | Nationwide aggregates including Mansoura when chip is All Egypt (`ui_e2.xml`) |
| 4 Return to selected city control | **VERIFIED** | `Return to selected city` present after New Cairo (`ui_e5.xml`) |
| 5 Out-of-region GPS recovery | **PARTIAL** | Unit tests + code path; device “Use device location” not fully screenshot-gated this pass |
| 6 Arabic Discover | **UNVERIFIED** | Locale not switched on emulator |
| City sheet thumbnails | **PARTIAL** | API returns `image_url`; Flutter parses + UI built; uiautomator tap on aggregate flaky (zoom without sheet) |
| Event details → Back | **UNVERIFIED** | Needs reliable sheet/marker open under automation |

Additional fix verified mid-session: catalog `_parseCatalog` omitted city lat/lng; `_apply` re-selected city and wiped picker centroids → origin-aware lost. Fixed in `catalog_remote_source.dart` + `discover_page.dart`.

---

## 14. Screenshot / dump evidence

Directory: `docs/flutter/parity_evidence/`

| File | Subject |
|------|---------|
| `08_discover_new_cairo_map.png` | New Cairo origin-aware map |
| `09_discover_city_events_thumbs.png` | Attempted city sheet capture |
| `11_discover_list_new_cairo.png` | List with New Cairo filter |
| `ui_e2.xml` … `ui_e9.xml` | Runtime UI dumps |
| Prior `03`–`07_*` | Earlier Discover / profile evidence |

---

## 15. Remaining issues

| Issue | Severity |
|-------|----------|
| City-aggregate → bottom sheet unreliable under uiautomator (manual OK expected) | Medium |
| Arabic Discover not device-verified | Medium |
| Out-of-region GPS screenshot not captured | Medium |
| OSM tile latency on emulator | Low |
| Some SnackBars still English defaults | Low |
| Full golden suite not re-baselined | Known |

---

## 16. Backend handoff

**None required.** Images + origin-aware map meta already on production API.

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
- `mobile/lib/features/catalog/data/catalog_remote_source.dart` (city lat/lng in catalog parse)
- `mobile/lib/l10n/app_en.arb` / `app_ar.arb` (+ generated)
- `mobile/test/features/discover/discovery_ssot_test.dart` **(new)**
- `docs/flutter/DISCOVER_COMPLETION_QA.md` **(this file)**

Public Profile files from the prior task remain in the working tree and were not reverted.

---

## 18. Final verdict

**CONDITIONAL**

**Why not PASS:** Arabic flow, reliable city-sheet thumbnail capture, event details→back, and out-of-region GPS screenshot remain incomplete under automation.

**Why not BLOCKED:** New Cairo origin-aware Discover works on emulator (Mansoura no longer appears under New Cairo chip); Map/List share city/query SSOT; API image contract wired; unit tests green; build `discover_completion_20261031_b` installed.
