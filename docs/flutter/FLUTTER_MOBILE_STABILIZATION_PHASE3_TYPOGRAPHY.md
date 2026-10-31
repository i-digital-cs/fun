# Flutter Mobile Stabilization — Phase 3 (Official Arabic Typography)

**Date:** 2026-10-31  
**Scope:** Bundle and apply product-approved **Tajawal** for all Arabic Flutter UI; preserve English IBM Plex Sans; theme-central application; emulator + analyze/test QA  
**Admin Dashboard / Laravel:** Not touched

---

## Verdict

| Layer | Result |
|-------|--------|
| Implementation | **PASS** — Tajawal bundled (400/500/700/800), registered in `pubspec.yaml`, applied via `AppTheme.light(locale:)` |
| Hardcoded font audit | **PASS** — only intentional `monospace` on profile public ID |
| Automated tests | **PASS** — **124/124** (includes new typography suite) |
| `flutter analyze` | **PASS** for typography files; repo still has pre-existing warnings (15), no new theme errors |
| Emulator AR QA | **PASS** — login, Discover, register RTL + Arabic chrome; EN Discover labels unchanged |
| Font scale | **PASS** — AR register at system `font_scale=1.3` without clipped glyphs in captured viewport |
| Overall | **PASS** |

---

## 1. Requirements mapping

| # | Requirement | Status |
|---|-------------|--------|
| 1 | Tajawal on all Arabic Flutter UI | Done via theme |
| 2 | Bundle locally (no AR runtime download) | `assets/fonts/Tajawal/*.ttf` in APK |
| 3 | Central theme + locale-aware | `AppTheme.light(locale:)` + `FunApp` rebuild |
| 4 | Preserve English font | Still `GoogleFonts.ibmPlexSansTextTheme()` |
| 5 | Weights 400/500/700 (+800 for existing w800 titles) | Regular / Medium / Bold / ExtraBold |
| 6 | Screens (home, discover, cards, friends, communities, chat, auth, profile, notifications, settings) | Inherit Material text themes; no per-screen AR font hardcodes |
| 7 | RTL / wrap / line height / scaling | Locale + Directionality unchanged; text-scale widget test + emulator font_scale |
| 8 | Avoid widget-level font overrides | Audit clean except monospace public ID |
| 9 | Dialogs, buttons, nav, sheets | Theme pins family on dialog / buttons / nav / inputs / snackbar / tabs / list tiles |
| 10 | Preserve functionality + tests | 124/124 green |

---

## 2. Implementation

### Assets
- `mobile/assets/fonts/Tajawal/Tajawal-Regular.ttf` (400)
- `mobile/assets/fonts/Tajawal/Tajawal-Medium.ttf` (500)
- `mobile/assets/fonts/Tajawal/Tajawal-Bold.ttf` (700)
- `mobile/assets/fonts/Tajawal/Tajawal-ExtraBold.ttf` (800)
- `mobile/assets/fonts/Tajawal/OFL.txt` (SIL OFL)

### Registration
`mobile/pubspec.yaml` → `flutter.fonts` family `Tajawal`.

### Theme
`mobile/lib/app/theme/app_theme.dart`:
- `ar` → `_tajawalTextTheme()` + `fontFamily: Tajawal` on ThemeData / component text styles
- `en` → `GoogleFonts.ibmPlexSansTextTheme()` (unchanged)
- Component coverage: AppBar, Dialog, Elevated/Filled/Text/Outlined buttons, NavigationBar/Rail, TabBar, Chip, ListTile, InputDecoration, SnackBar

### Docs
- `docs/FLUTTER_WEB_PARITY.md` §10 item **31** + footer
- `docs/FLUTTER_BEST_PRACTICES.md` stack fonts row

### Tests
`mobile/test/app/theme/app_theme_typography_test.dart` — Tajawal on text/dialog/button/nav/input themes; AR large text-scale sample UI without overflow.

---

## 3. Hardcoded font audit

| Location | Family | Action |
|----------|--------|--------|
| `app_theme.dart` | Tajawal / IBM Plex | Central (expected) |
| `profile_page.dart` public ID | `monospace` | **Kept** (codes/IDs) |
| All other feature widgets | (inherit theme) | No AR overrides |

Communities remain deferred product-wise (no member API); when added they will inherit `AppTheme`.

---

## 4. Commands

```text
flutter analyze   # typography: clean; repo: 15 pre-existing infos/warnings
flutter test      # 124/124 All tests passed!
```

APK packaging check: debug APK contains  
`assets/flutter_assets/assets/fonts/Tajawal/Tajawal-{Regular,Medium,Bold,ExtraBold}.ttf`.

---

## 5. Emulator QA (`bidex_api34` / `emulator-5554` / `com.funnity.app`)

Installed fresh `app-debug.apk` with bundled Tajawal.

| Evidence | File | Notes |
|----------|------|--------|
| AR login | `phase3_typography_qa/03_login_ar.png` | RTL; تسجيل الدخول / ع→EN toggle |
| AR Discover | `phase3_typography_qa/04_discover_ar.png` | اكتشف, تطبيق, bottom nav Arabic |
| AR register | `phase3_typography_qa/05_register_ar.png` | إنشاء حساب + form hints |
| AR + font_scale 1.3 | `phase3_typography_qa/07_login_ar_fontscale*.png` | No clipped Arabic glyphs in viewport |
| EN Discover | `phase3_typography_qa/08_discover_en.png` / UI dump | Discover / Home / Log in English labels |
| UI dumps | `ui_login_ar.xml`, `ui_discover_ar.xml`, `ui_register_ar.xml`, `ui_login_en.xml` | Accessibility tree text |

**Observations**
- Arabic UI uses Tajawal via theme (fonts present in APK; theme tests assert `fontFamily == Tajawal`).
- English chrome remains IBM Plex Sans path; labels render in English without Tajawal substitution.
- Long Arabic strings wrap; large text scale sample test + emulator scale shot show no overflow exceptions.
- Map/List chrome strings that are still English product copy (`Map`/`List`/`All Egypt`) are content, not font regressions.

---

## 6. Files touched (this phase)

| Path | Change |
|------|--------|
| `mobile/assets/fonts/Tajawal/*` | New bundled fonts + OFL |
| `mobile/pubspec.yaml` | Fonts family registration |
| `mobile/lib/app/theme/app_theme.dart` | Locale-aware Tajawal / IBM Plex |
| `mobile/test/app/theme/app_theme_typography_test.dart` | New |
| `docs/FLUTTER_WEB_PARITY.md` | §10.31 + last updated |
| `docs/FLUTTER_BEST_PRACTICES.md` | Fonts stack note |
| `docs/flutter/FLUTTER_MOBILE_STABILIZATION_PHASE3_TYPOGRAPHY.md` | This report |
| `docs/flutter/phase3_typography_qa/*` | Emulator evidence |

---

## 7. Out of scope (honored)

- Laravel Admin Dashboard / unrelated backend
- AAB / signing / versionCode changes
- Committing or pushing (not requested)
