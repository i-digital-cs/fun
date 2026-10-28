# Funnity V3 — Social Discovery Implementation Report

**Date:** 28 October 2026  
**Branch:** `feature/funnity-v3-social-home`  
**Overall verdict: CONDITIONAL** (Web PASS · Flutter not device-verified)

---

## 1. Reference verification

| Check | Result |
| --- | --- |
| Repo root | `/home/d14098/funnity.com` |
| Absolute path | `/home/d14098/funnity.com/docs/design/funnity-v3/references/APPROVED_SOCIAL_HOME.png` |
| Relative path | `docs/design/funnity-v3/references/APPROVED_SOCIAL_HOME.png` |
| Exists / readable | Yes |
| Format | PNG 1536×1024, 8-bit RGB/sRGB, ~1.76 MiB |
| Visual inspection | Completed — three-column social home (sidebar / main / rail), hero, event cards, people, purple/orange language |
| Git | Untracked until owner commits — **not ignored** |

Approved PNG was not regenerated or replaced.

---

## 2. Preserved work

- Web logo → `/` (public marketing home); Flutter logo → public site origin
- V2.1 navigation repairs, geographic discovery (Option B), friends, join/tickets, chat/notifications
- Public `/` remains marketing; authenticated Social Home is `/dashboard`
- No deploy / commit / push / reset in this pass

---

## 3. Architecture

| Surface | Route | Role |
| --- | --- | --- |
| Public marketing | `GET /` | Unchanged |
| Authenticated Social Home | `GET /dashboard` | V3 three-column discovery |
| APIs | Existing discovery + people + friendship | Unchanged contracts |

---

## 4. Files changed (high level)

### Web
- `resources/views/home/dashboard.blade.php` — hero + nearby (≤4) + people + worth + lg+ right rail
- `resources/views/components/dashboard/social-event-card.blade.php`
- `resources/views/components/dashboard/social-rail.blade.php`
- `resources/views/components/dashboard/sidebar.blade.php` — logo→`/`
- `resources/views/components/dashboard/topbar.blade.php` — V3 search + **location chip**
- `resources/views/components/community/people-suggestions.blade.php`
- `resources/css/app.css` — sidebar, hero min-height, overflow clip
- `lang/en|ar/dashboard.php`, `lang/en|ar/friends.php`
- Vite production rebuild (`public/build/*`)

### Flutter (out of Web acceptance scope)
- `mobile/lib/features/home/...`, `mobile/lib/core/config/env.dart`

### Tests / docs
- `tests/Feature/LogoOpensPublicHomeTest.php`, updated `HomePageTest.php` (incl. Arabic RTL shell)
- This report + screenshots under `docs/design/funnity-v3/screenshots/`

---

## 5. Web implementation (summary)

- **Left sidebar:** Home, Discover, My Events, Chats, Friends, Notifications, Saved, Create Event; promo; brand logo opens `/`
- **Main:** Hero (*Find people. Do what you love.* + mood search + city + chips), Events near you, People you may like to meet, Worth the trip (when data exists)
- **Right rail (lg+):** Your location, Upcoming, Host CTA, Live/up-next, Friends entry (**no fabricated communities**)
- **Cards:** Image-first, category, favorite form, schedule, place/distance when present, capacity, Free/price, View Event
- **RTL:** Arabic locale verified (`dir=rtl`)

Measured layout at 1440×900: CSS grid `848px 280px` (main + rail).

---

## 6. Flutter implementation (separate)

- Mobile-adapted Social Home; people before Worth the Trip; logo → public site
- **Not** device/emulator verified in this environment
- **Flutter verdict: CONDITIONAL** — code adapted; no device visual PASS

---

## 7. Regression tests (automated)

```
php artisan test --filter='HomePageTest|LogoOpensPublicHome|EventDiscovery|PeopleSuggestions|DiscoverNearby|UserLocation'
→ 31 passed (126 assertions)

Includes: Option B discovery, worth-the-trip dedupe, nationwide honesty,
logo→/, dashboard shell, Arabic RTL shell, location preference.
```

Flutter unit/layout tests were run in the prior implementation pass; **this acceptance pass did not re-run Flutter device suites** (Web-only scope).

---

## 8. Screenshots (evidence)

Local server: `APP_URL=http://127.0.0.1:8767`

| File | Notes |
| --- | --- |
| `docs/design/funnity-v3/screenshots/v3-social-home-1440.png` | Desktop first viewport — sidebar + hero + events + right rail + location chip |
| `docs/design/funnity-v3/screenshots/v3-social-home-1440-full.png` | Full page |
| `docs/design/funnity-v3/screenshots/v3-social-home-1440-mid.png` | Mid scroll — people + sticky rail |
| `docs/design/funnity-v3/screenshots/v3-social-home-1440-ar.png` | Arabic RTL |
| `docs/design/funnity-v3/screenshots/v3-social-home-1280.png` | 1280 |
| `docs/design/funnity-v3/screenshots/v3-social-home-768.png` | Tablet |
| `docs/design/funnity-v3/screenshots/v3-social-home-390.png` | Mobile web |
| `docs/design/funnity-v3/screenshots/v3-public-home-1440.png` | Public `/` remains separate |

Flutter device screenshots: **unavailable** here.

---

## 9. Visual comparison vs approved PNG (pre-acceptance)

| Aspect | Match | Notes |
| --- | --- | --- |
| Three-column shell | Strong | Evidence: 1440 screenshot |
| Hero + mood search + chips | Strong | Lighter trailing gradient so photo reads |
| Events near you cards | Strong | Real covers/titles; no fake friend avatars |
| People + Add friend | Strong | Mid-scroll; interests omitted until real schema |
| Right rail | Strong | Sticky; Friends instead of invented Communities |
| Illustrative distances / community counts | Not copied | Intentional honesty |

---

## 12. Web acceptance (final)

### 12.1 Visual comparison results

| Check | Result |
| --- | --- |
| Hierarchy (sidebar / hero / nearby / people / rail) | Pass |
| Card density (≤4 nearby on xl) | Pass |
| Sidebar proportions | Pass |
| Hero dimensions / photo wash | Pass (after soft gradient + min-height tweak) |
| Typography / spacing | Pass vs brand system (not pixel-identical to mock copy) |
| Event images | Pass (real media) |
| People cards | Pass |
| Right rail behavior (sticky on lg+) | Pass |
| Horizontal overflow @ 1440/1280/768/390/AR | Pass (measured `scrollWidth`) |
| Pixel-perfect mock content | N/A — real data; nationwide banner when city unset |

### 12.2 Navigation test results (browser)

| Flow | Result |
| --- | --- |
| Logo → public `/` (absolute home URL) | Pass — remains authenticated |
| Dashboard after visiting `/` | Pass |
| Friends nav | Pass |
| Browser Back / Forward | Pass |
| Create Event | Pass → `/events/create` |
| Event details from nearby card | Pass → `/events/{id}/…` |
| Topbar search (Enter) | Pass → `/events?q=…` |
| Guest `/dashboard` | Pass → `/login` |
| Intended-route / auth redirects | Pass (guest redirect); existing intended behavior preserved in prior V2.1 tests |

### 12.3 Functional test results

| Feature | Result |
| --- | --- |
| Nearby events | Pass (real cards) |
| Nationwide honesty (no city) | Pass — blue notice + generic subtitle |
| Worth the Trip | Pass when data exists (PHP Option B dedupe tests); empty for this demo session without city |
| People suggestions | Pass — section present; Add friend control enabled |
| Favorites | Pass — bookmark label toggles checkbox → POST; state persisted on reload |
| Location selection | Pass — modal opens via `[data-location-modal-open]` |
| Radius / city preference | Pass — `UserLocationPreferenceTest` |
| Upcoming participations | Pass — rail shows real upcoming join |
| Communities | N/A product — Friends entry only (honest) |

### 12.4 Responsive & localization

| Breakpoint / locale | Result |
| --- | --- |
| 1440 / 1280 three-column | Pass |
| 768 / 390 stack + bottom nav | Pass |
| Arabic RTL 1440 | Pass (`dir=rtl`, no overflow) |
| English LTR | Pass |

### 12.5 Confirmed fixes in this acceptance pass

1. **Nearby subtitle duplicate** — `nearby_sub_generic` no longer repeats the section title; honest nationwide copy (EN/AR).
2. **Topbar location chip** — pin + city/“Set location” opens location modal (reference parity).
3. **Hero photo wash** — lighter trailing gradient + taller min-height so lifestyle image reads.
4. **Hero find form** — flex row with nowrap CTA to avoid mid-width wrap.
5. **Horizontal overflow guard** — `overflow-x: clip` on `.dash-main` / main.
6. **Social circle copy** — removed meta “not a product surface” wording from UI.
7. **Laravel** — Arabic RTL shell assertion on dashboard.

### 12.6 Remaining Web defects / notes

1. Demo account without city shows nationwide notice — **expected Option B honesty**, not a layout bug.
2. Worth the Trip may be empty until location/discovery produces farther events.
3. People interest chips / fake distances from the mock are **not** rendered (no real interests schema / privacy).
4. Communities list from the mock is **not** shipped; Friends entry used instead.
5. Favorite bookmark UI uses existing form control (not a separate heart icon from the mock).

### 12.7 Verdicts

| Surface | Verdict |
| --- | --- |
| **Web authenticated Social Home** | **PASS** — ready for owner approval |
| **Flutter Social Home** | **CONDITIONAL** — adapted; no device visual verification |
| **Overall repo V3 status** | **CONDITIONAL** until Flutter device sign-off (optional follow-up) |

### 12.8 Owner approval readiness (Web)

**Yes** — Web implementation on `feature/funnity-v3-social-home` is ready for owner visual/product approval.  
Include `docs/design/funnity-v3/references/APPROVED_SOCIAL_HOME.png` when committing. Do not deploy until owner approves commit/push.

---

## 10. Remaining issues (cross-cutting)

1. Flutter device shots still missing.
2. Approved PNG + V3 docs remain untracked until commit.
3. Web PASS does not imply Flutter PASS.

---

## 11. Historical overall note

Prior implementation pass was CONDITIONAL. After this Web acceptance pass: **Web PASS**, overall still **CONDITIONAL** due to Flutter device gap only.
