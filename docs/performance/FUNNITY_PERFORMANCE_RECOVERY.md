# Funnity — Performance Recovery

**Branch:** `fix/funnity-performance-location-discovery`  
**Baseline (approved V3 + Flutter stab):** `4bd980e`  
**Date:** 2026-10-28

## 1. Performance root causes (code evidence)

| Cause | Evidence | Fix |
|-------|----------|-----|
| Discover header scroll called `setState` → full scaffold rebuild every collapse toggle | `discover_page.dart` previously flipped `_headerVisible` bool in `setState` during scroll | `ValueNotifier` + `ValueListenableBuilder` around header only |
| Per-row `ref.watch(authControllerProvider)` inside `SliverList` itemBuilder | Rebuild storm when auth/provider ticks during scroll | Capture `isAuthed` once in `build`; pass into itemBuilder |
| Event cover images decoded near full resolution in list | `EventCard` / `AppNetworkImage` without tight decode caps | `logicalWidth`/`maxCacheWidth` thumb decode; global `imageCache` caps in `main.dart` (120 images / 80 MiB) |
| Home nearby sequential awaits blocked first paint | `home_nearby_controller.dart` awaited GPS then each API serially | Parallelize independent futures; short GPS timeout (5s) |
| Location / filter apply could thrash discovery | Draft filters vs location controller drift | Single `DiscoveryLocationController`; `_apply` syncs carefully without wiping device mode |

## 2. Evidence supporting each cause

- Static analysis of Discover scroll path + Riverpod watches (no fabricated FPS).
- Image decode paths inspected in `event_card.dart` and `main.dart`.
- Home load path inspected for sequential network/GPS.

**Limitation:** No physical device or emulator was available in this environment for DevTools timeline / memory sampling.

## 3. Files changed (performance-relevant)

- `mobile/lib/main.dart` — image cache limits
- `mobile/lib/features/events/presentation/widgets/event_card.dart` — list decode sizing
- `mobile/lib/features/discover/presentation/discover_page.dart` — ValueNotifier header, list auth hoist, location chip
- `mobile/lib/features/home/presentation/home_nearby_controller.dart` — parallel load; no silent GPS→city swap
- `mobile/lib/core/location/discovery_location_service.dart` — one-shot foreground GPS + timeout

## 4. Before / after measurements

| Metric | Before | After |
|--------|--------|-------|
| UI frame time | NOT MEASURED | NOT MEASURED |
| Raster frame time | NOT MEASURED | NOT MEASURED |
| Dropped frames | NOT MEASURED | NOT MEASURED |
| Memory usage | NOT MEASURED | NOT MEASURED |
| Image cache | NOT MEASURED (code: uncapped → 80 MiB / 120 entries) | NOT MEASURED |
| Network requests | NOT MEASURED (code: sequential home → parallel) | NOT MEASURED |
| Navigation latency | NOT MEASURED | NOT MEASURED |

## 5. Debug vs Profile procedure (owner)

1. Install **profile** APK (not `app-debug.apk`) on the same device.
2. Reproduce: Discover scroll, Social Home, open/close search, event details round-trip.
3. Capture DevTools Performance + Memory on that scenario.
4. Compare only profile↔profile (or release↔release). Never compare debug frame times to 16.7 ms targets.

## 6. APK paths

| Build | Path / status |
|-------|----------------|
| Profile APK | `mobile/build/app/outputs/flutter-apk/app-profile.apk` (93.0MB, built this branch) |
| Release APK | Not built here (signing / deploy safety) |

## 7. Acceptance verdicts

| Gate | Verdict | Notes |
|------|---------|-------|
| **CODE PASS** | PASS | Confirmed rebuild/image/home defects fixed in code |
| **BROWSER PASS** | CONDITIONAL | Web location UX landed; browser scroll perf not instrumented |
| **DEVICE PASS** | NOT PASS | Requires owner profile-mode measurements |

**Final performance verdict:** CODE PASS / DEVICE NOT PASS (metrics NOT MEASURED).
