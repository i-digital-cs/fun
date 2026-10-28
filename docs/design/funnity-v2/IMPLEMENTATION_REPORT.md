# Funnity V2 — implementation report (visual acceptance)

Date: 28 October 2026  
Branch: `feature/funnity-v2-homepage`  
**Final verdict: CONDITIONAL**

Production deploy: **not approved / not performed**.  
Commit / PR: **not created** (awaiting owner visual approval).

---

## Verdict evidence

| Area | Result | Evidence |
| --- | --- | --- |
| Web visual (durable screenshots) | Pass with documented gaps | `docs/design/funnity-v2/screenshots/web-*.png` vs `references/APPROVED_V2_REFERENCE.png` |
| Flutter SDK compatibility | Resolved via side-by-side SDK | `/home/d14098/flutter-3.47` → Flutter **3.47.8**, Dart **3.13.7** (satisfies `sdk: ^3.13.1` without changing `pubspec.yaml`) |
| Flutter analyze | Pass for app code; pre-existing test mock errors | See § Flutter verification |
| Flutter unit tests (relevant) | **22 passed** | `test/core`, `event_join_actions_test`, `user_test` |
| Flutter build | **bundle OK**; APK debug **failed** (AAPT2) | See § Flutter verification |
| Flutter homepage screenshot | **Partial** | `flutter-home-hero.png` golden of V2 hero tokens — **not** full app on emulator/device |
| Emulator / physical device | **Unavailable** | `flutter devices` → Linux desktop only; no Android emulator image started; `DISPLAY` empty |
| Functional regression (web) | Pass (smoke + PHPUnit slice) | See § Functional regression |
| Production safety | Pass | No deploy; no production data changes |

**Why not PASS:** Flutter full-homepage visual verification on emulator/device was not achieved.  
**Why not BLOCKED:** Web implementation is screenshot-verified at required viewports; Flutter tokens/hero compile and unit-test under a compatible SDK; no critical functional break found on the homepage smoke path.

---

## 1. Web visual verification

### Screenshots saved

| File | Size |
| --- | --- |
| `screenshots/web-desktop-1440.png` | 1440×1600 |
| `screenshots/web-desktop-1440-tall.png` | 1440×3200 |
| `screenshots/web-laptop-1280.png` | 1280×1400 |
| `screenshots/web-tablet-768.png` | 768×1400 |
| `screenshots/web-mobile-390.png` | 390×1200 |

Tool: Chrome for Testing 156 headless → files written directly under `docs/design/funnity-v2/screenshots/`.

### Comparison vs `APPROVED_V2_REFERENCE.png`

| Check | Finding |
| --- | --- |
| Hero photography | Present (Cairo skyline asset + purple/orange wash). Reference is warmer sunset skyline + authenticated Arabic welcome — guest EN hero copy retained. |
| Typography / hierarchy | Match direction: bold white hero title, amber brand line, glass search. |
| Gradients / glass | Match: purple hero, glass stats/highlight, soft lavender ambient. |
| Cards / spacing | White premium event cards with purple progress + soft Join/View pill; good spacing at 1440/1280. |
| Weather | Photographic sunset wash + glass forecast chips; real API data (27°, city, forecast). |
| Active participations | Guest session has no participations (expected). Authenticated card chrome exists in Blade; not visible in guest screenshots. |
| Event grid | 3-column desktop; photography + badges + members bar present. |
| Create Event CTA | Strong orange gradient card present. |
| Trust | Emerald/teal glass bar with safety CTAs (reference interest-tagger not invented). |
| Nav / footer | White glass nav, orange Register, refined footer. |

### Visual differences found

1. Reference is **authenticated Arabic RTL** with Communities/People — **not implemented** (no product feature).
2. Reference hero photography is warmer/more prominent; first pass wash was too heavy.
3. Reference bottom green bar is interest discovery; product keeps **safety trust** content.
4. Category chips use emoji + labels (existing) vs icon-only mock style.
5. Guest EN screenshots vs Arabic reference language — locale switch verified (`dir=rtl`, `lang=ar`) but acceptance shots are EN guest.

### Fixes applied during acceptance

- Lightened hero gradient wash so Cairo photography reads more clearly (`resources/views/components/community/hero.blade.php`).
- Rebuilt Vite CSS assets; re-captured durable screenshots after the fix.

---

## 2. Flutter verification

### SDK investigation

| Item | Value |
| --- | --- |
| Default host SDK | `/home/d14098/flutter` → Flutter 3.35.5 / **Dart 3.9.2** (too old for `^3.13.1`) |
| Project constraint | `mobile/pubspec.yaml` → `sdk: ^3.13.1` (**unchanged**) |
| Compatible SDK used | Cloned side-by-side `/home/d14098/flutter-3.47` @ tag **3.47.8** → **Dart 3.13.7** |

### Commands run (compatible SDK)

```text
flutter pub get                         → OK
flutter analyze                         → app OK; 4 pre-existing test mock errors (AuthRepository)
flutter test test/core … join/user      → 22 passed
flutter test --update-goldens visual…   → passed; wrote flutter-home-hero.png
flutter build bundle                    → OK
flutter build apk --debug               → FAILED (AAPT2 daemon startup in Gradle transforms)
flutter devices                         → Linux desktop only; no emulator/device
```

### Flutter screenshot

- Saved: `docs/design/funnity-v2/screenshots/flutter-home-hero.png`
- Method: headless golden render of V2 hero gradient + orange Search CTA using `AppColors` (not a running app screenshot on emulator).
- **Explicit:** No full Flutter homepage screenshot from emulator/device — none available in this environment (`DISPLAY` empty; no emulator booted).

### Analyze note

Pre-existing failures in test fakes missing `loginWithApple` / account-deletion methods — **not introduced by V2**. V2 files only had style infos (underscores) which were cleaned.

---

## 3. Functional regression

Local server smoke (`127.0.0.1:8765`):

| Check | Result |
| --- | --- |
| Home | 200 |
| Search `?q=football` | 200 |
| Category filter | 200 |
| Event show `/events/414/local-fifa-cup-414` | 200 |
| Login / Register pages | 200 |
| Notifications / Chats (guest) | 302 → login (auth gate preserved) |
| Locale → `ar` | 302 then home `dir=rtl` `lang=ar` |
| V2 hero/weather assets in HTML | Present |
| Search input `name=q` | Present |

PHPUnit slice: **OrganicDiscovery + WeatherApi → 11 passed (66 assertions).**

Business logic / APIs / tickets / payments: **not modified**.

---

## 4. Remaining limitations

1. No production deploy; APP_URL still points absolute links at `https://funnity.com` in local HTML.
2. Flutter APK debug failed (AAPT2); bundle succeeded.
3. No Android emulator / physical device / GUI session for full Flutter homepage screenshot.
4. Authenticated participation cards and Arabic chrome not in guest EN acceptance screenshots.
5. Reference Communities/People/interest-tagger deliberately omitted.
6. Hero photo is licensed Unsplash substitute, not the exact mock plate.

---

## 5. Files touched in this acceptance pass

- `resources/views/components/community/hero.blade.php` (lighter wash)
- `docs/design/funnity-v2/screenshots/*` (durable captures)
- `docs/design/funnity-v2/IMPLEMENTATION_REPORT.md` (this update)
- `mobile/test/visual/v2_home_hero_capture_test.dart` + golden
- Vite `public/build/*` rebuilt

---

## 6. Owner next steps (no commit yet)

1. Review durable web screenshots under `docs/design/funnity-v2/screenshots/`.
2. Optionally provide emulator or approve Flutter hero golden as sufficient interim evidence.
3. Approve visual result → then commit / PR / deploy as separate explicit requests.
