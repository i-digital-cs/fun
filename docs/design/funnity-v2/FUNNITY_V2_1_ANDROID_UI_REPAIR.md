# Funnity V2.1 — Android UI Rescue & Branding Fix

**Date:** 28 October 2026  
**Branch:** `main` (working tree; no commit/push)  
**Verdict: CONDITIONAL**

---

## 1. Executive summary

Flutter mobile UI stabilization for the owner-installed Debug APK: Friends Discovery overflow fixed, suggestion cards rebuilt for phone widths, event cards compacted (~313 logical px vs prior fixed ~360–440), homepage spacing tightened, mobile header moved to a two-row layout, and Android launcher icons regenerated from the approved Funnity mark assets.

**Not device-verified on a phone/emulator in this environment** (Linux desktop Flutter target only). Widget tests cover overflow and card height; owner should reinstall the new Debug APK for visual confirmation.

---

## 2. Root causes

| Defect | Root cause | Fix |
| --- | --- | --- |
| `BOTTOM OVERFLOWED BY 7.0 PIXELS` (Friends Discovery) | Horizontal cards in a **fixed `SizedBox(height: 188)`** with Material buttons at default ~48px min height + `Spacer` — content exceeded the box | Intrinsic-height horizontal strip; compact `ButtonStyle` (`minHeight: 36`, shrinkWrap tap target); vertical card stack |
| Oversized friend cards / truncated names | Wide side-by-side avatar+name + dual Expanded buttons in 168px cards | Centered 56px avatar, 148px card width, 2-line ellipsis name, stacked Add/Profile actions |
| Event cards nearly full-screen | Fixed `cardHeight ≈ 360+` Stack layout + large media + 3D tilt | Column layout; image capped 120–140px (~16:9); tighter body; tilt removed (also inflated bounds) |
| Cramped mobile header / truncated search | Single 70px row: brand + search + many icons | Compact mode: row1 brand+actions, row2 full-width search |
| Excessive homepage whitespace | Section tops mostly `28` / large greeting | Coherent 12–16 spacing scale; greeting 22sp |
| “Set your location” | Expected when `user.cityName == null` — not a GPS bug | Documented; location shortcut still opens settings; GPS remains opt-in |
| Launcher branding drift | Adaptive FG / mipmaps not freshly aligned to `assets/branding` | Regenerated from `funity-mark.png` / `funity-app-icon.png`; bg `#6366F1` |

---

## 3. Files changed

### Flutter UI
- `mobile/lib/features/home/presentation/widgets/people_suggestions_section.dart` — rebuild cards + overflow fix
- `mobile/lib/features/events/presentation/widgets/event_card.dart` — compact mobile card
- `mobile/lib/features/home/presentation/widgets/dash_topbar.dart` — two-row compact header
- `mobile/lib/features/home/presentation/home_page.dart` — spacing / hierarchy

### Branding (Android)
- `mobile/android/app/src/main/res/drawable/ic_launcher_foreground.png` — from `assets/branding/funity-mark.png` (safe-zone padded, transparent)
- `mobile/android/app/src/main/res/mipmap-*/ic_launcher.png` — from `assets/branding/funity-app-icon.png`
- `mobile/android/app/src/main/res/values/colors.xml` — `ic_launcher_background` / notification `#6366F1`

### Tests
- `mobile/test/features/home/people_suggestion_card_test.dart` (new)
- `mobile/test/features/events/event_card_layout_test.dart` (new)

### Docs
- This file

---

## 4. Branding assets used

| Asset | Role |
| --- | --- |
| `mobile/assets/branding/funity-mark.svg` | In-app mark (unchanged geometry) |
| `mobile/assets/branding/funity-mark.png` | Adaptive foreground source |
| `mobile/assets/branding/funity-app-icon.png` | Legacy mipmap / full-bleed icon source |
| `public/branding/funity-mark.svg` | Canonical web mark (same geometry) |

No new logo invented. In-app lockup continues to use `kFunityMarkAsset`.

---

## 5. Before / after (behavioral)

| Area | Before | After |
| --- | --- | --- |
| People strip | Fixed 188px height → yellow/black overflow banner | Intrinsic height; no overflow at 360–430 / textScale 1.3 |
| People card | Side-by-side cramped 168×~188 | Vertical 148-wide; avatar 56; stacked CTAs |
| Event card | Fixed ~360–440 tall Stack | ~313 tall compact Column; image ≤140 |
| Header (phone) | One crowded row | Brand/actions + full-width search |
| Launcher | Stale / inconsistent adaptive FG | Regenerated mark + indigo adaptive bg |

---

## 6. Responsive verification

| Check | Result |
| --- | --- |
| Widget tests 360 / 390 / 412 / 430 | PASS (people cards) |
| Arabic + textScale 1.3 | PASS (people card test) |
| Event card height &lt; 320 | PASS (~313) |
| Real Android emulator / device screenshots | **NOT RUN** — no AVD / device on this host |
| Owner APK reinstall visual QA | Pending owner |

---

## 7. Location discovery UX note

`LocationShortcutCard` shows **“Set your location”** when `cityName` is null. That is intentional until the member saves a city (or enables the optional device-GPS preference on Location settings). The app does **not** request location permission on every launch.

---

## 8. Test results

```
flutter analyze (changed UI files) → No issues found

flutter test \
  test/features/home/people_suggestion_card_test.dart \
  test/features/events/event_card_layout_test.dart \
  test/features/friends/friend_user_suggestion_test.dart \
  test/core/navigation/app_navigation_test.dart
→ All tests passed! (10)
```

---

## 9. APK build

```
GRADLE_USER_HOME=$HOME/.gradle-exec flutter build apk --debug
→ ✓ Built (Debug APK)
Artifact: mobile/build/app/outputs/flutter-apk/app-debug.apk
```

Not distributed / not published. Reinstall over the previous Debug build for acceptance.

---

## 10. Remaining limitations

1. No real-device screenshots in this session (widget tests ≠ device visual QA).
2. Shared interests still placeholder copy until a real interests schema exists (API unchanged).
3. Home hero remains a large visual block by design (V2); only section gaps were tightened.
4. Adaptive icon appearance varies by OEM launcher mask — owner should confirm on their device.

---

## 11. Verdict

**CONDITIONAL** — code fixes + tests + Debug APK ready; **PASS** only after owner confirms on-device: no overflow, compact cards, two-row header, and correct launcher icon.
