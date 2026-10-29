# WEB ↔ FLUTTER UI PARITY AUDIT — Home & Discover

**Date:** 2026-10-29  
**Branch:** `main`  
**Baseline commit (before edits):** `2d88523ae99fd53c847db5fbd6bfb8ea643354ec`  
**Repo root:** `C:\xampp\htdocs\Funity\fun`  
**Flutter root:** `C:\xampp\htdocs\Funity\fun\mobile`  
**Application ID:** `com.funnity.app`  
**Emulator target:** `bidex_api34` → device id `emulator-5554`  
**Preserved untracked artifacts:** `mobile/test/visual/failures/` (not deleted)

---

## 1. Repository baseline

| Check | Result |
|-------|--------|
| Active branch | `main` |
| Sync with `origin/main` | Even (`0` ahead / `0` behind) at audit start |
| Uncommitted at start | Only `?? mobile/test/visual/failures/` |
| Destructive git ops | None |

---

## 2. Active web source-of-truth

Authoritative user-facing web (not unused `components/home/*`, not Living Map unless env-gated):

| Screen | Route | Controller | Template | Shell |
|--------|-------|------------|----------|-------|
| Marketing home | `GET /` | `app/Http/Controllers/Web/HomeController.php` | `resources/views/home/index.blade.php` | `layouts/home.blade.php` |
| Member home | `GET /dashboard` | `app/Http/Controllers/Web/DashboardController.php` | `resources/views/home/dashboard.blade.php` | `layouts/dashboard.blade.php` |
| Discover / Explore | `GET /events` | `app/Http/Controllers/Web/Events/EventController.php` | `resources/views/events/index.blade.php` | `layouts/dashboard.blade.php` |

**Supporting web assets**

- Filters / Map·List: `resources/js/discovery-location.js`
- Leaflet Discover map: `resources/js/discover-map.js` (default; Living Map off unless `LIVING_MAP_REPLACE_DISCOVER`)
- Home mini map: `resources/js/home-mini-map.js`
- Mobile bottom nav: `resources/views/components/dashboard/mobile-nav.blade.php` (Home · Discover · Create · My Events · Profile)
- Topbar search: `resources/views/components/dashboard/topbar.blade.php`
- Tokens / CSS: `resources/css/app.css`, `DESIGN.md`
- Parity contract: `docs/FLUTTER_WEB_PARITY.md` §2.2–2.3, roadmap #23–25

### Web Discover mobile hierarchy (≤412px) — source-derived

1. Dashboard topbar (global search) — **no in-page H1 “Discover”**
2. Compact toolbar: Search · Location · Radius · Category · Apply · **Map \| List**
3. Map pane (default) then results list **or** list-only
4. **Create Event** only via bottom nav / sidebar / empty-state — **not** in Discover toolbar

### Web member Home mobile hierarchy — source-derived

Topbar → greeting/hero → mini map → nearby cards → people / worth-the-trip → rail stacks → bottom nav.

**Visual verification note:** Local Blade screenshots at 360/393/412 were not captured in this pass (no browser session attached). Layout conclusions above are **source-derived** from active templates + CSS breakpoints (`sm` 640, sidebar ~850, `lg` 1024).

---

## 3. Flutter implementation paths

| Concern | Path |
|---------|------|
| Home | `mobile/lib/features/home/presentation/home_page.dart` |
| Home sections / hero / mini map | `widgets/home_sections.dart`, `home_hero_banner.dart`, `home_explore_map_card.dart` |
| Discover | `mobile/lib/features/discover/presentation/discover_page.dart` |
| Discover map | `widgets/discover_map_view.dart`, `discover_map_controller.dart` |
| Shell / topbar / bottom nav | `main_shell.dart`, `widgets/dash_topbar.dart` |
| Location | `discovery_location_controller.dart`, `widgets/location_picker_sheet.dart` |
| Tokens | `mobile/lib/app/theme/app_colors.dart` |

---

## 4. Parity matrix

| Screen | Component | Active Web Behavior | Flutter Behavior (before) | Difference | Class | Proposed Fix |
|--------|-----------|---------------------|---------------------------|------------|-------|--------------|
| Discover | Page title + subtitle | No in-page H1; document title only | Large title+sub in same `Row` as Map/List+Create | Title squeezed → `Disc`/`over` | RESPONSIVE BUG | Stack title full-width; `maxLines: 1` |
| Discover | Map/List switcher | Compact end of toolbar | Unconstrained `SegmentedButton` in crowded `Row` | Dominates width | VISUAL MISMATCH | Own row, compact density, end-aligned |
| Discover | Create Event | Bottom nav / sidebar / empty state | Full `GradientButton` in header `Row` | Excessive width; not on web toolbar | VISUAL MISMATCH | Remove from header (nav Create remains) |
| Discover | Top inset | Content under topbar, no double status gap | `SafeArea` on page **and** topbar | Large blank band | RESPONSIVE BUG | `SafeArea(top: false)` under shell |
| Home | Top inset | Same | Same double SafeArea | Blank under topbar | RESPONSIVE BUG | `SafeArea(top: false)` |
| Home | Space before nearby cards | Hero + mini map + sections intentional | Hero `minHeight` 280 + map 240 + weather… | Tall stack | PLATFORM / MATCH (content) | Keep; only fix double SafeArea |
| Discover | Map initial camera | Egypt / origin-aware Leaflet boot | `initialZoom: 6` then post-frame `fitCamera` + second `move(…,12)` | Africa → Egypt flash | FUNCTIONAL MISMATCH | `initialCameraFit` Egypt; drop duplicate listen |
| Discover | Filters / location chip | Explore V4 modes | Present | Aligns | MATCH | — |
| Shell | Bottom nav 5 tabs | Present &lt;lg | Present | Aligns | MATCH | — |
| Map | Exact coords fabrication | City aggregates vs exact pins | Same API contract | Aligns | MATCH | — |

---

## 5. Confirmed defects & root causes

1. **Discover title wrap** — Single `Row` with `Expanded(title)` + full Map/List + Create left ~20–50px for text on ~360dp → mid-word wrap.
2. **Subtitle squeeze** — Same narrow `Expanded` column.
3. **Controls too wide** — Unconstrained Material 3 `SegmentedButton` + `GradientButton` in header (web puts Create in shell nav).
4. **Empty vertical space (Home/Discover)** — Nested `SafeArea` under `MainShell` topbar re-applied status-bar padding.
5. **Map Africa→Egypt** — First paint at zoom 6 + delayed `fitCamera`, compounded by a second `ref.listen` calling `_map.move(…, 12)`.

---

## 6. Changes implemented (Flutter only)

| File | Change |
|------|--------|
| `mobile/lib/features/discover/presentation/discover_page.dart` | Header → stacked Column; Create removed from header; Map/List compact + end-aligned; `SafeArea(top: false)`; directional padding |
| `mobile/lib/features/home/presentation/home_page.dart` | `SafeArea(top: false)` |
| `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart` | `initialCameraFit` Egypt bounds; remove duplicate zoom-12 listen; shared Egypt fit helper; `userAgentPackageName: com.funnity.app` |
| `mobile/lib/features/home/presentation/widgets/home_explore_map_card.dart` | `userAgentPackageName: com.funnity.app` |

**Not changed:** package id, signing, `versionCode`, backend, web UI, golden baselines, business APIs.

---

## 7. Screenshots / visual evidence

| Evidence | Path / status |
|----------|---------------|
| Web mobile @360/393/412 | **Not captured** (source-derived layout only) |
| Emulator Discover (after fix) | `docs/flutter/parity_evidence/02_discover.png` (+ `_sm.png`) |
| Emulator Home (after fix) | `docs/flutter/parity_evidence/01_home.png` (+ `_sm.png`) |
| UI Automator dump (Discover) | `docs/flutter/parity_evidence/ui.xml` |

**Emulator Discover a11y bounds (1080×2400):** title `[53,446]–[1028,540]` full width; subtitle full width; Map/List at y≈627 end-aligned (below subtitle); no Create in header; map markers for Egyptian cities (Alexandria, Port Said, Damietta, …).

---

## 8. Static analysis

```text
flutter analyze <changed files>
→ No issues found
```

Full-project analyze may still report pre-existing findings outside this change set.

---

## 9. Flutter tests

```text
flutter test test/features/discover test/features/events/discover_filters_test.dart
→ All tests passed (+14)
```

Golden / visual tests: **not** regenerated. Existing failure artifacts under `mobile/test/visual/failures/` preserved. Prior ~0.09% Windows pixel delta remains **UNVERIFIED** as regression vs platform font rendering — baseline not overwritten.

---

## 10. Business logic preservation

No changes to auth, join, tickets, chat, communities, notifications, discovery filter semantics, map marker fabrication rules, or API contracts. Create Event remains reachable via bottom nav + empty states.

---

## 11. Emulator runtime

| Check | Result |
|-------|--------|
| Device | `emulator-5554` · AVD `bidex_api34` · Android 14 · 1080×2400 |
| Launch | `flutter run -d emulator-5554 --dart-define=FLAVOR=prod --dart-define=API_BASE_URL=https://funnity.com/api` — success |
| Discover title wrap | **Fixed** — single-line full-width “Discover” |
| Discover subtitle squeeze | **Fixed** — full-width under title |
| Map/List + Create crowding | **Fixed** — Create removed from header; Map/List compact, end-aligned below title |
| Map initial region | **Egypt** city aggregates visible (not southern Africa) |
| Bottom nav | Home · Discover · Create · My Events · Profile |
| Home double-gap under topbar | Reduced via `SafeArea(top: false)`; remaining scroll height is intentional hero/sections |
| RTL Arabic pass | **Not re-run** this session |
| Map/List toggle interaction | Map selected in dump; List control present |

---

## 12. Remaining issues

| Item | Severity | Notes |
|------|----------|-------|
| Flutter still shows Discover title/sub (web has none) | Low | Kept for app IA; now full-width — acceptable platform difference |
| Map/List labels hardcoded EN (matches current Blade literals) | Low | Localize later with web |
| Home content stack before nearby cards still tall | Info | Intentional sections; not blank SafeArea |
| Living Map V3.3 Flutter port | Deferred | Web env-gated; parity roadmap #26–27 |
| Web mobile screenshots not visually re-verified | Process | Source-derived audit |
| Full `flutter test` suite / golden 0.09% | Open | Baseline preserved |

---

## 13. Files changed

- `mobile/lib/features/discover/presentation/discover_page.dart`
- `mobile/lib/features/home/presentation/home_page.dart`
- `mobile/lib/features/discover/presentation/widgets/discover_map_view.dart`
- `mobile/lib/features/home/presentation/widgets/home_explore_map_card.dart`
- `docs/flutter/WEB_FLUTTER_UI_PARITY_AUDIT.md` (this file)

---

## 14. Final verdict

**CONDITIONAL**

Discover header overflow, Create-in-toolbar mismatch, double top SafeArea, and Egypt map framing are fixed and **emulator-verified**. Remaining gaps for **PASS**: web viewport screenshots @360–412, Arabic RTL pass, full `flutter test` / golden 0.09% triage, and optional removal of Flutter-only Discover title to match web chrome exactly.
