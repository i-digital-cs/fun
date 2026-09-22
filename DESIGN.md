# DESIGN.md

# Product Design System

**This file is the single source of truth for UI across the whole platform** (home, discover, event details, auth, settings, safety, and admin chrome). Blade implementations must follow it. Do not invent one-off visual patterns per page.

---

## 1. Design Goal

The product should feel like a **vibrant, trustworthy neighborhood meetup platform** — not a flat admin panel and not a generic social feed.

Communicate:

* community & locality
* energy & activity
* trust & safety
* simplicity of the join journey
* real-world interaction

Visual atmosphere is required: aurora ambient background, glass surfaces, soft gradients, and intentional motion. Flat gray-on-white alone is not enough.

---

## 2. Core UX Principle

Primary journey:

Discover → View → Request to Join → Get Accepted → Participate → Chat → Attend → Rate

Create journey:

Create Event → Activity → Location → Date → Capacity → Requirements → Publish

---

## 3. Primary Screens

### Public / member

* Home (hero + participations + discover + trust)
* Discover / Nearby events
* Event details, members, requests, chat
* Login / Register
* Identity upload
* Location settings
* Report / Block / Safety guidelines

### Administration

* Dashboard, users, identity, events, reports, categories, cities, settings, audit

Admin keeps denser data tables but **must use the same brand tokens, typography, radius, and CTA styles**. Admin uses **`layouts/admin`** (sidebar + topbar + footer, same shell pattern as the member dashboard).

---

## 4. Shells

### A) Marketing / guest shell (`layouts/home.blade.php`, `layouts/app.blade.php` for guests & public flows)

1. **Ambient layer** — aurora + dots + noise (`x-community.ambient`)
2. **Glass sticky navbar** (`x-community.navbar`)
3. **Main** — `max-w-7xl`
4. **Footer** (`x-community.footer`)

### B) Authenticated member dashboard (`layouts/dashboard.blade.php`)

Source mock: `public/together_home_page.html`.

1. **Fixed sidebar** — brand, Home / Discover / My Events / Create, explore categories, city+distance card (`x-dashboard.sidebar`)
2. **Sticky topbar** — search, locale, notifications, profile (`x-dashboard.topbar`)
3. **Main canvas** — soft `#f5f7fb` background, greeting, photo hero with find-events form, category grid, nearby events + starting-soon + safety side stack, how-it-works, create CTA
4. **Mobile bottom nav** (`x-dashboard.mobile-nav`)

`/` is always the **marketing home** (guest shell). Authenticated members use **`/dashboard`** for the sidebar dashboard.

Brand tokens stay indigo primary + orange Create CTAs (prefer orange Create over mock’s blue create button). Typography remains IBM Plex (not Inter from the static mock).

Do not reintroduce the old flat `site-header` / `site-footer` on member pages.

---

## 5. Typography

* English: IBM Plex Sans
* Arabic: IBM Plex Sans Arabic
* `html[lang]` + `dir` always match locale (`ltr` / `rtl`)
* Headings: extrabold / semibold, tight tracking
* Body: readable slate; muted for secondary

---

## 6. Color System

| Token | Role | Value |
|------|------|-------|
| Primary | Trust, nav active, progress | Indigo `#4F46E5` |
| Primary deep | Hero gradients | Indigo/Violet `#4338CA` → `#6D28D9` |
| Accent | High-conversion CTAs | Orange `#F97316` → Amber `#F59E0B` gradients |
| Success / trust | Verified, positive | Emerald `#10B981` / teal trust banners |
| Background | Page (under ambient) | Slate `#F8FAFC` |
| Surface | Cards | White / `white/80` glass |
| Text | Primary text | Slate `#0F172A` |
| Muted | Secondary | Slate `#64748B` |
| Border | Dividers | Slate `#CBD5E1` / soft `slate-200/70` |

**Usage rules**

* Primary indigo: active nav pills, links, progress bars (not urgent)
* Accent orange gradients: Create Event, Search CTA, View Event, Request to Join, chat ping dots
* Emerald: verified badge, confirmed attendance, trust banner (`from-emerald-600 to-teal-600`)
* Location chips: `bg-indigo-100 text-indigo-700`
* Do not invent page-specific brand colors

CSS tokens live in `resources/css/app.css` (`@theme` + atmosphere utilities: `animate-aurora`, `animate-float`, `bg-dots`, `bg-dots-slate`, `bg-noise`, `fancy-scroll`).

---

## 7. Shape, depth, motion

* Large radii: pages/cards `rounded-3xl` / `rounded-[2rem]`; pills `rounded-full`; controls `rounded-xl` / `rounded-2xl`
* Soft borders: `border-slate-200/70`
* Shadows: soft elevation; indigo/orange tinted shadows on primary CTAs (`shadow-orange-500/30`, `shadow-indigo-600/25`)
* Hover: slight lift (`-translate-y-0.5` / `-translate-y-1`) on cards and CTAs
* Motion: aurora + float on decorative layers; respect `prefers-reduced-motion`
* Prefer logical CSS (`start`/`end`, `ms`/`me`) for RTL

---

## 8. Buttons & CTAs

| Kind | Style |
|------|--------|
| Primary conversion | `bg-gradient-to-r from-orange-500 to-amber-500`, bold white, rounded-full or rounded-xl, orange glow shadow |
| Secondary brand | Indigo→violet gradient or solid indigo |
| Ghost / quiet | White/border slate, hover indigo border |
| Destructive | Danger text/border; never orange |

One primary orange CTA per view when possible.

---

## 9. Forms & inputs

* Labels: small, muted, medium weight
* Fields: `rounded-xl` or `rounded-2xl`, soft border, focus ring indigo (`focus:ring-4 focus:ring-indigo-100`)
* Filter panels: white/glass card `rounded-3xl`, not dense admin tables on member pages
* Upload zones: dashed slate border, indigo iconography, emerald success

---

## 10. Event card (platform standard)

Reuse `x-events.card` / discover card pattern everywhere events are listed.

Must show:

* Cover image (real or `public/images/activities/*.png` fallback via `event_cover_url`)
* Category emoji + name chip
* Price or Free chip
* Urgency chip when useful (Almost full / Filling fast / New / Popular)
* Schedule line (indigo)
* Title, venue
* Attendance progress + host initials
* Full-width orange **View Event** CTA

No flat border-only cards without media treatment on member surfaces.

---

## 11. Event details

Hierarchy:

1. Media / category
2. Title
3. Date/time & venue
4. Description & requirements
5. Cost & capacity
6. Primary join CTA (orange)
7. Owner tools (visually secondary)
8. Members / chat entry

Use a single large `rounded-3xl` surface card; primary action obvious.

---

## 12. Join request states

* Request to Join — orange CTA  
* Request Pending — muted outline  
* Joined — emerald soft badge + chat CTA  
* Rejected / Full — muted/warning  

Backend remains authority for state.

---

## 13. Home-specific (already shipped)

* Gradient indigo hero with glass search, date chips, stats, tonight highlight
* Category rail (`fancy-scroll`)
* Participation stepper cards
* Discover grid + orange “Gather neighbors” host CTA card
* Emerald trust strip

Other pages do not need a full hero; they need the **same shell + card/CTA language**.

---

## 14. Empty & error states

Empty collections: clear copy + one orange or indigo action (e.g. Create an Event).

Errors: human-readable only; no SQL/stack traces in UI.

---

## 15. Accessibility

* Semantic HTML, labels, focus rings
* Contrast on gradients (white text on indigo/orange)
* Touch targets ≥ 40px where possible
* Keyboard-reachable nav and menus

---

## 16. i18n / RTL

* All user strings via `lang/{en,ar}` (`design.php`, `home.php`, `events.php`, …)
* Locale toggle in navbar + footer
* Mirror layouts with logical properties; test AR on key screens

---

## 17. Components to reuse

Prefer existing community components and helpers:

* `x-community.ambient` / `navbar` / `footer` / `trust`
* `x-events.card`
* Helpers: `site_display_name()`, `category_emoji()`, `event_cover_url()`, `user_initials()`

Do not add a second header/footer system.

---

## 18. Design rule

If an existing pattern can be reused, reuse it.

Any visual-system change starts in **this file**, then lands in `resources/css/app.css` and shared Blade components, then propagates to pages — never the reverse.
