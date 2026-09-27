# Flutter ↔ Web (Blade) Feature Parity Guide

**Purpose:** Keep **zero intentional gap** between the Laravel Blade web app and the Flutter client — every member/public screen, gate, CTA, and data field.

**Also read:** [`DESIGN.md`](../DESIGN.md) · [`docs/FLUTTER_BEST_PRACTICES.md`](FLUTTER_BEST_PRACTICES.md) · [`docs/FLUTTER_SEX_FIELD.md`](FLUTTER_SEX_FIELD.md) *(required sex: male/female/mix)* · [`docs/GOOGLE_OAUTH_SETUP.md`](GOOGLE_OAUTH_SETUP.md) · [`docs/SEO_OPS.md`](SEO_OPS.md) · [`docs/SEO_CWV_BUILD_PLAN.md`](SEO_CWV_BUILD_PLAN.md) · Postman [`docs/postman/`](postman/) · API `routes/api.php`

**Rule:** Pixel-perfect desktop layout is not required. **Same information hierarchy, same actions, same join states, same brand tokens, same account gates** are required. If web can do it, Flutter must be able to do it via API (or an explicit “web-only” note — admin only).

---

## 0. How to use this doc

For each screen:

1. Open the **Blade path** in the browser (reference).
2. Build / adjust the Flutter screen to match the **Must implement** list.
3. Wire the listed **API** endpoints.
4. Check the **Parity checklist** at the bottom before calling the screen done.

**Out of scope for member Flutter app:** `/admin/*` (platform admin stays web-only unless a separate admin app is requested).

---

## 0.1 Full-project parity contract (non-negotiable)

| Layer | Web (Blade) | Flutter must |
|-------|-------------|--------------|
| **Shell (logged in)** | `layouts/dashboard` — sidebar + topbar + mobile 5-tab | Same IA: Home · Discover · Create · My Events · Profile |
| **Shell (guest auth)** | `layouts/app` + ambient + glass | Ambient + glass cards; brand from API |
| **Branding** | Admin display name + logo | `GET /api/site` → name + logo on auth/identity/home |
| **App version** | Admin min / latest / force update | `GET /api/site` → `app` payload; animated splash (`SplashPage`) with site logo + force/soft update |
| **Auth** | Login/register + cinema + email code + forgot/reset | Same flows via Auth API (§2A) |
| **Gates** | email verified → identity → active | Same order using `/api/user` flags |
| **Discover / events** | Filters, rich cards, join states | Same filters + `join_action` (send **Bearer** on event GETs) |
| **Dashboard home** | Greeting, hero, categories+counts, nearby, safety, how-it-works, CTA | Compose from events/categories/user/participations |
| **Profile** | Photo, name, email, sex (male/female/mix), locale, password | `PUT /api/settings/profile` |
| **Identity** | Status chips, thumbs, 3 uploads, cinema | `GET/POST /api/identity` + document streams |
| **Safety** | Guidelines, report, blocks | l10n + `POST /api/reports` + blocks API |

**Do not ship Flutter screens that invent different CTAs, skip gates, or ignore `join_action`.**

---

## 0.2 Master API map (member)

| Area | Endpoints |
|------|-----------|
| Branding | `GET /api/site` |
| Auth | `POST /register` · `/login` · `/auth/google` · `/logout` · `/email/verify` · `/email/verify/resend` · `/forgot-password` · `/forgot-password/verify` · `/reset-password` |
| Me | `GET /api/user` (`email_verified`, `needs_email_verification`, `needs_identity_documents`, `has_identity_documents`, `identity_resubmit_required`, `sex`, `needs_sex`, `can_participate`, `has_password`, city…) |
| Profile | `GET /api/users/{publicId}` (public) · `PUT /api/settings/profile` · `PUT /api/settings/location` |
| Home | `GET /api/home/participations` · `GET /api/events` · `GET /api/categories` (includes `published_events_count`) · `GET /api/weather` |
| Catalog | `GET /api/cities` · `GET /api/categories` |
| Events | `GET/POST /api/events` · `GET/PUT /api/events/{id}` · `GET /api/my-events` · `GET /api/favorites` · `POST /api/events/{id}/favorite` · publish/cancel/complete · `GET /api/events/{id}/ticket` · `GET /api/events/now-playing` · search via `?q=` (show Searching while awaiting); covers: `image_url` (card) + `image_url_detail`; `slug` on event payloads |
| Join | `POST .../join` · `POST .../join/cancel` (withdraw pending) · `POST .../leave` · requests accept/reject · members promote/demote/transfer |
| Chat | `GET/POST /api/events/{id}/messages` |
| Ratings | `GET .../ratings` · `GET .../ratings/me` · `POST .../ratings` |
| Identity | `GET/POST /api/identity` · `GET /api/identity/documents/{id}` |
| Safety | `POST /api/reports` (`description` not `details`) · `GET /api/blocks` · block/unblock user |
| Notifications | `GET /api/notifications` · `GET /api/notifications/unread-count` · `POST /api/notifications/{id}/read` · `POST /api/notifications/read-all` · `POST/DELETE /api/device-tokens` (FCM) |

**Auth header:** Send `Authorization: Bearer {token}` on **all** authenticated calls **and** on public `GET /api/events` / `GET /api/events/{id}` so `join_action` is personalized.

---

## 1. Shells & navigation (match before individual pages)

### 1.1 Guest / public shell

| Web | Blade |
|-----|--------|
| Marketing home | `layouts/home.blade.php` + `x-community.*` |
| Auth & public flows | `layouts/app.blade.php` (ambient + glass navbar + footer) |

**Flutter must:**

- Aurora / ambient background + glass-style cards on login/register/safety/public discover.
- Brand colors: indigo primary, orange→amber CTAs, emerald trust, canvas `#f5f7fb`.
- EN / AR + `dir` / `TextDirection` sync.

### 1.2 Authenticated member shell

| Web | Blade |
|-----|--------|
| Dashboard layout | `layouts/dashboard.blade.php` |
| Sidebar | `components/dashboard/sidebar.blade.php` |
| Topbar | `components/dashboard/topbar.blade.php` |
| Mobile nav | `components/dashboard/mobile-nav.blade.php` |

**Web reference pages using this shell:** dashboard, discover, event detail/create/edit/mine/requests/members/chat, profile, location, identity, blocks, report, account deactivated.

**Flutter must (responsive):**

| Breakpoint | Chrome |
|------------|--------|
| Phone (&lt; ~850px) | Bottom nav 5 tabs: **Home · Discover · Create · My Events · Profile** |
| Wide | Left sidebar (same items + Blocked users + location card) |

**Create** tab: opens create-event (or identity gate if `!can_participate`) — not a permanent empty tab.

**Profile** tab: **Profile settings** + links to location / identity / blocks.

**Topbar:** live search → suggestions via `GET /search/suggest` (wifi **Searching** while typed query awaits API); titles use BiDi isolate (`bidi_dir` / `bidiTextDirection`); Enter / See all → Discover; locale; **notifications bell** → `/notifications` (orange unread dot); avatar menu (Profile · Location · Identity · Blocks · Logout).
*(Topbar chat icon → `/chats` with unread dot when messages arrive.)*

---

## 2. Screen inventory — member / public

### 2.1 Marketing home `/`

| | |
|--|--|
| **Route** | `home` → `/` |
| **Blade** | `home/index.blade.php` |
| **Guest?** | Yes (also shows extra blocks when authed) |
| **API** | `GET /api/site`, `GET /api/events` (+ Bearer), `GET /api/categories`, `GET /api/home/participations` (auth) |

**Sections (order):**

1. Hero (`x-community.hero`) — brand, CTA into discover/create  
2. **Weather + now playing** — equal `lg:grid-cols-2` inside a home glass panel. Weather: indigo/orange/slate theme glass (`weather-panel`). Now playing: light indigo glass list (`x-dashboard.now-playing`). Weather: user city when set, else first active city; guests see sample city + sign-in link. Now playing: live / up next / just wrapped (`GET /api/events/now-playing`)  
3. Participations (`x-community.participations`) — **auth + canParticipate only**  
4. Discover strip (`x-community.discover`) — rich event cards  
5. Trust (`x-community.trust`)

**Flutter note:** Phone apps often skip a separate marketing home and open **Login** or **Member Home (`/dashboard`)**. If you keep a guest home, mirror these four sections. Prefer matching **dashboard** for logged-in users (next screen).

---

### 2.2 Member dashboard home `/dashboard`

| | |
|--|--|
| **Route** | `dashboard` → `/dashboard` |
| **Blade** | `home/dashboard.blade.php` |
| **Auth** | Required |
| **API** | `GET /api/site`, `GET /api/user`, `GET /api/events` (nearby + Bearer), `GET /api/categories` (`published_events_count`), `GET /api/cities`, `GET /api/home/participations`, `GET /api/weather` |

**Sections (order) — implement all:**

1. **Greeting** — “Good morning/afternoon/evening, {firstName} 👋” + subtitle + orange **Create event**  
2. **Hero** — dark photo/gradient block, titles “Find people. / Do what you love.”, **Find events** (indigo) → Discover, trust chips; ambient **espiral** glow anchored end-side (`x-ui.espiral`, amber) — decorative only, not a loader  
3. **Your participations** — same card style as before, scaled down; **4-up** preview (max 4) from `GET /api/home/participations`; overflow → My Events; empty → Discover CTA
4. **What are you into?** — category grid (emoji + name + count) → Discover filtered by category  
5. **Happening near you** — compact cards + weather + **now-playing** (live/next/previous) + starting soon + safety  
6. **How {brand} works** — 3 steps  
7. **CTA banner** — “Can’t find your activity?” + orange create  

**Card style here:** compact dashboard card (indigo soft “View event”), **not** the full Discover rich card.

**My events `/my-events`:** three blocks — **Waiting for acceptance** (pending), **Hosting** (your own), **Events you joined** (memberships that aren’t yours) + View ticket.

**Favorites `/favorites`:** bookmarked events (sidebar after My Events); toggle via amber bookmark on cards/detail (`POST /api/events/{id}/favorite`).

### 2.2a Weather card *(required — dashboard + Flutter home)*

| | |
|--|--|
| **Web** | `x-dashboard.weather-card` → `.weather-panel` on `/` (marketing home) and `/dashboard` (nearby sidebar) — brand glass gradients (indigo / orange sunny / slate cloudy) |
| **API** | `GET /api/weather?city_id=` (optional; defaults to `user.city_id`) · Bearer |
| **Source** | [Open-Meteo](https://open-meteo.com/) (no API key) using city `latitude` / `longitude` |
| **Cache** | ~30 minutes per city + locale |

**Response shape:**

```json
{
  "data": {
    "provider": "open_meteo",
    "city": { "id": 1, "name": "Cairo", "latitude": 30.04, "longitude": 31.23 },
    "local_time": "14:05",
    "local_date": "WED 09-23",
    "temperature": 36,
    "high": 42,
    "low": 28,
    "range": "42°/28°",
    "condition": "clear",
    "label": "Sunny",
    "icon": "sun",
    "theme": "sunny",
    "forecast": [
      { "day": "Thu", "condition": "rain", "icon": "rain", "high": 30, "low": 22 }
    ]
  }
}
```

**Flutter:** same card on Home when `user.city_id` set; else CTA → location settings. Themes: `sunny` / `cloudy` / `rain`. Icons: `sun` / `cloud` / `rain`.

### 2.2b Now playing *(required — home + dashboard)*

| | |
|--|--|
| **Web** | `x-dashboard.now-playing` on `/` and `/dashboard` — light indigo/white glass panel (brand mark, live orange pulse, cover rows + EQ / chevron) |
| **API** | `GET /api/events/now-playing?city_id=` (optional; prefers user/city, falls back nationwide) |
| **Brand** | Site logo + indigo/orange accents (DESIGN.md tokens — not dark offer-card, not Spotify green) |
| **Flutter** | `NowPlayingCard` + `EventsRepository.nowPlaying()` — same light glass; wire into home/dashboard beside weather |

**Payload:** `{ running: Event|null, next: Event[], previous: Event[] }`

- **running** — published, started, not yet ended (or open-ended within 6h of start)  
- **next** — upcoming published (up to 2)  
- **previous** — recently finished (up to 2)  

**Flutter UI:** light glass rows — live row with animated EQ bars; next/previous with chevron; tap → event detail. Use cover `image_url`, title, category + city / Up next · time as subtitle.

---

### 2.3 Discover `/events`

| | |
|--|--|
| **Route** | `events.index` → `/events` |
| **Blade** | `events/index.blade.php` |
| **Guest?** | Yes (Create CTA only if authed + canParticipate) |
| **API** | `GET /api/events?q&category_id&city_id&distance_km&when` (+ **Bearer** for join_action) |

**Must implement:**

- Title + subtitle  
- Filter panel: search, category, city, distance, Apply / Clear  
- Prompt to set location if `city_id` missing (link to location settings)  
- Grid/list of **rich** cards: `components/events/card.blade.php`  
- Empty / nationwide-fallback messaging when applicable  
- Orange Create when allowed  

**Rich card (web + Flutter):** cover (category-matched stock or upload), category chip, orange price chip (`50.00 EGP / person`), schedule, title, venue, members box + bar, pills `X/Y` · currency · `%`, **View Event**. Blade `.event-3d`; Flutter `EventCard` uses the same geometry (media 148px, glass from top 96 with end-corner swoosh, indigo `#312E81` title). Hot-restart Flutter after pull.

**Live search (typed → API wait):** when the user types in search (topbar, home, Discover `q`, admin users), show the wifi **Searching** indicator **only** while the debounced request is in flight — never as a page/spinner loader for navigation.

**System loader:** page/content loading uses Uiverse circle loader — web `x-ui.loader` / full-page `x-ui.page-loader` (link + form navigation); Flutter `FunLoader` via `AsyncBody` and screens.

| | |
|--|--|
| **Web suggest** | `GET /search/suggest?q=&type=events\|users` |
| **API list** | `GET /api/events?q=` (Flutter) |
| **Min chars** | 2 · debounce ~350ms |
| **UI** | wifi arcs + lowercase “Searching” / “جاري البحث” (`ui.searching`) |

Flutter: overlay/inline Searching animation on query change; hide when results arrive or input cleared.

---

### 2.4 Event detail `/events/{id}/{slug}`

| | |
|--|--|
| **Route** | `events.show` → `/events/{id}/{slug}` (bare `/events/{id}` **301** to canonical). Member actions: `/events/{id}/{slug}/edit|chat|ticket|members|requests|…` via `event_route()` |
| **Blade** | `events/show.blade.php` |
| **API** | `GET /api/events/{id}` (+ Bearer), join/leave, ratings; response includes `creator`, `join_action`, `creator_rating`, `slug`, `image_url` (card/optimized), `image_url_detail` (larger OG/detail variant) |
| **Web SEO** | Public published/completed pages emit meta description, Open Graph, Twitter card, canonical, **Event** + **BreadcrumbList** JSON-LD (`App\Support\Seo::forEvent`). Draft/cancelled remain `noindex`. Share previews use detail `coverUrl()`. |

**Layout hierarchy (same as web):**

1. Cover + category chip + city + title + **status badge** + **favorite bookmark** + ambient espiral on cover (`x-ui.espiral`, indigo, corner-anchored)  
2. Date/time chip + venue chip + **Open in maps** when `maps_open_url` present  
3. OSM map preview when `has_place_pin` (web Leaflet viewer; Flutter opens external maps)  
4. Description  
5. Requirements box  
6. Cost / free + members count chips  
7. **Primary actions** (join state — see §3); when `joined` include **View ticket**  
8. Owner tools (edit / publish / cancel / complete / requests / members)  
9. Report event + block host  
10. Organizer rating summary  
11. Rate form (when `can_rate`)  
12. Ratings list  

---

### 2.4a Event ticket `/events/{id}/ticket` *(required)*

| | |
|--|--|
| **Route** | `events.ticket` (web) |
| **Blade** | `events/ticket.blade.php` + `x-events.ticket` |
| **API** | `GET /api/events/{id}/ticket` (Bearer, member only) |
| **Authz** | `EventPolicy::viewTicket` — user must be an `EventMember` |

Holographic perforated pass (web CSS class `event-ticket`). Show when `join_action === joined` from event detail and from My events. Ticket stage may include ambient espiral behind the pass (decorative).

**API response:**

```json
{
  "data": {
    "code": "K7M2P9QX4R",
    "role": "member",
    "pass_label": "Event pass",
    "title": "Sunset Meetup",
    "date_label": "May 14th 2026",
    "date_iso": "2026-05-14T18:00:00+03:00",
    "venue_line": "Riverside Park, Cairo",
    "barcode_shadow": "0px 0 0 1px #000, 3px 0 0 1px #000, …",
    "member": { "id": 12, "name": "Alex" },
    "event": { "id": 5, "title": "Sunset Meetup", "status": "published" }
  }
}
```

`code` is a **globally unique** 10-character pass id stored on `event_members.ticket_code` (issued when the member joins; never reused). The decorative barcode pattern is derived from that code so each ticket looks different.

**Flutter must:**

1. Screen `/events/:id/ticket` (full-screen centered ticket).
2. Fetch `GET /api/events/{id}/ticket` when opening; 403 → pop back.
3. Visual parity with web: perforated card, holographic feel (CustomPainter / assets OK), header “TICKET”, pass label, title, date, venue, role + code, decorative barcode.
4. Entry points: Event detail when joined; My events row action **View ticket**.
5. EN/AR via l10n (`events.view_ticket`, etc.).

---

### 2.5 Create event `/events/create`

| | |
|--|--|
| **Route** | `events.create` / `events.store` |
| **Blade** | `events/create.blade.php` + `events/_form.blade.php` |
| **Gate** | `canParticipate()` else redirect identity |
| **API** | `POST /api/events` (multipart if image) |

**Form fields (all):**

| Field | Required |
|-------|----------|
| title | yes |
| description | no |
| category_id | yes |
| city_id | yes |
| venue | no |
| latitude / longitude | no (map pin) |
| maps_url | no (Google Maps / OSM share link — **no Google API key**; server + clients parse coords from the URL) |
| starts_at | yes |
| ends_at | no |
| max_participants | yes |
| **sex** (audience: `male` \| `female` \| `mix`) | **yes** |
| requirements | no |
| expected_cost | no |
| cost_per_person | no |
| image (jpeg/png/webp) | no |

**Place on map (free):** Web create/edit uses **Leaflet + OpenStreetMap tiles** (CDN, no credentials). **Type a venue** → `GET /api/places/suggest` (Photon, Nominatim fallback) drops the pin. Tap map to drop/drag pin, or paste a Maps share link. Short Google links are resolved via `POST /api/maps/resolve` (follows redirects, no Google API key). Placeholder venue text (`Venue` / `المكان`) is replaced with a free Nominatim reverse-geocode label (or city name). Flutter create/edit matches (venue autocomplete + pin + link). Detail **Open in maps** / map tap opens **Google Maps** (`https://www.google.com/maps?q={lat},{lng}`) — web wraps preview in `<a>`; Flutter uses `url_launcher` + Android VIEW `<queries>`.

**API location fields (every event):**

```json
{
  "venue": "Corniche · Cairo",
  "location_label": "Corniche · Cairo",
  "location": {
    "label": "Corniche · Cairo",
    "venue": "Corniche · Cairo",
    "venue_raw": "Venue",
    "latitude": 30.0266,
    "longitude": 31.2234,
    "maps_url": "https://www.openstreetmap.org/…",
    "maps_open_url": "https://www.google.com/maps?q=30.0444,31.2357",
    "has_place_pin": true,
    "city": { "id": 1, "name": "Cairo", "latitude": 30.04, "longitude": 31.23 }
  },
  "latitude": 30.0266,
  "longitude": 31.2234,
  "maps_url": "…",
  "maps_open_url": "…",
  "has_place_pin": true
}
```

**Open in maps:** always opens **Google Maps** (`https://www.google.com/maps?q=lat,lng`) from pin coords (or a parsable stored link rewritten to Google). Tapping the in-app OSM preview (web + Flutter) also opens Google Maps. If the user pastes a link then moves the pin, the pin wins and `maps_url` is rewritten to match.

CTA: orange create/save. After create → show or edit draft + publish.

---

### 2.6 Edit event `/events/{id}/edit`

| | |
|--|--|
| **Blade** | `events/edit.blade.php` + `_form` |
| **API** | `PUT /api/events/{id}` |
| **Authz** | Owner / can update only |

Same fields as create. Keep existing image if not replaced.

---

### 2.7 My events `/my-events`

| | |
|--|--|
| **Blade** | `events/mine.blade.php` |
| **API** | `GET /api/my-events` |

**Must:** list **Hosting** (`created_by = me`) and **Joined** (member, not owner) separately — title, date, members, city, **status pill** + relationship badge (`your_own` / `joined`); pending section; empty state + Create / Discover CTAs; tap → detail; **View ticket** action per row (§2.4a). API `GET /api/my-events` includes `join_action` (`owned` | `joined` | …); optional `?section=hosting|joined`.

---

### 2.7a Favorites `/favorites` *(required)*

| | |
|--|--|
| **Route** | `events.favorites` (web) |
| **Blade** | `events/favorites.blade.php` + `x-events.favorite-bookmark` |
| **API** | `GET /api/favorites` · `POST /api/events/{id}/favorite` (Bearer, `can_participate`) |
| **Authz** | Active participant; event must be viewable |

Bookmark UI (web CSS `.ui-bookmark`) on Discover / dashboard / home cards and event detail. Amber when favorited.

**List / detail payload:** when authenticated, event resources include `is_favorited` (bool).

**Toggle response:**

```json
{
  "message": "Added to favorites.",
  "is_favorited": true,
  "event_id": 12
}
```

**Flutter must:**

1. Screen `/favorites` — same rich cards as Discover; empty → Discover CTA.
2. Bookmark control on event cards + detail (optimistic toggle OK; sync with `POST .../favorite`).
3. Sidebar / drawer item **Favorites** directly under **My Events**.
4. EN/AR via l10n (`events.favorites`, `events.favorite`, `events.unfavorite`, …).

---

### 2.8 Join requests `/events/{id}/requests`

| | |
|--|--|
| **Blade** | `events/requests.blade.php` |
| **API** | `GET .../requests`, `POST .../accept`, `POST .../reject` · join via `POST /api/events/{id}/join` (web `events.join`) |
| **Authz** | Owner / can review |

List pending requesters with Accept / Reject.

**Organizer notify (web + API, same service):** when someone requests to join, the event **creator** receives `JoinRequestReceivedNotification` via **mail + database inbox + FCM** (when a device token exists) **+ realtime** Pusher `join.requested` on private `App.Models.User.{creator}` (web + Flutter sweet toast + notify badge). CTA → event requests. Failures are logged and do **not** block the join.

**Applicant notify on accept:** `JoinRequestAcceptedNotification` → **database inbox + FCM** + realtime Pusher event `join.accepted` on private `App.Models.User.{id}` (web + Flutter sweet toast + notify badge).

Flutter: poll `GET /api/notifications` for join request / accept as fallback; same `POST .../join` and accept endpoints trigger notifies + live badge bumps.

---

### 2.8a Notifications inbox `/notifications` *(required)*

| | |
|--|--|
| **Blade** | `notifications/index.blade.php` |
| **Web** | `GET /notifications` · mark-read · mark-all-read |
| **API** | `GET /api/notifications` · `GET /api/notifications/unread-count` · `POST /api/notifications/{id}/read` · `POST /api/notifications/read-all` |
| **Push** | `POST /api/device-tokens` `{token, platform: android\|ios\|web}` · `DELETE /api/device-tokens` `{token}` |
| **Authz** | Authenticated + `account.active` |

In-app list of database notifications (`join_request`, `join_accepted`). Unread badge on web topbar / community navbar **and Flutter topbar / sidebar** (`/notifications`, polled unread-count). Flutter: open inbox from bell; tap `join_request` → `/events/:id/requests`, `join_accepted` → event detail.

**FCM push (outside app):** join request, join accept, and chat messages (`JoinRequestReceivedNotification`, `JoinRequestAcceptedNotification`, `ChatMessagePushNotification`). Flutter registers via `POST /api/device-tokens` after login (requires `google-services.json` — see `mobile/docs/FIREBASE_PUSH.md`). Logout → `DELETE`. Chat push body does **not** include message text (privacy). Inbox still stores join request/accept.

**Foreground vs background (Flutter):** when the app is **open**, FCM still arrives but **no system tray banner** is shown (`onMessage` → in-app sweet toast + badge; iOS `setForegroundNotificationPresentationOptions(alert: false)`). When the app is **backgrounded / killed**, the OS shows the tray notification. Pusher toasts remain the primary live path while open (FCM is a deduped fallback).

**Android tray branding:** status-bar icon is `ic_stat_funity` (white silhouette — Android requirement) tinted indigo (`#4F46E5`); channel `funnity_alerts`. Server FCM sets `android.notification.icon` / `color` / `channel_id`. Expanded large image uses site logo URL only when it is a bitmap (PNG/JPEG/WebP), not SVG.

**Firebase (server):** set `FIREBASE_PROJECT_ID` + `FIREBASE_CREDENTIALS`. Without them, mail + in-app still work; FCM is no-op (logged).

---

### 2.9 Members `/events/{id}/members`

| | |
|--|--|
| **Blade** | `events/members.blade.php` |
| **API** | `GET .../members`, promote, demote, transfer |

Show roles (owner/admin/member). Owner tools: promote, demote, transfer ownership. Member can leave from detail (not owner).

---

### 2.10 Chat `/events/{id}/chat`

| | |
|--|--|
| **Blade** | `events/chat.blade.php` |
| **Inbox** | `GET /chats` → `chats/index.blade.php` · `GET /api/chats` · Flutter `/chats` — **same arrangement on web + Flutter:** event cover, event date/time, compact relative last-message time (`now`/`Nm`/`Nh`/`Nd`), **Recent \| Unread** segment, search (icon toggle), separate tinted cards, unread badge beside preview. Payload: `image_url`, `starts_at`. Thread: long-press → **copy message** (Flutter); web report link in bubble footer. |
| **Toast** | Web + Flutter: private `App.Models.User.{id}` `.chat.message` → Uiverse toast (suppressed on that event’s chat). **Also:** `.join.requested` (organizer toast + notify badge) and `.join.accepted` (applicant toast + notify badge). **Transport:** production uses **Pusher Channels** (`BROADCAST_CONNECTION=pusher`); Reverb remains an optional VPS alternative. Flutter: `GET /api/site` → `broadcast` + `POST /api/broadcasting/auth` (Sanctum); poll `/api/chats` every 15s as fallback (60s unread sync when WS live). **While app open:** no FCM system banner — same sweet toast UI (Pusher primary, FCM data as deduped fallback). **FCM tray:** only when backgrounded/killed. SEC-PRIV-002: Flutter chat toasts never echo message body. |
| **Unread** | `event_chat_reads.last_read_message_id`; mark on open thread / `POST /api/events/{id}/messages/read` |
| **API** | `GET /api/events/{id}/messages` (page 1 = **newest first** for pagination), `POST .../messages` |
| **Access** | Joined members only |

**Must:** message list (author, time, body), dark Uiverse-style composer (textarea + attach affordance + Chat chip + send plane), report message link, link back to event.  
**Display order:** chat UI must show **oldest → newest** (web reverses the API page; Flutter sorts by `id` ascending) with newest at the **bottom** near the composer.  
**Realtime thread (web + Flutter):** both subscribe to private `events.{id}.chat` / `.message.posted` (web Echo; Flutter `ChatRealtimeClient.subscribeEventChat`). Flutter keeps a slow poll (60s) as safety net when the socket is live, and 8s poll when realtime is unavailable.  
**Thread UI (web + Flutter parity):** mine/theirs row with **avatar** (photo or initial) — tap avatar/name → `/u/{publicId}`; **solid indigo** mine bubbles + white text; time **inside** bubble (`g:i A`); author only on theirs; system messages centered italic; AppBar/header **Chat** + event subtitle + **View Event**. Web `POST` JSON + Echo payload include `user.public_id` + `user.profile_image_path`.  
Composer visual matches across platforms (`ChatComposer` dark bar). User-channel Pusher toasts remain for inbox (suppressed while viewing that event’s chat).

---

### 2.11 Login `/login`

| | |
|--|--|
| **Blade** | `auth/login.blade.php` (`x-auth.access-shell`) |
| **API** | `POST /api/login` · `POST /api/auth/google` → token |

Glass card with **site name + logo** (from admin settings). Email + password, remember, **indigo→violet** CTA, **Continue with Google**, links to register + forgot password.

**After submit (web cinematic):** digits orbit → verify ring 1–100 → celebrate → redirect.  
**Flutter:** optional matching animation; always handle API flags:

| Response field | Flutter action |
|----------------|----------------|
| `needs_email_verification` / `email_verified: false` | Navigate to **Verify email** (code was emailed) |
| `needs_identity_documents` | Navigate to Identity |
| else | Member home / dashboard |

`user` also includes `email_verified`, `email_verified_at`, `needs_identity_documents`.

---

### 2.12 Register `/register`

| | |
|--|--|
| **Blade** | `auth/register.blade.php` |
| **API** | `POST /api/register` |

Name, email, **sex** (`male` \| `female` \| `mix`, required), password, confirmation, locale. **Orange→amber** CTA. Site branding on card.

**Response:** Sanctum `token` + `email_verified: false` + `user.sex`. A **4-digit email code** is sent automatically.

**Next screen:** Verify email (`§2.12a`) — do **not** open Identity until email is verified.

Google sign-up may leave `sex` null (`needs_sex: true`); user must set it on Profile settings.

---

### 2.12a Verify email `/email/verify` *(new — required)*

| | |
|--|--|
| **Blade** | `auth/verify-email.blade.php` |
| **API** | `POST /api/email/verify` · `POST /api/email/verify/resend` |
| **Auth** | Bearer (Sanctum) |

**UI:** 4 digit OTP boxes (auto-advance / paste), Resend, site logo + name. Web uses the same access-code theater animation.

| Call | Body | Success |
|------|------|---------|
| Verify | `{ "code": "1234" }` | `email_verified: true` + `user` → then Identity or Home |
| Resend | `{}` | `{ "message", "email" }` |

Codes expire in **15 minutes**. Gate Identity / create / join until `email_verified`.

---

### 2.12b Forgot password `/forgot-password` *(new — required)*

| | |
|--|--|
| **Blade** | `auth/forgot-password.blade.php` |
| **API** | `POST /api/forgot-password` |

Email field + send code. Response always looks successful (no email enumeration).

```json
{ "email": "user@example.com" }
```

→ then **Verify reset code** screen.

---

### 2.12c Verify reset code `/forgot-password/verify` *(new — required)*

| | |
|--|--|
| **Blade** | `auth/verify-reset-code.blade.php` |
| **API** | `POST /api/forgot-password/verify` · `POST /api/forgot-password` (resend = same as request) |

```json
{ "email": "user@example.com", "code": "1234" }
```

**Success:**
```json
{
  "message": "...",
  "reset_token": "<opaque>",
  "expires_in_minutes": 15
}
```

Store `reset_token` + email for the next screen. Do **not** treat this as a login token.

---

### 2.12d Reset password `/reset-password` *(new — required)*

| | |
|--|--|
| **Blade** | `auth/reset-password.blade.php` |
| **API** | `POST /api/reset-password` |

```json
{
  "email": "user@example.com",
  "reset_token": "<from verify>",
  "password": "NewPassword1!",
  "password_confirmation": "NewPassword1!"
}
```

**Success:** Sanctum `token` + `user` (user is logged in). Route by `needs_identity_documents` / home.

---

### 2.13 Identity `/identity`

| | |
|--|--|
| **Blade** | `identity/create.blade.php` (dashboard shell) |
| **API** | `GET /api/identity` · `POST /api/identity` · `GET /api/identity/documents/{id}` |
| **Auth** | Bearer + **email verified** first |
| **Web UX** | Site logo/name, status chip, optional existing thumbs via **folder attachment** card, 3 uploads with folder picker (click to open → choose file), cinematic submit (orbit → 1–100 → celebrate) |

**Must implement (Flutter = same states as Blade):**

| UI block | When |
|----------|------|
| Brand header (app name + logo) | Always |
| Title + help text | Always |
| Status chip | `pending_upload` / `submitted` / `resubmit_required` |
| Amber resubmit banner | `resubmit_required` |
| Grid of 3 images (front / back / selfie) | Any uploaded docs exist — load via document `url` with Bearer |
| Upload form (3 required image fields) | `can_upload` / `needs_upload` |
| Live local previews before submit | Upload mode |
| Orange **Submit** / **Submit new documents** | Upload mode |
| Success copy + Home CTA | Submitted + cannot re-upload |
| Cinematic / progress on submit | Optional polish; API is source of truth |

**Cycle (Option 1 — immediate activate + admin resubmit):**

1. New user (`pending_documents`, no files) → upload form only  
2. After upload → `active`; show thumbnails of ID front / back / selfie (owner-only stream)  
3. Active users cannot re-upload until admin asks  
4. Admin **Request documents again** → `pending_documents`; show previous images + banner + replace form  
5. Re-upload all three → `active` again  

**`GET /api/identity` response (parity):**

```json
{
  "status": "pending_documents",
  "email_verified": true,
  "needs_email_verification": false,
  "needs_upload": true,
  "can_upload": true,
  "needs_identity_documents": true,
  "resubmit_required": false,
  "has_identity_documents": false,
  "documents": [
    { "type": "id_front", "id": null, "url": null, "uploaded": false, "mime_type": null, "size": null },
    { "type": "id_back", "id": null, "url": null, "uploaded": false, "mime_type": null, "size": null },
    { "type": "selfie", "id": null, "url": null, "uploaded": false, "mime_type": null, "size": null }
  ],
  "user": { "id": 1, "status": "pending_documents", "needs_identity_documents": true, "..." : "..." }
}
```

**`POST /api/identity`:** multipart `id_front`, `id_back`, `selfie` (jpeg/png/webp).  
Success returns same shape as `GET` + `message` (account activated).

**Document images:** `GET /api/identity/documents/{id}` with Bearer — display in Image with auth headers / downloaded bytes (not a public URL).

Gate create/join/chat while `needs_identity_documents`. Routing order: email verify → identity → home.

**Attachments UI (web + Flutter):** any file pick / existing attachment list uses the animated **folder card** (`x-ui.attachment-folder`): **click the folder** (or Choose file) → native picker opens; after pick, show **filename + size status**, **image thumbnail preview**, and open folder cards with count badge. Same pattern on identity, event cover, profile photo, site logo, admin identity review. Flutter: folder-open metaphor with clear selected-media preview — not a plain system file dialog only.

---

### 2.14 Account deactivated `/account/deactivated`

| | |
|--|--|
| **Blade** | `account/deactivated.blade.php` |

Block app usage; show message + logout. No member APIs.

---

### 2.15a Public profile `/u/{public_id}` *(required)*

| | |
|--|--|
| **Web** | `GET /u/{publicId}` → `profile/public.blade.php` + `x-profile.public-card` (Uiverse card) |
| **API** | `GET /api/users/{publicId}` (public, throttled) |
| **ID** | Immutable sequential `users.public_id` = `Funity-F` + 6 digits starting at `Funity-F001007` (then `F001008`…) |

**Public fields only:** `public_id`, `name`, `initials`, `profile_image_path`, `role_label`, `city.name`, stats (`hosted_events`, `joined_events`, `rating_average`, `rating_count`), recent hosted published events. **Never** email / private prefs.

**Entry points:** event organizer card → profile; settings “Open public profile”; Flutter Profile hub + `/u/:publicId` route (guest-browsable).

`GET /api/user` and auth payloads include `public_id`. Event `creator` includes `public_id` + `profile_url`.

---

### 2.15 Profile settings `/settings/profile` *(new — required)*

| | |
|--|--|
| **Blade** | `profile/edit.blade.php` (dashboard shell) |
| **API** | `PUT /api/settings/profile` (JSON or multipart) |
| **Auth** | Bearer |

**Fields:** photo (optional), name, email, **sex** (`male` \| `female` \| `mix`, required), locale, current_password (if changing password), password + confirmation.

| Behavior | Detail |
|----------|--------|
| Sex | Required; values `male`, `female`, `mix` |
| Change password | Send `current_password` + `password` + `password_confirmation` |
| Change email | Sets `email_verified: false`, sends new code → Verify email screen (`email_changed: true` in response) |
| Photo | `photo` file part, or `remove_photo: true` |

**Entry points:** Profile tab (bottom nav), topbar avatar menu → Profile.

Also keep Location as a separate screen (`§2.16`).

---

### 2.16 Location settings `/settings/location`

| | |
|--|--|
| **Blade** | `profile/location.blade.php` (+ location modal on web) |
| **API** | `PUT /api/settings/location` |

City select + distance_km (5 / 10 / 25 / 50). Orange save. Feeds Discover/Home filters.

---

### 2.17 Safety guidelines `/safety`

| | |
|--|--|
| **Blade** | `safety/guidelines.blade.php` |
| **API** | Static copy (l10n) |

Readable guidelines page; linked from dashboard safety card.

---

### 2.18 Report `/report`

| | |
|--|--|
| **Blade** | `safety/report.blade.php` |
| **API** | `POST /api/reports` |

Params: `reportable_type` (`user` \| `event` \| `message`), `reportable_id`, reason, **`description`** (optional — not `details`).

---

### 2.19 Blocks `/settings/blocks`

| | |
|--|--|
| **Blade** | `safety/blocks.blade.php` |
| **API** | `GET /api/blocks`, `POST/DELETE /api/users/{id}/block` |

List blocked users + unblock. Block host from event detail.

---

## 2A. Auth API quick reference (Flutter)

Base: `{APP_URL}/api` · codes are **4 digits** · expire **15 minutes**.

| Flow | Method | Path | Auth | Body |
|------|--------|------|------|------|
| Site branding | `GET` | `/site` | — | — → `{ display_name, logo_url, app }` (see §2C) |
| Register | `POST` | `/register` | — | name, email, password, password_confirmation, **sex** (`male`\|`female`\|`mix`), locale? |
| Login | `POST` | `/login` | — | email, password |
| Google Sign-In | `POST` | `/auth/google` | — | `id_token`, locale? → Sanctum token (email already verified; `needs_sex` if sex unset) |
| Logout | `POST` | `/logout` | Bearer | — |
| Me | `GET` | `/user` | Bearer | — → includes `sex`, `needs_sex` |
| Resend verify code | `POST` | `/email/verify/resend` | Bearer | — |
| Verify email | `POST` | `/email/verify` | Bearer | `code` |
| Forgot password | `POST` | `/forgot-password` | — | `email` |
| Verify reset code | `POST` | `/forgot-password/verify` | — | `email`, `code` → `reset_token` |
| Reset password | `POST` | `/reset-password` | — | `email`, `reset_token`, `password`, `password_confirmation` |
| Update profile | `PUT` | `/settings/profile` | Bearer | name, email, **sex**, locale?, current_password?, password?, photo?, remove_photo? |
| Home participations | `GET` | `/home/participations` | Bearer | memberships + pending_requests |
| Identity status | `GET` | `/identity` | Bearer | — |
| Upload identity | `POST` | `/identity` | Bearer | multipart: `id_front`, `id_back`, `selfie` |
| Identity image | `GET` | `/identity/documents/{id}` | Bearer | stream |

**Post-auth routing order:**

1. `needs_email_verification` → Verify email  
2. else `needs_identity_documents` → Identity  
3. else → Home (`/dashboard`)

**Google Sign-In (Flutter):** use `google_sign_in` with `serverClientId` = **Web** `GOOGLE_CLIENT_ID`, send `idToken` to `POST /api/auth/google`. New users skip email verify (`email_verified: true`) and usually land on Identity. Setup (Console clients + Android SHA-1): [`docs/GOOGLE_OAUTH_SETUP.md`](GOOGLE_OAUTH_SETUP.md).

Use `GET /api/site` for logo/name **and** app version / force-update on splash (same as admin Site settings). See **§2B** (login/identity samples) and **§2C** (version check).

Site branding for auth UI: admin **display name** + **logo**. Official Funity mark (Brand Identity System) ships at `public/branding/funity-mark.svg` and is attached into site settings storage so `GET /api/site` → `logo_url` is always available for Flutter. Admin can replace via **Site settings** attachment.

---

## 2B. Flutter code samples (login + identity)

Copy-paste oriented samples for a Dio + Riverpod app. Adapt package names to your project. Persist the Sanctum token (secure storage preferred) and attach `Authorization: Bearer …` on authenticated calls.

### Packages

```yaml
dependencies:
  dio: ^5.7.0
  flutter_riverpod: ^2.6.1
  package_info_plus: ^8.0.0   # for §2C version check
  url_launcher: ^6.3.0        # open store URLs
  image_picker: ^1.1.0        # identity uploads
```

### Dio client (sketch)

```dart
final dioProvider = Provider<Dio>((ref) {
  final dio = Dio(BaseOptions(
    baseUrl: const String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'https://www.funnity.com/api',
    ),
    headers: {'Accept': 'application/json'},
  ));
  dio.interceptors.add(InterceptorsWrapper(
    onRequest: (options, handler) async {
      final token = await ref.read(tokenStoreProvider).read();
      if (token != null) {
        options.headers['Authorization'] = 'Bearer $token';
      }
      handler.next(options);
    },
  ));
  return dio;
});
```

### Post-auth routing helper

```dart
String routeAfterAuth(Map<String, dynamic> user) {
  if (user['needs_email_verification'] == true ||
      user['email_verified'] == false) {
    return '/email/verify';
  }
  if (user['needs_identity_documents'] == true) {
    return '/identity';
  }
  return '/home';
}
```

### Login (Dio + Riverpod)

```dart
final authRepositoryProvider = Provider(AuthRepository.new);

class AuthRepository {
  AuthRepository(this.ref);
  final Ref ref;

  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    final res = await ref.read(dioProvider).post('/login', data: {
      'email': email,
      'password': password,
    });
    final data = res.data as Map<String, dynamic>;
    final token = data['token'] as String?;
    if (token != null) {
      await ref.read(tokenStoreProvider).write(token);
    }
    return data;
  }
}

final loginControllerProvider =
    AsyncNotifierProvider<LoginController, void>(LoginController.new);

class LoginController extends AsyncNotifier<void> {
  @override
  Future<void> build() async {}

  Future<String> submit(String email, String password) async {
    state = const AsyncLoading();
    state = await AsyncValue.guard(() async {
      final data = await ref.read(authRepositoryProvider).login(
            email: email,
            password: password,
          );
      final user = Map<String, dynamic>.from(data['user'] as Map);
      return routeAfterAuth(user);
    });
    return state.valueOrNull ?? '/login';
  }
}
```

**UI:** glass card, site name/logo from `GET /api/site`, email + password, indigo→violet CTA, then `context.go(await controller.submit(...))`.

### Identity status + upload

```dart
final identityRepositoryProvider = Provider(IdentityRepository.new);

class IdentityRepository {
  IdentityRepository(this.ref);
  final Ref ref;

  Future<Map<String, dynamic>> fetch() async {
    final res = await ref.read(dioProvider).get('/identity');
    return Map<String, dynamic>.from(res.data as Map);
  }

  Future<Map<String, dynamic>> upload({
    required String idFrontPath,
    required String idBackPath,
    required String selfiePath,
  }) async {
    final form = FormData.fromMap({
      'id_front': await MultipartFile.fromFile(idFrontPath, filename: 'id_front.jpg'),
      'id_back': await MultipartFile.fromFile(idBackPath, filename: 'id_back.jpg'),
      'selfie': await MultipartFile.fromFile(selfiePath, filename: 'selfie.jpg'),
    });
    final res = await ref.read(dioProvider).post('/identity', data: form);
    return Map<String, dynamic>.from(res.data as Map);
  }

  /// Document image bytes (not a public URL).
  Future<Uint8List> documentBytes(int documentId) async {
    final res = await ref.read(dioProvider).get<List<int>>(
      '/identity/documents/$documentId',
      options: Options(responseType: ResponseType.bytes),
    );
    return Uint8List.fromList(res.data ?? const []);
  }
}
```

**UI gates from `GET /api/identity`:**

| Flag | UI |
|------|-----|
| `can_upload` / `needs_upload` | Show 3 pickers + Submit |
| `resubmit_required` | Amber banner + previous thumbs + replace form |
| submitted / active, `!can_upload` | Thumbs + success / wait copy, no form |

Load each doc with `documentBytes(id)` when `documents[].uploaded == true`.

---

## 2C. App version / force update (admin → Flutter)

### Admin (web)

Super admin → **Site settings** (`/admin/settings`):

| Field | Meaning |
|-------|---------|
| Android / iOS **latest** | Current store version |
| Android / iOS **minimum** | Anything older is always blocked |
| **Force update** | When on, anything older than **latest** is also blocked (cannot dismiss) |
| Store URLs | Play Store / App Store links |
| Update message EN/AR | Shown on the update dialog |

Saving clears server cache; clients see new values on the next `GET /api/site`.

### Public API

`GET /api/site` (optional `?locale=ar` or `Accept-Language`):

```json
{
  "display_name": "Fun",
  "logo_url": "https://…/logo.png",
  "app": {
    "force_update": false,
    "message": "A new version is available.",
    "android": {
      "latest": "1.2.0",
      "min": "1.0.0",
      "store_url": "https://play.google.com/store/apps/details?id=…"
    },
    "ios": {
      "latest": "1.2.0",
      "min": "1.0.0",
      "store_url": "https://apps.apple.com/app/…"
    }
  }
}
```

### Client rules (implement exactly)

On **cold start** (before login UI):

1. `PackageInfo.fromPlatform()` → `version` (e.g. `1.0.3`).
2. `GET /api/site`.
3. Pick `app.android` or `app.ios` from `Platform.isIOS`.
4. Semver compare (numeric segments):

```
if current < min          → FORCE (block app)
else if force_update && current < latest → FORCE
else if current < latest  → SOFT (optional “Update” / “Later”)
else                      → OK
```

5. FORCE: non-dismissible dialog → only **Update** → `launchUrl(store_url)`.
6. SOFT: dismissible dialog with Update + Later.
7. If `/api/site` fails: **do not** block the app (fail open); retry later.

Bump `version:` in `pubspec.yaml` (and store listings) whenever you ship; admin sets **latest** to match.

### Flutter implementation sample

```dart
import 'dart:io';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

enum UpdateKind { none, soft, force }

class AppUpdateInfo {
  const AppUpdateInfo({
    required this.kind,
    this.message,
    this.storeUrl,
  });
  final UpdateKind kind;
  final String? message;
  final String? storeUrl;
}

int compareSemver(String a, String b) {
  List<int> parts(String v) =>
      v.split('.').map((e) => int.tryParse(e) ?? 0).toList();
  final pa = parts(a);
  final pb = parts(b);
  final n = pa.length > pb.length ? pa.length : pb.length;
  for (var i = 0; i < n; i++) {
    final x = i < pa.length ? pa[i] : 0;
    final y = i < pb.length ? pb[i] : 0;
    if (x != y) return x.compareTo(y);
  }
  return 0;
}

Future<AppUpdateInfo> checkForUpdate(Dio dio) async {
  try {
    final locale = PlatformDispatcher.instance.locale.languageCode;
    final res = await dio.get('/site', queryParameters: {'locale': locale});
    final app = Map<String, dynamic>.from(res.data['app'] as Map);
    final platform = Platform.isIOS ? app['ios'] : app['android'];
    final latest = '${platform['latest']}';
    final min = '${platform['min']}';
    final storeUrl = platform['store_url'] as String?;
    final forceFlag = app['force_update'] == true;
    final message = app['message'] as String?;

    final info = await PackageInfo.fromPlatform();
    final current = info.version;

    if (compareSemver(current, min) < 0) {
      return AppUpdateInfo(kind: UpdateKind.force, message: message, storeUrl: storeUrl);
    }
    if (forceFlag && compareSemver(current, latest) < 0) {
      return AppUpdateInfo(kind: UpdateKind.force, message: message, storeUrl: storeUrl);
    }
    if (compareSemver(current, latest) < 0) {
      return AppUpdateInfo(kind: UpdateKind.soft, message: message, storeUrl: storeUrl);
    }
  } catch (_) {
    // fail open
  }
  return const AppUpdateInfo(kind: UpdateKind.none);
}

Future<void> openStore(String? url) async {
  if (url == null || url.isEmpty) return;
  final uri = Uri.parse(url);
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}
```

Wire in `main` / splash:

```dart
final update = await checkForUpdate(ref.read(dioProvider));
if (update.kind == UpdateKind.force) {
  // show blocking dialog; barrierDismissible: false
} else if (update.kind == UpdateKind.soft) {
  // optional dialog
}
// then continue to auth / home
```

**Admin API (optional):** `GET/PUT /api/admin/settings` (super admin) includes the same version fields as the web form.

---

## 3. Join action states (must match web exactly)

From `App\Enums\EventJoinAction` — drive buttons on cards + event detail:

| Value | UI |
|-------|-----|
| `request_to_join` | Orange **Request to join** |
| `request_pending` | Muted “Request pending” + **Cancel join** (`POST /api/events/{id}/join/cancel`, web `events.join.cancel`) |
| `owned` | Indigo **Your event** (creator) — discover/search/cards; detail uses owner lifecycle actions |
| `joined` | Emerald “Joined” + **View ticket** + Chat + Members (+ **Leave** via cancel/stop button if not owner) |
| `…` | Owner **Cancel event** also uses the same cancel/stop control (web `x-ui.cancel-btn`: idle label → focus/press shows **Stop**) |

**Cancel / close pattern (web + Flutter):** destructive dismiss actions — leave event, **cancel join request**, cancel event, reject join request, modal Cancel/Close — use the **Cancel → Stop** control (`x-ui.cancel-btn`: idle label → focus/press shows **Stop**). Web uses a compact dark/orange button (readable inside overflow cards). Flutter: same two-state control for leave, cancel join, cancel event, close sheet, reject.

**Confirm dialogs (web + Flutter):** any “Are you sure…?” (leave, cancel join, cancel event, reject, delete, identity resubmit) uses the branded confirm modal with the **tapping hand** animation (`x-ui.confirm-dialog` / `x-ui.tapping-hand`) — never the native browser `confirm()`. Flutter: modal/sheet with the same hand animation + message + Cancel/Stop + confirm CTA.
| `request_rejected` | Amber rejected + allow request again |
| `event_full` | Muted “Event full” |
| `not_available` | Hide join / guest → login |

Backend is authority; never invent local join logic that contradicts `join_action` from API.

---

## 4. Event lifecycle actions (owner)

| Status | Actions on detail |
|--------|-------------------|
| `draft` | Edit, **Publish**, Cancel |
| `published` | Edit, Cancel, **Complete**, join requests, manage members |
| `cancelled` / `completed` | View mostly; ratings when completed + eligible |

API: `POST .../publish|cancel|complete`.

---

## 5. Shared UI components to port

| Web component | Flutter equivalent |
|---------------|-------------------|
| `x-events.card` | 3D glass card + cover image + members `X / Y` |
| `x-dashboard.event-card` | Same as `x-events.card` |
| `x-community.ambient` | AmbientBackground |
| `x-ui.espiral` | Soft spiral/particle glow on **dashboard hero**, **event cover**, **ticket stage** only (not global; respect reduced motion) |
| `x-dashboard.mobile-nav` | 5-tab bottom bar |
| `x-dashboard.sidebar` | Wide-layout drawer/sidebar |
| Orange gradient CTA | Primary conversion button |
| Indigo→violet CTA | Secondary brand (login, chat) |
| Form fields | rounded-xl, indigo focus |
| Mixed AR/EN titles | `bidi_dir()` / `.bidi-text` (web) · `bidiTextDirection()` (Flutter) — isolate by first strong letter |

Copy strings from `lang/en/*.php` and `lang/ar/*.php` into Flutter l10n (ARB).

---

## 6. Account gates (parity with middleware)

| User status | Flutter behavior |
|-------------|------------------|
| Guest | Login/register/forgot-password; public discover/detail read-only join → login |
| Logged in + email **not** verified | Verify-email screen only (resend allowed); block Identity / create / join |
| `pending_documents` + email verified | Allow browse; block create/join/chat → Identity |
| `active` + canParticipate | Full member flows |
| `deactivated` | Deactivated screen only |

Mirror web: email verified → then `account.active` + `canParticipate()` for create/join/report/rate.

`GET /api/user` / login / register expose: `email_verified`, `needs_email_verification`, `needs_identity_documents`, `has_identity_documents`, `identity_resubmit_required`, `sex`, `needs_sex`.

---

## 7. Admin screens (web-only — do not block member app)

Documented for awareness; **not required** in member Flutter:

- `/admin` dashboard, users, identity moderation, events, calendar, chat moderation, reports, cities, categories, settings, audit  

Use web admin at `https://www.funnity.com/admin`.

---

## 8. Suggested Flutter route map

| Flutter route | Matches web |
|---------------|-------------|
| `/login` | `/login` |
| `/register` | `/register` |
| `/email/verify` | `/email/verify` |
| `/forgot-password` | `/forgot-password` |
| `/forgot-password/verify` | `/forgot-password/verify` |
| `/reset-password` | `/reset-password` |
| `/home` | `/dashboard` |
| `/discover` | `/events` |
| `/events/create` | `/events/create` |
| `/events/:id` | `/events/{id}/{slug}` (web; bare id 301s) |
| `/events/:id/ticket` | `/events/{id}/ticket` |
| `/events/:id/edit` | `/events/{id}/edit` |
| `/chats` | `/chats` |
| `/notifications` | `/notifications` |
| `/events/:id/chat` | `/events/{id}/chat` |
| `/events/:id/requests` | `/events/{id}/requests` |
| `/events/:id/members` | `/events/{id}/members` |
| `/my-events` | `/my-events` |
| `/favorites` | `/favorites` |
| `/identity` | `/identity` |
| `/u/:publicId` | `/u/{publicId}` |
| `/settings/profile` | `/settings/profile` |
| `/settings/location` | `/settings/location` |
| `/blocks` | `/settings/blocks` |
| `/report` | `/report` |
| `/safety` | `/safety` |
| `/deactivated` | `/account/deactivated` |

---

## 9. Parity checklist (per screen)

Copy this into PRs:

- [ ] Same primary sections / fields as Blade  
- [ ] Same CTAs and join/lifecycle states  
- [ ] EN + AR strings + RTL layout  
- [ ] Design tokens (indigo / orange CTA / radii / canvas)  
- [ ] API wired; errors mapped to user-readable messages  
- [ ] Account gates respected  
- [ ] Empty / loading / error states  
- [ ] Compared side-by-side with web on phone width  

---

## 10. Known gap themes (prioritize)

Close these in Flutter against **current** web + API (no “skip for later” on member features):

1. **Auth + identity + profile** — full stack with `GET /api/site` branding + version check (§2C); **required sex** (male/female/mix) on register + profile (web+Flutter+API)  
2. **Shell** — 5-tab + dashboard IA; Profile hub  
2a. **Public profiles** — `/u/{public_id}` card + API (web+Flutter)  
3. **Discover** — filters including `when`; **always send Bearer** for `join_action`  
4. **Event detail** — creator card, ratings, report/block, full owner matrix, **View ticket**  
5. **Event ticket** — holographic pass UI + `GET /api/events/{id}/ticket` (§2.4a)  
6. **Favorites** — bookmark toggle + `/favorites` list (§2.7a)  
7. **Cards** — rich 3D glass + cover + members; price chip uses Egyptian pound (`EGP` / `ج.م`); same card on home discover + dashboard  
8. **Create/edit** — all fields including **required sex/audience** (`male`/`female`/`mix`) + image + free place pin + **place text suggest** (`GET /api/places/suggest`); join respects `sex_mismatch`  
9. **Members** — promote / demote / transfer + report/block links  
10. **Chat** — inbox `/chats` + thread list/post; web + Flutter share layout (Recent/Unread, cards, avatars, indigo mine bubbles, in-bubble time); live `events.{id}.chat` + user-channel toasts (Pusher/Reverb); Flutter poll fallback; avatars → public profile; View Event CTA
11. **Home** — participations + weather glass panel + category counts + nearby; equal-width weather + light `NowPlayingCard` (API ready, compose on home)  
12. **Event place** — pin picker / Maps share link / place autocomplete without Google credentials (web+Flutter+API); open → Google Maps  
13. **Web technical SEO (web-only crawl surface)** — `/robots.txt`, `/sitemap.xml`, OG/JSON-LD on home · discover · event show · public profile · safety; Flutter unchanged (API IDs).  
14. **Cover media variants** — web serves WebP srcset; API `image_url` is card-sized, `image_url_detail` for full hero/share. Flutter should prefer `image_url` on lists and can use `image_url_detail` on detail/share.  
15. **Web event slugs** — public web URL is `/events/{id}/{slug}` with 301 from `/events/{id}`; **all member web actions** use `/events/{id}/{slug}/…` (edit, chat, ticket, members, requests, join, …) via `event_route()` / `event_show_url()`. Legacy `/events/{id}/edit|chat|…` **301** to the slug form. API stays `/api/events/{id}/…` (Flutter GoRouter can stay `/events/:id`).  
16. **Publish SEO soft-warn** — `POST /api/events/{id}/publish` may include `seo_warning: true` + `message` when description is empty (still publishes). Flutter can toast the message; non-blocking.
17. **Notifications** — join request/accept → internal inbox (+ email on request; Pusher/FCM-while-open sweet toast + badge). Chat messages → FCM push (tray when backgrounded) + in-app toast when open. Flutter: inbox screen + FCM token register (§2.8a).
18. **Mixed AR/EN titles (BiDi)** — web `bidi_dir` + `.bidi-text` isolate; Flutter `bidiTextDirection` on cards, detail, search, chats, now-playing, participations. Prefer Arabic-first titles when seeding mixed content.

**Explicitly web-only (OK to skip in member app):** `/admin/*` only (except shared branding / app version via `GET /api/site`). Web HTTP error pages (404/500/…) use a branded runner screen — Flutter should map status codes to native empty/error UI, not load those Blade pages. Crawl/SEO head tags and sitemap are web-only.

---

## 11. Reference URLs (local QA)

Base: `https://www.funnity.com`

| Page | URL |
|------|-----|
| Marketing | `/` |
| Dashboard | `/dashboard` |
| Discover | `/events` |
| Login | `/login` |
| Register | `/register` |
| Verify email | `/email/verify` |
| Forgot password | `/forgot-password` |
| Reset password | `/reset-password` |
| Create | `/events/create` |
| My events | `/my-events` |
| Favorites | `/favorites` |
| Notifications | `/notifications` |
| Event ticket | `/events/{id}/ticket` |
| Profile | `/settings/profile` |
| Location | `/settings/location` |
| Identity | `/identity` |
| Safety | `/safety` |
| Blocks | `/settings/blocks` |
| Report | `/report` |
| Robots | `/robots.txt` |
| Sitemap | `/sitemap.xml` |

API base: `https://www.funnity.com/api`  
Public branding: `GET /api/site`

---

*Last updated: 2026-09-24 — Phase 4 SEO ops (`seo:health`, publish soft-warn, SEO_OPS.md); Phase 3 slugs; Phase 2 CWV; Phase 1 SEO.*
