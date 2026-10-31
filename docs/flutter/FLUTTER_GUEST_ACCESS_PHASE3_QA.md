# Flutter Guest Access — Phase 3 QA

**Date:** 2026-10-31  
**Device:** `bidex_api34` / `emulator-5554` / `com.funnity.app`  
**Branch:** `main` @ `b3212a3` (+ uncommitted guest + typography work)  
**Admin / Laravel / prod DB:** Not modified

---

## Verdict: **CONDITIONAL**

| Area | Result |
|------|--------|
| Guest splash → Home | **PASS** (emulator) |
| Guest Home public content + Login/Sign up | **PASS** (emulator) |
| Guest Discover Map/List | **PASS** (emulator) |
| Guest public event detail | **PASS** (emulator) |
| Guest Join → Login (`returnTo`) | **PASS** (emulator UI; OTP/login completion not exercised — no prod writes) |
| Guest Create → Login | **PASS** (emulator) |
| Logout → Home | **PASS** (code); emulator login/logout skipped (no test credentials / no prod users) |
| Arabic guest Home + Discover | **PASS** (emulator) |
| Automated tests | **PASS** — **133/133** |
| Security (drafts / tickets / chats gated) | **PASS** (allowlist + router) |
| Full authenticated return + explicit re-confirm | **CONDITIONAL** — covered by unit tests + gate UX; live OTP round-trip not run |

Release readiness: **CONDITIONAL** — browse-first guest path is verified on emulator; complete login→returnTo→manual join needs a non-prod account in a later pass.

---

## 1. Root cause of forced login

1. **`MainShell._onBranchTap`** only allowed guests on Discover (`index == 1`). Home (`0`) pushed `/login`.
2. **`isGuestBrowsableLocation`** omitted `/home`, so the router treated Home as protected.
3. **Unauthenticated splash** redirected to `/discover` instead of `/home`.
4. **Logout** navigated to `/login` instead of preserving public browse on `/home`.

---

## 2. Files modified

| Path | Change |
|------|--------|
| `mobile/lib/core/navigation/app_navigation.dart` | `/home` guest-browsable; `guestLandingLocation`; central `requireAuth` |
| `mobile/lib/app/router.dart` | Splash → Home; session-restore failure allows public browse |
| `mobile/lib/features/home/presentation/main_shell.dart` | Guests may open Home + Discover tabs |
| `mobile/lib/features/home/presentation/home_page.dart` | Guest greeting, Login/Sign up, create/location gates |
| `mobile/lib/features/home/presentation/widgets/dash_topbar.dart` | Logout → `/home`; clear unread badges |
| `mobile/lib/features/events/presentation/event_detail_page.dart` | Guest Join CTA → `requireAuth` (no auto-join) |
| `mobile/lib/features/profile/presentation/public_profile_page.dart` | Guest Add friend → `requireAuth` + `returnTo` |
| `mobile/lib/features/discover/presentation/discover_page.dart` | Empty-state Create uses `requireAuth` |
| `mobile/lib/features/splash/presentation/splash_page.dart` | Continue browsing after session restore failure |
| `mobile/lib/l10n/app_en.arb` / `app_ar.arb` | Guest greeting / CTA / continue browsing |
| `mobile/test/core/navigation/guest_access_test.dart` | New |
| `mobile/test/app/guest_router_redirect_test.dart` | New |
| `docs/FLUTTER_WEB_PARITY.md` | §10.32 guest browse-first |
| `docs/flutter/guest_access_qa/*` | Emulator evidence |

---

## 3. Guest routes

Allowed without auth (`isGuestBrowsableLocation`):

- `/home`, `/discover`
- `/events/:id` (detail only)
- `/u/:publicId`
- Legal: `/privacy-policy`, `/terms`, `/support`, `/safety/guidelines`, `/account-deletion`

Still gated (redirect → `/login?returnTo=…`):

- `/create`, `/my-events`, `/profile`, `/chats`, `/friends`, `/notifications`, `/favorites`
- `/events/:id/{chat,requests,members,edit,ticket}`
- Settings / identity / blocks / report / DMs

---

## 4. Protected actions

Central helper: **`requireAuth(context, auth, returnTo: …)`**.

Used for: Create Event, Join (guest CTA), Add friend (guest profile), Discover empty Create, Home location settings when guest.

Does **not** auto-join, auto-friend, or auto-publish after authentication — user returns to the destination and confirms.

---

## 5. Auth return behavior

Existing `sanitizeReturnTo` / `loginLocation` / `goAfterSuccessfulAuthWithRouter` / OTP `returnTo` chaining preserved.

Examples covered by tests:

- `/events/604`, `/create`, `/u/Funity-F######`, `/home`

---

## 6. Security

- Guests cannot open ticket/chat/edit/members via router allowlist.
- Backend remains authoritative; Flutter does not bypass API authZ.
- Draft/private event access still depends on API 403/404 (no client bypass).
- **Backend gaps:** none newly observed for public Home/Discover/event show in this pass. If a public list/detail endpoint unexpectedly returns 401 for guests, escalate to Cursor Server (not seen on emulator for published Discover items).

---

## 7. Automated tests

```text
flutter pub get
flutter analyze   # pre-existing infos/warnings only; no guest regressions
flutter test      # 133/133 All tests passed!
```

New coverage: guest allowlist, protected denylist, `requireAuth` navigation, `returnTo` for create/event/profile, Arabic guest strings, landing constant `/home`.

---

## 8. Emulator evidence

Folder: `docs/flutter/guest_access_qa/`

| Step | Evidence | Result |
|------|----------|--------|
| Fresh guest → Home | `01_guest_home.png` | Welcome to Funnity; Log in / Sign up; no forced login modal |
| Home → Discover map | `02_guest_discover.png` | Map + aggregates; guest bottom nav |
| Create Event → Login | `03_guest_create_login.png` | Login theater |
| Discover List | `04_guest_discover_list.png` | Public event cards + View Event |
| Public event detail | `05_guest_event.png` / `05b_*.xml` | Detail + Request to join |
| Join → Login | `06_guest_join_login.png` | Login (returnTo preserved in code path) |
| Arabic Home | `07_guest_home_ar.png` | أهلاً بك في فنيتي; RTL CTAs |
| Arabic Discover | `08_guest_discover_ar.png` | اكتشف + map aggregates |

Not run on emulator (policy): live login/OTP against production, logout-with-session, intentional draft/private probes that need auth.

---

## 9. Arabic / typography

Tajawal (bundled) + RTL guest Home/Discover verified. No new font overrides.

---

## 10. Remaining issues

1. Live **login → returnTo → explicit Join** needs a non-production account.
2. **Logout → Home** code path ready; emulator confirmation pending authenticated session.
3. Discover Map/List chrome still shows some English product labels (`Map`/`List`/`All Egypt`) — content, not guest-gate regressions.
4. Communities remain deferred (no member Communities API).

---

## 11. Policy summary

**Browse first. Login only when interaction is required.**
