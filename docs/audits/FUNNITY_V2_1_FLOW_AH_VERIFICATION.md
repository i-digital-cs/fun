# Funnity V2.1 — Flow A–H verification log

**Date:** 28 October 2026  
**Environment:** Linux desktop Flutter target only. No Android emulator (`emulator` not installed). No physical phone attached.

| Flow | Intent | Code status | Device result |
| --- | --- | --- | --- |
| A | Shell ↔ event detail Back | P0 stack hygiene | **NOT RUN** |
| B | Notification → Friends requests tab | `tab=requests` + FCM openAppPath | **NOT RUN** |
| C | Discover filters + Back restore | Riverpod filters; web query | **NOT RUN** |
| D | Guest → event → login → returnTo | `sanitizeReturnTo` + tests | **NOT RUN** (unit/feature only) |
| E | Create publish → detail → Back | post-create push pattern | **NOT RUN** |
| F | Deep link / openAppPath allowlist | helpers + unit tests | **NOT RUN** on device |
| G | FCM cold/background open | getInitialMessage + onMessageOpenedApp | **NOT RUN** |
| H | Modal / overlay dismiss vs Back | existing patterns | **NOT RUN** |

Automated only: `mobile/test/core/navigation/app_navigation_test.dart`, `tests/Feature/AuthIntendedRedirectTest.php`.

Do not treat this file as device sign-off.
