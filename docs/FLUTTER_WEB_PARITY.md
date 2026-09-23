# Flutter ↔ Web (Blade) Feature Parity Guide

**Purpose:** Keep **zero intentional gap** between the Laravel Blade web app and the Flutter client — every member/public screen, gate, CTA, and data field.

**Also read:** [`DESIGN.md`](../DESIGN.md) · [`docs/FLUTTER_BEST_PRACTICES.md`](FLUTTER_BEST_PRACTICES.md) · [`docs/GOOGLE_OAUTH_SETUP.md`](GOOGLE_OAUTH_SETUP.md) · Postman [`docs/postman/`](postman/) · API `routes/api.php`

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
| **Auth** | Login/register + cinema + email code + forgot/reset | Same flows via Auth API (§2A) |
| **Gates** | email verified → identity → active | Same order using `/api/user` flags |
| **Discover / events** | Filters, rich cards, join states | Same filters + `join_action` (send **Bearer** on event GETs) |
| **Dashboard home** | Greeting, hero, categories+counts, nearby, safety, how-it-works, CTA | Compose from events/categories/user/participations |
| **Profile** | Photo, name, email, locale, password | `PUT /api/settings/profile` |
| **Identity** | Status chips, thumbs, 3 uploads, cinema | `GET/POST /api/identity` + document streams |
| **Safety** | Guidelines, report, blocks | l10n + `POST /api/reports` + blocks API |

**Do not ship Flutter screens that invent different CTAs, skip gates, or ignore `join_action`.**

---

## 0.2 Master API map (member)

| Area | Endpoints |
|------|-----------|
| Branding | `GET /api/site` |
| Auth | `POST /register` · `/login` · `/auth/google` · `/logout` · `/email/verify` · `/email/verify/resend` · `/forgot-password` · `/forgot-password/verify` · `/reset-password` |
| Me | `GET /api/user` (`email_verified`, `needs_email_verification`, `needs_identity_documents`, `has_identity_documents`, `identity_resubmit_required`, `can_participate`, `has_password`, city…) |
| Profile | `PUT /api/settings/profile` · `PUT /api/settings/location` |
| Home | `GET /api/home/participations` · `GET /api/events` · `GET /api/categories` (includes `published_events_count`) |
| Catalog | `GET /api/cities` · `GET /api/categories` |
| Events | `GET/POST /api/events` · `GET/PUT /api/events/{id}` · `GET /api/my-events` · publish/cancel/complete |
| Join | `POST .../join|leave` · requests accept/reject · members promote/demote/transfer |
| Chat | `GET/POST /api/events/{id}/messages` |
| Ratings | `GET .../ratings` · `GET .../ratings/me` · `POST .../ratings` |
| Identity | `GET/POST /api/identity` · `GET /api/identity/documents/{id}` |
| Safety | `POST /api/reports` (`description` not `details`) · `GET /api/blocks` · block/unblock user |

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

**Topbar:** search → Discover; locale; avatar menu (Profile · Location · Identity · Blocks · Logout).  
*(Web bell icon is decorative → My Events — do not invent a notifications API.)*

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
2. Participations (`x-community.participations`) — **auth + canParticipate only**  
3. Discover strip (`x-community.discover`) — rich event cards  
4. Trust (`x-community.trust`)

**Flutter note:** Phone apps often skip a separate marketing home and open **Login** or **Member Home (`/dashboard`)**. If you keep a guest home, mirror these four sections. Prefer matching **dashboard** for logged-in users (next screen).

---

### 2.2 Member dashboard home `/dashboard`

| | |
|--|--|
| **Route** | `dashboard` → `/dashboard` |
| **Blade** | `home/dashboard.blade.php` |
| **Auth** | Required |
| **API** | `GET /api/site`, `GET /api/user`, `GET /api/events` (nearby + Bearer), `GET /api/categories` (`published_events_count`), `GET /api/cities`, `GET /api/home/participations` |

**Sections (order) — implement all:**

1. **Greeting** — “Good morning/afternoon/evening, {firstName} 👋” + subtitle + orange **Create event**  
2. **Hero** — dark photo/gradient block, titles “Find people. / Do what you love.”, **Find events** (indigo) → Discover, trust chips  
3. **What are you into?** — category grid (emoji + name + count) → Discover filtered by category  
4. **Happening near you** — compact cards (`x-dashboard.event-card`) + safety card (indigo gradient)  
5. **How {brand} works** — 3 steps  
6. **CTA banner** — “Can’t find your activity?” + orange create  

**Card style here:** compact dashboard card (indigo soft “View event”), **not** the full Discover rich card.

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

**Rich card must show:** cover, category chip, Free/price chip, urgency chip, schedule, title, venue, members progress, join-state banner, full-width orange **View event**.

---

### 2.4 Event detail `/events/{id}`

| | |
|--|--|
| **Route** | `events.show` |
| **Blade** | `events/show.blade.php` |
| **API** | `GET /api/events/{id}` (+ Bearer), join/leave, ratings; response includes `creator`, `join_action`, `creator_rating` |

**Layout hierarchy (same as web):**

1. Cover + category chip + city + title + **status badge**  
2. Date/time chip + venue chip  
3. Description  
4. Requirements box  
5. Cost / free + members count chips  
6. **Primary actions** (join state — see §3)  
7. Owner tools (edit / publish / cancel / complete / requests / members)  
8. Report event + block host  
9. Organizer rating summary  
10. Rate form (when `can_rate`)  
11. Ratings list  

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
| starts_at | yes |
| ends_at | no |
| max_participants | yes |
| requirements | no |
| expected_cost | no |
| cost_per_person | no |
| image (jpeg/png/webp) | no |

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

**Must:** list owned/membership events — title, date, members, city, **status pill**; empty state + Create CTA; tap → detail.

---

### 2.8 Join requests `/events/{id}/requests`

| | |
|--|--|
| **Blade** | `events/requests.blade.php` |
| **API** | `GET .../requests`, `POST .../accept`, `POST .../reject` |
| **Authz** | Owner / can review |

List pending requesters with Accept / Reject.

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
| **API** | `GET /api/events/{id}/messages`, `POST .../messages` |
| **Access** | Joined members only |

**Must:** message list (author, time, body), composer, report message link, link back to event.  
Web uses realtime; Flutter may poll until websockets are wired — document polling interval in app.

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

Name, email, password, confirmation, locale. **Orange→amber** CTA. Site branding on card.

**Response:** Sanctum `token` + `email_verified: false`. A **4-digit email code** is sent automatically.

**Next screen:** Verify email (`§2.12a`) — do **not** open Identity until email is verified.

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
| **Web UX** | Site logo/name, status chip, optional existing thumbs, 3 uploads with live preview, cinematic submit (orbit → 1–100 → celebrate) |

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

---

### 2.14 Account deactivated `/account/deactivated`

| | |
|--|--|
| **Blade** | `account/deactivated.blade.php` |

Block app usage; show message + logout. No member APIs.

---

### 2.15 Profile settings `/settings/profile` *(new — required)*

| | |
|--|--|
| **Blade** | `profile/edit.blade.php` (dashboard shell) |
| **API** | `PUT /api/settings/profile` (JSON or multipart) |
| **Auth** | Bearer |

**Fields:** photo (optional), name, email, locale, current_password (if changing password), password + confirmation.

| Behavior | Detail |
|----------|--------|
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
| Site branding | `GET` | `/site` | — | — → `{ display_name, logo_url }` |
| Register | `POST` | `/register` | — | name, email, password, password_confirmation, locale? |
| Login | `POST` | `/login` | — | email, password |
| Google Sign-In | `POST` | `/auth/google` | — | `id_token`, locale? → Sanctum token (email already verified) |
| Logout | `POST` | `/logout` | Bearer | — |
| Me | `GET` | `/user` | Bearer | — |
| Resend verify code | `POST` | `/email/verify/resend` | Bearer | — |
| Verify email | `POST` | `/email/verify` | Bearer | `code` |
| Forgot password | `POST` | `/forgot-password` | — | `email` |
| Verify reset code | `POST` | `/forgot-password/verify` | — | `email`, `code` → `reset_token` |
| Reset password | `POST` | `/reset-password` | — | `email`, `reset_token`, `password`, `password_confirmation` |
| Update profile | `PUT` | `/settings/profile` | Bearer | name, email, locale?, current_password?, password?, photo?, remove_photo? |
| Home participations | `GET` | `/home/participations` | Bearer | memberships + pending_requests |
| Identity status | `GET` | `/identity` | Bearer | — |
| Upload identity | `POST` | `/identity` | Bearer | multipart: `id_front`, `id_back`, `selfie` |
| Identity image | `GET` | `/identity/documents/{id}` | Bearer | stream |

**Post-auth routing order:**

1. `needs_email_verification` → Verify email  
2. else `needs_identity_documents` → Identity  
3. else → Home (`/dashboard`)

**Google Sign-In (Flutter):** use `google_sign_in` with `serverClientId` = **Web** `GOOGLE_CLIENT_ID`, send `idToken` to `POST /api/auth/google`. New users skip email verify (`email_verified: true`) and usually land on Identity. Setup (Console clients + Android SHA-1): [`docs/GOOGLE_OAUTH_SETUP.md`](GOOGLE_OAUTH_SETUP.md).

Use `GET /api/site` for logo/name on auth + splash (same as admin Site settings).

Site branding for auth UI: admin **display name** + **logo**.

---

## 3. Join action states (must match web exactly)

From `App\Enums\EventJoinAction` — drive buttons on cards + event detail:

| Value | UI |
|-------|-----|
| `request_to_join` | Orange **Request to join** |
| `request_pending` | Muted “Request pending” (no primary join) |
| `joined` | Emerald “Joined” + Chat + Members (+ Leave if not owner) |
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
| `x-events.card` | Rich discover/list card |
| `x-dashboard.event-card` | Compact home nearby card |
| `x-community.ambient` | AmbientBackground |
| `x-dashboard.mobile-nav` | 5-tab bottom bar |
| `x-dashboard.sidebar` | Wide-layout drawer/sidebar |
| Orange gradient CTA | Primary conversion button |
| Indigo→violet CTA | Secondary brand (login, chat) |
| Form fields | rounded-xl, indigo focus |

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

`GET /api/user` / login / register expose: `email_verified`, `needs_email_verification`, `needs_identity_documents`, `has_identity_documents`, `identity_resubmit_required`.

---

## 7. Admin screens (web-only — do not block member app)

Documented for awareness; **not required** in member Flutter:

- `/admin` dashboard, users, identity moderation, events, calendar, chat moderation, reports, cities, categories, settings, audit  

Use web admin at `https://fun.idigital-cs.com/admin`.

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
| `/events/:id` | `/events/{id}` |
| `/events/:id/edit` | `/events/{id}/edit` |
| `/events/:id/chat` | `/events/{id}/chat` |
| `/events/:id/requests` | `/events/{id}/requests` |
| `/events/:id/members` | `/events/{id}/members` |
| `/my-events` | `/my-events` |
| `/identity` | `/identity` |
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

1. **Auth + identity + profile** — full stack with `GET /api/site` branding  
2. **Shell** — 5-tab + dashboard IA; Profile hub  
3. **Discover** — filters including `when`; **always send Bearer** for `join_action`  
4. **Event detail** — creator card, ratings, report/block, full owner matrix  
5. **Cards** — rich vs compact per surface  
6. **Create/edit** — all fields + image  
7. **Members** — promote / demote / transfer + report/block links  
8. **Chat** — list/post; poll until websockets  
9. **Home** — participations + category counts + nearby  

**Explicitly web-only (OK to skip in member app):** `/admin/*` only.

---

## 11. Reference URLs (local QA)

Base: `https://fun.idigital-cs.com`

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
| Profile | `/settings/profile` |
| Location | `/settings/location` |
| Identity | `/identity` |
| Safety | `/safety` |
| Blocks | `/settings/blocks` |
| Report | `/report` |

API base: `https://fun.idigital-cs.com/api`  
Public branding: `GET /api/site`

---

*Last updated: 2026-09-23 — full-project parity; Google Sign-In via `POST /api/auth/google`; member pages on dashboard shell; API site/participations/creator/sanctum join_action/category counts.*
