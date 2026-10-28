# Funnity V2.1 — Implementation Report

**Date:** 28 October 2026  
**Branch:** `feature/funnity-v2-1-discovery-nav` (from `feature/funnity-v2-homepage`; V2 visual WIP preserved)  
**Verdict: CONDITIONAL**

---

## 1. Executive summary

P0 navigation repairs, Option B geographic discovery (city-first + optional foreground GPS origin), and Friends people-suggestions (API + Web + Flutter home) are **implemented in code**. Automated Laravel and Flutter tests for the new contracts pass. A **Debug APK** builds successfully when Gradle caches live on an executable filesystem (`GRADLE_USER_HOME` under `$HOME`).

**Not device-verified:** Flow A–H on Android/iOS emulator or hardware (none available in this environment — only Linux desktop Flutter target). Flutter full homepage with people strip was not captured on a running emulator. Guest web screenshots show geo discovery (“Use my location”); people strip requires authenticated peers and is empty for guests.

Hence **CONDITIONAL**, not PASS. No deploy / publish / PR without owner approval.

---

## 2. Implemented vs device-verified

| Area | Implemented | Device-verified |
| --- | --- | --- |
| P0 navigation code (returnTo, GoRouter, FCM open, friends tab, web intended) | Yes | **No** (unit/widget + feature tests only) |
| Flows A–H (Back, AppBar, bottom nav, search restore, auth redirects, deep links, notifications, chat, modals) | Code paths present | **No** — no Android AVD / physical device |
| Geographic discovery Option B (nearby / expand / Worth the Trip) | Yes | Web screenshots only |
| Foreground GPS origin (Flutter + Web + API validation) | Yes | **Not** exercised on device GPS hardware |
| People suggestions API + Web Blade | Yes | Web guest: section hidden when empty |
| Flutter people suggestions home section | Yes | **No** emulator UI capture |
| Debug APK assemble | Yes (with `$HOME` Gradle cache) | APK not installed / not distributed |
| Light-only V2 visual | Yes | Desktop + mobile web + AR RTL screenshots |

---

## 3. Git branch and working-tree status

- Branch: `feature/funnity-v2-1-discovery-nav`
- Uncommitted V2 + V2.1 work preserved (no reset/clean)
- No commit / PR / production deploy performed

---

## 4. Completed tasks (this completion pass)

1. **Flutter Friends Discovery UI** — `PeopleSuggestionsSection` on home; `GET /api/people/suggestions`; existing friendship request/cancel/view-profile; loading/empty/error; no new friendship model; coords never parsed into `FriendUser`.
2. **Permission-based GPS discovery** — city-first retained; optional device lat/lng on discover home; foreground-only Geolocator; Web “Use my location” after click in secure context; server validates bounds; `origin_type` city vs device; no user coords in people/profile APIs; no background location permission.
3. **Screenshots** — fresh V2.1 web desktop/mobile/geo/AR RTL under `docs/design/funnity-v2/screenshots/v2-1-*`.
4. **Regression tests** — Laravel discovery/people/privacy/friendship + Flutter nav/friends/visual (see §11).
5. **Android Debug APK** — built after diagnosing AAPT2 `noexec` on `/tmp` (see §13).
6. **Docs** — this report; `ROADMAP.md` V2.1 supersede note; parity gap updates.

---

## 5. Navigation (P0) — code status

| ID | Fix |
| --- | --- |
| NAV-F8 | Single GoRouter + `refreshListenable`; no rebuild wipe on auth watch |
| NAV-F1 | Allowlisted `returnTo` through login/gates |
| NAV-F6 | `/friends?tab=requests` honored |
| NAV-F7 | FCM `getInitialMessage` + `onMessageOpenedApp` → `openAppPath` |
| NAV-F3 / F2 | Post-create / drill-down hygiene vs stack-clearing `go` |
| NAV-W3 | Identity + email verification `redirect()->intended(...)` |

Contract: `docs/audits/FUNNITY_V2_1_NAVIGATION_CONTRACT.md`

**Flow A–H:** Automated coverage is partial (`app_navigation_test`, `AuthIntendedRedirectTest`). **Manual matrix on real device/emulator: NOT RUN** — environment has `Linux (desktop)` only; `emulator` binary / AVDs unavailable.

---

## 6. Geographic discovery + GPS behavior

### Architecture (unchanged Option B)

- `EventDiscoveryService` — Nearby + expansion ladder + Worth the Trip (de-duped); crow-flies km.
- Radii: `5, 10, 25, 50, 100, 0` (`0` = All distances).
- API SoT: `GET /api/events/discover/home`.

### Device origin (new)

| Layer | Behavior |
| --- | --- |
| API | Optional `latitude`/`longitude` query; bounds −90…90 / −180…180; rejected otherwise; **never** echoed as `user_latitude` / profile fields |
| Origin types | `city` (saved/manual city centroid) vs `device` (ephemeral request coords) |
| Persistence | Device coords **not** stored on `users`; preference flag only (`discovery_use_device_location` SharedPreferences / web consent click) |
| Flutter | `DiscoveryLocationService` — one-shot `getCurrentPosition`; denied / deniedForever clears preference; no stream / no background |
| Android | `ACCESS_COARSE_LOCATION` + `ACCESS_FINE_LOCATION` only (no background) |
| iOS | When-in-use usage string via `Info.plist` (existing Geolocator setup) |
| Web | `navigator.geolocation` after **Use my location** click; secure context required |
| Logging | No deliberate precise-coordinate logging added |

Manual city selection and saved city fallback remain primary when GPS is off/denied.

---

## 7. Flutter Friends Discovery

| Item | Detail |
| --- | --- |
| Widget | `mobile/lib/features/home/presentation/widgets/people_suggestions_section.dart` |
| Mounted | `home_page.dart` Sliver |
| API | `GET /api/people/suggestions` via friends remote/repo |
| Card | Photo / initials, name, shared interests when present, friendship CTA |
| Actions | Add Friend (if `can_request`), View Profile; pending state prevents duplicate request |
| Privacy | Same server exclusions (self / friends / pending / blocked); no coordinates in model/tests |
| States | Loading, empty (hidden/quiet), error with retry |
| l10n | EN/AR via app localizations + friendship strings |

Web: `x-community.people-suggestions` (renders only when suggestions non-empty).

---

## 8. Database migrations

None. No destructive schema changes. Config-only distance list.

---

## 9. API changes (additive)

| Endpoint | Notes |
| --- | --- |
| `GET /api/events/discover/home` | Bundle; accepts optional device lat/lng |
| `GET /api/people/suggestions` | Auth + active; throttled; no coords |
| `GET /api/cities` `distances_km` | Includes 100 and 0 |
| Existing `GET /api/events` | Unchanged; distance `0` = all |

---

## 10. Key changed / added files

**Laravel:** `EventDiscoveryService`, `PeopleDiscoveryService`, discovery/people API controllers, `DiscoverEventsRequest`, Home/Dashboard controllers, discover Blade + people-suggestions component, location/events/friends lang, feature tests.

**Flutter:** `core/navigation/*`, `core/location/discovery_location_service.dart`, home people section + nearby GPS filters, location settings device switch, geolocator + Android/iOS manifests, friends suggestion parsing, nav/friends tests.

**Docs:** this report, audits, `ROADMAP.md` V2.1 supersede, `FLUTTER_WEB_PARITY.md`, screenshots.

---

## 11. Test results (exact)

### Laravel (this session)

```
php artisan test --filter='EventDiscovery|PeopleSuggestions|AuthIntended|HomePage'
→ 21 passed (88 assertions)

php artisan test --filter='DiscoverNearby|Privacy|Block|Friendship|People'
→ 33 passed (124 assertions)  # includes DiscoverNearby, FriendshipApi, SafetyReportsAndBlocks, PeopleSuggestions
```

Notable cases: device origin accepted / never echoed; invalid coords rejected; suggestions exclude self/friends/blocked and omit coordinates.

### Flutter (this session)

```
flutter test test/core/navigation test/features/friends test/visual
→ All tests passed (5) — app_navigation_test, friend_user_suggestion_test, v2_home_hero_capture_test
```

**Not claimed:** instrumented Android UI / integration tests for Flows A–H.

---

## 12. Visual screenshots

Directory: `docs/design/funnity-v2/screenshots/`

| File | Notes |
| --- | --- |
| `v2-1-web-desktop-1440.png` | Desktop hero (light V2) |
| `v2-1-web-desktop-1440-full.png` | Full-page desktop |
| `v2-1-web-desktop-geo-discovery.png` | Happening Near You + **Use my location** |
| `v2-1-web-desktop-geo-lower.png` | Lower geo / cards |
| `v2-1-web-mobile-390.png` | Mobile hero |
| `v2-1-web-mobile-390-mid.png` | Mobile geo + Use my location |
| `v2-1-web-desktop-ar-rtl.png` | Arabic `dir=rtl` hero |
| `v2-1-web-desktop-ar-geo.png` | Arabic geo region |
| `flutter-home-hero.png` | Widget golden (hero tokens) — **not** full emulator home with people strip |

People strip: guest HTML omits section when empty; authenticated peer data required for visual proof of cards.

---

## 13. Android build status

| Attempt | Result |
| --- | --- |
| Default Gradle cache under `/tmp/...` | **FAIL** — AAPT2 daemon startup failed |
| Root cause | `/tmp` is **noexec** (`Permission denied` even with `+x` on binaries) |
| Fix | `GRADLE_USER_HOME=/home/d14098/.gradle-exec` (executable FS) — **no AGP/build-tools upgrade** |
| `./gradlew :app:assembleDebug` | **BUILD SUCCESSFUL** (~3m 40s) |
| Artifact | `mobile/build/app/outputs/flutter-apk/app-debug.apk` (~164 MB) |

**Not done:** install on device, Play upload, release/App Bundle, distribution. Rebuild locally with the same `GRADLE_USER_HOME` (or any exec-capable Gradle home) if `/tmp` remains noexec.

---

## 14. Remaining blockers / gaps

1. **Flow A–H device matrix** — owner or CI with emulator/device sign-off required for PASS.
2. **Flutter emulator screenshot** of people + GPS UX — Linux desktop only here.
3. **Authenticated web screenshot** of people cards (needs seed peers in city).
4. App Links / HTTPS deep links still absent (NAV-F10 — P1).
5. Discover filter URL sync on Flutter still Riverpod-SoT (documented earlier).

---

## 15. Production readiness

**CONDITIONAL** — code complete for V2.1 discovery + people + GPS consent path; automated tests green; Debug APK reproducible with exec-capable Gradle home.

**Do not deploy or publish** until owner accepts: device Flow A–H, optional authenticated people visual, and staging review. No commit/PR without approval.
