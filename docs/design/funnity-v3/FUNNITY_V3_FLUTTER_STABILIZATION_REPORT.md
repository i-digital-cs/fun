# Funnity V3 — Flutter Stabilization Report

**Date:** 28 October 2026  
**Branch:** `fix/funnity-v3-flutter-stabilization`  
**Baseline:** `c489220fd7443a4b4287a0d6511d56b2292fdb5d` (`feature/funnity-v3-social-home`)  
**Verdict: CONDITIONAL**

---

## 1. Root causes

### P0 — Email authentication redirect
After the access theater finished, `commitPendingSession()` updated Riverpod auth state and relied **only** on GoRouter `refreshListenable` + `redirect` to leave `/login`. On Android that race often left the user on the login/theater surface (or failed to honor `returnTo`). Social login called a no-op `commitPendingSession` after already setting `Authenticated`.

**Fix:** `resolvePostAuthLocation` + `goAfterSuccessfulAuth` explicitly `context.go` to allowlisted `returnTo` or gates (`/email/verify`, `/identity`) or `/home`. Applied on login theater success, Google/Apple success, register, email verify, and reset-password success. Failed login still surfaces theater `onCancel` error on the form.

### P0 — Friends discovery cards
`PeopleSuggestionCard` used `mainAxisSize: min` in a horizontal `Row`, so heights varied with name/city lines and buttons floated inconsistently. Avatars lacked a hard `SizedBox` frame.

**Fix:** Fixed `cardWidth`/`cardHeight`, fixed avatar box, reserved text slots, bottom-aligned actions, horizontal `ListView.separated` with fixed height. Friends search rows use a constrained `Row` + fixed 40px avatar (no ListTile trailing overflow).

### P1 — Discover scrolling
Discover title/create row lived **outside** `CustomScrollView`, so it never scrolled away.

**Fix:** Single scroll controller on the list; `discoverHeaderVisibleAfterScroll` hides header on scroll-down and shows on scroll-up / near top. Filters stay in the scrollable body (state in `DiscoverController`). `PageStorageKey` preserves list offset when returning from event details. Bottom nav unchanged in `MainShell`.

### P1 — Mobile header redesign
Compact topbar still jammed language into the action cluster beside notify/chat/avatar.

**Fix:** Row 1 = logo (public site) + notifications + chat + profile. Row 2 = full-width search + Discover filter shortcut. Language remains on **Profile** (and wide topbar), not removed.

---

## 2. Files changed

| Area | Files |
| --- | --- |
| Auth nav | `mobile/lib/core/navigation/app_navigation.dart`, `login_page.dart`, `register_page.dart`, `verify_email_page.dart`, `reset_password_page.dart` |
| Friends | `people_suggestions_section.dart`, `friends_page.dart` |
| Discover | `discover_page.dart`, `discover_header_scroll.dart` (new) |
| Header | `dash_topbar.dart` |
| Tests | `app_navigation_test.dart`, `people_suggestion_card_test.dart`, `discover_scroll_header_test.dart` (new), `auth_controller_test.dart` (fake repo methods) |
| Docs | this report |

---

## 3. Before / after

| Issue | Before | After |
| --- | --- | --- |
| Email login | Theater success → often stuck / missed `returnTo` | Commit session + `go` to safe destination |
| Failed login | Error via theater cancel | Unchanged — form shows message |
| People cards | Uneven heights / carousel clip risk | Uniform 152×248 cards |
| Discover header | Always pinned above list | Collapses on down-scroll, returns on up |
| Mobile topbar | Crowded + language chip | Two-row clean layout; language on Profile |

---

## 4. Tests and results

```
flutter analyze (touched libs) → No issues found

flutter test:
  app_navigation_test (sanitize + resolvePostAuthLocation)
  route_after_auth_test
  auth_controller_test
  people_suggestion_card_test (320/360/390/412/430 + uniform carousel)
  discover_scroll_header_test
  event_card_layout_test
→ 24 passed
```

---

## 5. Screenshots

**Emulator/device screenshots: not available** (only Linux desktop device connected).  
Do not treat this report as device visual PASS.

---

## 6. APK build

```
flutter build apk --debug → success
build/app/outputs/flutter-apk/app-debug.apk (~194 MiB)
```

APK is a local build artifact — **not** committed.

---

## 7. Remaining limitations

1. No Android emulator/device UI capture in this environment.
2. Discover header collapse is notification-driven (not a Material `SliverAppBar`); filters remain in the scroll body by design.
3. Language switcher is on Profile for compact phones (still available; not deleted).
4. Uncommitted until owner approval.

---

## 8. Verdict

**CONDITIONAL** — All four defects fixed in code with unit/widget coverage and a successful debug APK build; **device PASS blocked** until owner/emulator visual confirmation.

**Ready for owner review / commit approval** on `fix/funnity-v3-flutter-stabilization` (does not modify `feature/funnity-v3-social-home`).
