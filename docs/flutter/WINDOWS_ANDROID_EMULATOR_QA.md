# Windows Android Emulator QA — Fresh Install Verification

**Date:** 2026-10-30  
**Repo:** `C:\xampp\htdocs\Funity\fun`  
**Flutter:** `C:\xampp\htdocs\Funity\fun\mobile`  
**Branch:** `main`  
**Application ID:** `com.funnity.app`  
**Emulator:** `bidex_api34` → device `emulator-5554` (1080×2400, Android 14)

---

## 1. Git commit and branch

| Item | Value |
|------|-------|
| Branch | `main` |
| `HEAD` | `350c7ba` — *Add WEB ↔ FLUTTER UI PARITY AUDIT documentation and implement Flutter UI fixes* |
| `origin/main` | `350c7ba` (even: `0` ahead / `0` behind) |
| Prior baseline mentioned | `2d88523` (superseded by `350c7ba`) |

## 2. Local uncommitted changes (during this QA)

At QA start: **clean** vs `origin/main` (parity already committed).

Introduced during this verification (not committed):

| Path | Purpose |
|------|---------|
| `mobile/lib/main.dart` | Debug-only `FUNNITY_BUILD_PROOF` print via `--dart-define=BUILD_PROOF` |
| `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart` | `errorTileCallback` logcat probe for gray-map diagnosis |
| `docs/flutter/parity_evidence/build_stamp_*` | Build stamp file |
| `docs/flutter/parity_evidence/03_*` … `05_*` | Fresh-install screenshots |
| `docs/flutter/WINDOWS_ANDROID_EMULATOR_QA.md` | This report |

## 3. Previous parity changes — present on disk?

**Yes.** Commit `350c7ba` contains:

- `mobile/lib/features/discover/presentation/discover_page.dart` (stacked header, `SafeArea(top: false)`, Map/List end-aligned, Create removed from header)
- `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart` (`initialCameraFit` Egypt)
- `mobile/lib/features/home/presentation/home_page.dart` (`SafeArea(top: false)`)
- `mobile/lib/features/home/presentation/widgets/home_explore_map_card.dart` (UA package name)
- `docs/flutter/WEB_FLUTTER_UI_PARITY_AUDIT.md` + `docs/flutter/parity_evidence/`

Verified against audit claims: title/subtitle full-width, Map/List below title, Create via bottom nav only, Egypt camera fit — **present in source**.

## 4. Flutter entrypoint

`mobile/lib/main.dart` → `FunApp` via `ProviderScope`.

## 5. Build configuration

| Setting | Value used |
|---------|------------|
| Mode | Debug (`flutter run`) |
| Device | `emulator-5554` |
| `--dart-define=FLAVOR` | `prod` |
| `--dart-define=API_BASE_URL` | `https://funnity.com/api` (default Env) |
| `--dart-define=BUILD_PROOF` | `verify_350c7ba_20261030_fresh` |
| Signing | Debug (local) |
| Package ID | unchanged `com.funnity.app` |
| versionCode / versionName | unchanged `3` / `1.0.2` (expected in debug) |

## 6. Emulator device ID

`emulator-5554` (product `sdk_gphone64_x86_64`)

## 7. Installed application

| Field | Before clean reinstall | After fresh `flutter run` |
|-------|------------------------|---------------------------|
| Package | `com.funnity.app` | `com.funnity.app` |
| versionName | `1.0.2` | `1.0.2` |
| versionCode | `3` | `3` |
| lastUpdateTime | `2026-10-30 00:11:57` | **`2026-10-30 00:28:27`** |
| codePath | `…P4F-oKqMgqlXhRta2Z_wrQ==` | **`…oXEMmcb209gTpOVHB2rWBQ==`** (new path) |

Version numbers alone do **not** prove freshness; **lastUpdateTime + new codePath + BUILD_PROOF log** do.

## 8–11. Fresh build / install / launch evidence

```text
√ Built build\app\outputs\flutter-apk\app-debug.apk
Installing build\app\outputs\flutter-apk\app-debug.apk...  (success)
I/flutter: FUNNITY_BUILD_PROOF=verify_350c7ba_20261030_fresh api=https://funnity.com/api flavor=prod
```

Commands used:

```powershell
flutter clean
flutter pub get
flutter run -d emulator-5554 `
  --dart-define=FLAVOR=prod `
  --dart-define=API_BASE_URL=https://funnity.com/api `
  --dart-define=BUILD_PROOF=verify_350c7ba_20261030_fresh
```

**Conclusion:** The running app is the freshly built debug APK from current tree (`350c7ba` + QA probes), not a stale binary.

## 12. Gray map root cause

| Check | Result |
|-------|--------|
| `INTERNET` permission | Present in `AndroidManifest.xml` |
| Emulator network | Ping 8.8.8.8 and `tile.openstreetmap.org` OK (~200–500 ms) |
| Host OSM GET w/ `flutter_map (com.funnity.app)` UA | HTTP 200, PNG |
| `FUNNITY_MAP_TILE_ERROR` count | **0** (no decode/network exceptions observed) |
| Initial screenshot map region | ~94% near-gray pixels (tiles not yet painted) |
| After pan + wait | File size ↑; terrain/labels visible (tan desert, place names); gray ratio ↓ |

**Root cause:** Slow / deferred painting of **public OSM** tiles on the emulator (high latency + many concurrent tile fetches). Markers/clusters could paint before basemap tiles. Not a missing permission, not a wrong package ID, and not a broken TileLayer URL.

flutter_map also logs the standard OSM tile-usage policy warning (expected when using `tile.openstreetmap.org`).

**Not changed:** tile provider URL (no replacement without approval). Added diagnostic `errorTileCallback` only.

**Mitigation options (future, needs approval):** dedicated tile CDN / MapTiler / self-hosted tiles; optional loading placeholder; enable/tune caching.

## 13. Flutter analyze

```text
flutter analyze lib/main.dart lib/features/discover/.../discover_map_view.dart ...
→ No issues found
```

## 14. Flutter test

```text
flutter test test/features/discover test/features/events/discover_filters_test.dart
→ All tests passed (exit 0)
```

Golden 0.09% baseline: **not** regenerated.

## 15. Web ↔ Flutter parity findings (Discover)

| Item | Web Explore V4 | Fresh Flutter (verified) | Notes |
|------|----------------|--------------------------|-------|
| Title | No in-page H1 | Full-width “Discover” | Acceptable IA difference |
| Subtitle | None | Full-width under title | OK |
| Map/List | Toolbar end | Compact, end-aligned below title | Match intent |
| Create Event | Bottom nav / sidebar | Bottom nav only (not in header) | Match |
| Location chip | Present | “All Egypt” | Match |
| Map viewport | Dominant | Present; tiles slow on emulator | Network |
| Bottom nav 5 tabs | Yes | Yes | Match |
| Light theme / purple | Yes | Yes | Match |

Evidence: `docs/flutter/parity_evidence/03_discover_fresh.png`, `05_discover_after_pan.png`, `ui_fresh.xml`.

## 16. Files modified (this QA)

- `mobile/lib/main.dart`
- `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart`
- `docs/flutter/WINDOWS_ANDROID_EMULATOR_QA.md`
- `docs/flutter/parity_evidence/*` (stamps + screenshots)

## 17. Remaining issues

1. Public OSM basemap can appear gray for several seconds (or until pan) on emulator — network/provider, not layout.
2. Arabic RTL not re-verified in this pass.
3. Web viewport screenshots @360–412 still not captured.
4. Uncommitted QA probes (`BUILD_PROOF` print + tile error log) still in working tree.
5. Full-suite golden 0.09% still open (baseline preserved).

## 18. Final verdict

**VERIFIED LATEST BUILD**

The installed `com.funnity.app` on `emulator-5554` was rebuilt and reinstalled from current source (`350c7ba` + QA probes). Proven by:

1. New `lastUpdateTime` / `codePath`
2. Log line `FUNNITY_BUILD_PROOF=verify_350c7ba_20261030_fresh`
3. Discover UI matching committed parity layout

Gray basemap is a **tile-loading latency** issue with public OSM on the emulator, not proof of a stale APK.
