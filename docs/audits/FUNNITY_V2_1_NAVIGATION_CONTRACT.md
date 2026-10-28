# Funnity V2.1 — Navigation Contract

**Status:** Implemented alongside P0 navigation repair  
**Platforms:** Flutter (GoRouter) + Laravel Web (Blade / browser history)

## Verbs

| Verb | Flutter | Web | Use when |
| --- | --- | --- | --- |
| **push** | `context.push` | Normal `<a href>` / form GET that adds history | Drill-in: list → detail → chat / profile / settings |
| **replace** | `context.pushReplacement` / `go` same level | `redirect()` without intending to keep prior | Login↔register; chat → event without duplicate; auth completion |
| **go / reset** | `context.go` / `goBranch` | Full navigation to a primary destination | Shell tabs only; logout; post-auth landing; invalid-route fallback |
| **pop** | System / AppBar back when `canPop` | Browser Back | Dialogs/sheets first; then route |

## Rules

1. Never `go('/discover')` or `go('/home')` from a detail screen when the user expects Back — use `push` into detail, or restore query params when switching to Discover intentionally (search).
2. Shell tab re-tap may reset that tab (`goBranch(initialLocation: true)`). System Back on shell root = OS exit (do not rotate tabs).
3. Root overlays (`parentNavigatorKey`) hide bottom nav until pop — documented parity vs web (nav always visible).
4. Auth: stash allowlisted `returnTo` (Flutter query) / session `url.intended` (Web) through **all** gates (email, identity). After gates → `returnTo ?? defaultHome`.
5. Do not recreate `GoRouter` on auth watch — `refreshListenable` + `ref.read` only.
6. Deep link / FCM cold start: `openAppPath` → allowlisted path; if unauthenticated, login with `returnTo`; if `canPop` false after open, shell fallback `/home` or `/discover`.
7. Friends deep link: `/friends?tab=requests` opens Requests tab; `/friends?search=1` focuses search.
8. Post-create: `go('/my-events')` then `push('/events/:id')` — no orphan root-only detail.
9. Web: no global `history.back()` hack; discover filters live in the URL (`replaceState` for live typing is OK and does not stack undo steps).
10. Distance / geo labels are separate from navigation — see discovery audit.

## Allowlisted returnTo roots

Implemented in `mobile/lib/core/navigation/app_navigation.dart` (`sanitizeReturnTo`).
