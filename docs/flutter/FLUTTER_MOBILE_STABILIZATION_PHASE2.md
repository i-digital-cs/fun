# Flutter Mobile Stabilization — Phase 2

**Date:** 2026-10-31  
**Scope:** Auth redirect, friend cards, Discover chrome, logo→Home, aggregate hit targets  
**Admin Dashboard V2:** Not touched (server agent parallel work)

---

## 15. Final verdict

| Layer | Verdict |
|-------|---------|
| Code implementation | **PASS** for auth race, logo→Home, friend avatar share, Discover chrome, aggregate z-order/hit boxes |
| Automated tests | **PASS** — **121/121** |
| Emulator verification | **CONDITIONAL** — Discover compact chrome + prior Mansoura sheet evidence; Arabic live toggle incomplete on login; guest Home gated to Login |
| Publishing / OTP / Public Profile | Preserved (contract tests still green) |
| Overall release readiness | **CONDITIONAL** |

---

## 1. Git baseline

| Item | Value |
|------|-------|
| Branch | `main` |
| HEAD | `d3e5df51a4d0797de2eee54e0c2b677991953f6a` |
| Working tree | Dirty — Phase 1 publishing + Phase 2 stabilization (uncommitted) |
| Destructive git | None |
| Backend / admin edits | None |

---

## 2. Working-tree state

Preserved prior uncommitted publishing work. Phase 2 added Flutter-only changes under `mobile/` + this report + `docs/flutter/phase2_qa/`.

---

## 3. Root causes

### Auth stuck after email/OTP
`commitPendingSession()` flips `Authenticated` → GoRouter refresh can dispose `/login` before `goAfterSuccessfulAuth(context, …)`. `if (!mounted) return` then **skipped** the explicit `go`, leaving Android dependent on flaky redirect alone.

### Friend card inconsistency
Home used a private 56px `_Avatar` + fixed 248px card; Friends used a separate 40px `_Avatar`. No shared leaf.

### Discover crowded header
Shell topbar + title/subtitle + Map/List + location + search + (list) duplicate search field stacked. Map never collapsed chrome.

### Dense Cairo aggregate taps
Exact event markers painted **above** city aggregates in one `MarkerLayer`; hit boxes deferred to small children (dead padding).

### Logo
`DashTopbar` brand opened marketing `Env.publicSiteOrigin` instead of in-app `/home`.

---

## 4. Files changed (Phase 2)

| Path | Change |
|------|--------|
| `lib/core/navigation/app_navigation.dart` | `goAfterSuccessfulAuthWithRouter` |
| `lib/features/auth/presentation/login_page.dart` | Pre-commit router/`returnTo`; preserve returnTo on register/forgot |
| `lib/features/auth/presentation/register_page.dart` | Same commit race + returnTo to login |
| `lib/features/auth/presentation/verify_email_page.dart` | Same commit race |
| `lib/features/auth/presentation/reset_password_page.dart` | Same commit race |
| `lib/features/home/presentation/widgets/dash_topbar.dart` | Logo → `context.go('/home')` |
| `lib/features/discover/presentation/discover_page.dart` | Compact Map/List chrome; no duplicate list search; map starts collapsed title |
| `lib/features/discover/presentation/widgets/discover_map_view.dart` | Aggregates on top layer; opaque larger hit targets |
| `lib/features/friends/presentation/widgets/friend_user_avatar.dart` | **New** shared avatar |
| `lib/features/friends/presentation/friends_page.dart` | Use `FriendUserAvatar` |
| `lib/features/home/presentation/widgets/people_suggestions_section.dart` | Shared avatar; cardHeight 264 |
| `test/core/navigation/post_auth_navigation_test.dart` | **New** returnTo contract tests |
| `test/features/discover/discover_filter_panel_test.dart` | Updated (no duplicate search) |

---

## 5. Authentication fixes

1. Capture `GoRouter` + `returnTo` **before** `commitPendingSession()`.
2. Navigate via `goAfterSuccessfulAuthWithRouter` — **no** `mounted` gate.
3. Login ↔ register / forgot-password keep sanitized `returnTo` via `locationWithReturnTo`.
4. Allowlist / `..` rejection from Phase 1 regression preserved.

---

## 6. Navigation fixes

- Topbar logo → `/home` (member shell parity with sidebar).
- Guest tapping Home shell may still land on Login (auth gate) — expected for gated home content.
- Allowlist unchanged except prior `..` harden.

---

## 7. Friend card improvements

- Shared `FriendUserAvatar` (oval + `BoxFit.cover` + initials fallback).
- Home suggestion card height **248 → 264** for AR / text-scale headroom.
- Friends list/search/requests reuse the same avatar leaf.
- No Discover people-card redesign (Discover has none).

---

## 8. Discover header improvements

- Always-on compact **Map | List** row.
- List title/subtitle collapsible; Map starts without large title block.
- Single shared search field (removed duplicate inside `DiscoverFilterPanel`).
- Location chip remains reachable on Map and List.
- Query SSOT / Map-List business logic unchanged.

---

## 9. Aggregate marker fixes

- Second `MarkerLayer`: events under, **city aggregates + clusters on top**.
- Aggregate markers **56–60px** with `HitTestBehavior.opaque` + `SizedBox.expand`.
- City sheet still uses `selectedCity.cityName` (Mansoura → “Events in Mansoura” previously verified).

---

## 10. Arabic / RTL QA

| Check | Result |
|-------|--------|
| Widget EN/AR publish + OTP AR | PASS (automated) |
| Login `ع` control | Present; live switch did **not** flip strings in this emulator pass (needs deeper branding wire-up or profile locale) |
| Guest Discover AR | **NOT VERIFIED** live |
| Profile EN/AR segments | Exist for authenticated Profile |

**Arabic live QA: CONDITIONAL.**

---

## 11. Automated tests

```text
flutter test → +121 All tests passed
```

Includes prior 116 baseline + post-auth contract tests + updated filter panel test.  
Publishing, OTP, public profile, navigation allowlist, hero AA comparator: green.

`flutter analyze` on touched areas: no errors after go_router import fix on verify-email.

---

## 12. Emulator evidence

| Item | Value |
|------|-------|
| Device | `bidex_api34` / `emulator-5554` |
| Build | `2026-10-31 13:00:30` → `13:01:14` debug APK |
| Install | `13:01:14` → `13:01:26` Success |
| Screenshots | `docs/flutter/phase2_qa/` (`01_discover.png`, login, etc.) |
| Discover compact chrome | Map/List + All Egypt without large subtitle stack (UI dump) |
| Mansoura sheet this session | Re-tap blocked while stuck on Login mid-session; prior regression QA still valid |

---

## 13. Remaining issues

| # | Severity | Issue | Next |
|---|----------|-------|------|
| 1 | Medium | Login `ع` may not drive `localeCodeProvider` | Wire auth branding locale to same provider as Profile |
| 2 | Medium | Guest Home → Login (product gate) | Confirm product intent vs soft guest home |
| 3 | Low | Dense-city aggregate still gesture-sensitive under extreme overlap | Optional zoom-gate exact pins |
| 4 | Release | Publishing prod E2E still skipped | Staging Create Event |

---

## 14. Backend handoff

**None required** for these Flutter fixes. Admin Dashboard V2 remains server-owned.

---

## Distinction summary

- **Code:** Auth race, logo, friends avatar, Discover chrome, marker hits — implemented.
- **Tests:** 121/121 PASS.
- **Emulator:** Partial functional proof; Arabic live incomplete.
- **Release:** CONDITIONAL until staging auth+AR+publish E2E.
