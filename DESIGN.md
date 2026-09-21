# DESIGN.md

# Product Design System

## 1. Design Goal

The application should feel like a modern, trustworthy community platform.

The design should communicate:

* community
* activity
* trust
* safety
* simplicity
* locality
* real-world interaction

Avoid making the interface look like a generic admin dashboard or generic social-media clone.

---

# 2. Core UX Principle

The primary user journey should be extremely simple:

Discover
→ View
→ Request to Join
→ Get Accepted
→ Participate
→ Chat
→ Attend
→ Rate

Creating an event should also be simple:

Create Event
→ Choose Activity
→ Choose Location
→ Choose Date
→ Set Capacity
→ Add Requirements
→ Publish

---

# 3. Primary Screens

## Public

* Landing page
* Discover events
* Event details
* Login
* Registration

## Authenticated

* Home
* Nearby events
* My events
* Create event
* Event details
* Join requests
* Event members
* Event chat
* Profile
* Settings
* Reports

## Administration

* Dashboard
* Users
* Identity documents
* Events
* Reports
* Moderation
* Categories
* Cities
* Site settings (display name / brand title)
* Audit activity

---

# 4. Event Card

Event cards should communicate the most important information immediately.

Example:

Football

Football — Obour

Al Ahly Club Playground

Today · 8:00 PM

7 / 10 members

80 EGP / person

[View Event]

The card should not expose unnecessary information.

---

# 5. Event Details

Recommended hierarchy:

1. Activity/title
2. Image
3. Date/time
4. Location
5. Venue
6. Description
7. Requirements
8. Cost information
9. Capacity
10. Members
11. Join request
12. Chat for members

---

# 6. Join Request UX

The primary action should be obvious.

Before requesting:

[Request to Join]

After requesting:

[Request Pending]

After approval:

[Joined]

After rejection:

[Request Rejected]

After full capacity:

[Event Full]

The backend remains the authority for all states.

---

# 7. Event Ownership

Owner controls should be visually distinct.

Owner actions:

* Edit event
* Manage members
* Approve/reject requests
* Promote member
* Transfer ownership
* Moderate event chat
* Leave after ownership transfer

---

# 8. Chat UI

Event chat should feel lightweight and realtime.

Display:

* sender
* message
* timestamp
* moderation actions where authorized

Avoid unnecessary social-media features in V1.

---

# 9. Identity UI

The identity flow should communicate why documents are required without making unnecessary security promises.

Recommended language:

"Identity documents are required to help maintain a safer community."

Upload zones:

* large dashed border (`slate-300`)
* clear indigo iconography
* immediate emerald success confirmation after successful upload/activation

Avoid absolute claims such as:

"This app is 100% safe."
"This platform is 100% secured."

Prefer concrete safer-community language unless product explicitly overrides this rule in MASTER_CONTEXT.md.

Admin interfaces may display identity documents only to authorized staff.

---

# 10. Safety UX

Safety features should be easy to find.

Include:

* Report
* Block user (V1)
* Event moderation
* Account deactivation handling

Do not hide reporting or blocking behind complicated menus.

---

# 11. Typography

Use a professional readable font system supporting:

* Arabic
* English

The UI must support:

* LTR for English
* RTL for Arabic

`dir` and locale must stay in sync.

Typography must remain readable on:

* desktop
* tablet
* mobile

---

# 12. Color System

Approved V1 brand palette:

| Token | Role | Value |
|------|------|-------|
| Primary | Trust & community | Indigo `#4F46E5` (Tailwind `indigo-600`) |
| Accent | Energy & CTA | Orange `#F97316` (Tailwind `orange-500`) |
| Success / trust feedback | Approval & positive status | Emerald `#10B981` (Tailwind `emerald-500`) |
| Background | App background | Slate `#F8FAFC` (Tailwind `slate-50`) |
| Surface | Cards / panels | White |
| Text | Primary text | Slate `#0F172A` (Tailwind `slate-900`) |
| Muted | Secondary text | Slate `#64748B` (Tailwind `slate-500`) |
| Border | Borders / dashed upload zones | Slate `#CBD5E1` (Tailwind `slate-300`) |

Semantic colors required:

* Primary
* Secondary / Accent
* Background
* Surface
* Text
* Muted
* Border
* Success
* Warning
* Danger
* Info

Usage rules:

* Primary (Indigo): navbar, headings, active navigation, own chat bubbles
* Accent (Orange): high-conversion CTAs only — Create Event, Join / Request to Join, chat notification pings
* Success (Emerald): account approved states, active indicators, positive confirmation
* Incoming chat bubbles: `slate-100`
* Own chat bubbles: `indigo-600` with white text
* Location tags: soft indigo pill (`bg-indigo-100 text-indigo-700`)

Semantic colors must be used consistently via CSS variables / design tokens.

Do not introduce arbitrary colors per page.

---

# 13. Components

Create reusable components for:

* buttons
* inputs
* selects
* cards
* event cards
* badges
* alerts
* modals
* avatars
* pagination
* empty states
* loading states
* error states
* chat messages

---

# 14. Responsive Design

Mobile is a first-class experience.

The application must work properly at:

* mobile
* tablet
* desktop

Do not simply shrink desktop layouts.

Navigation and actions should be redesigned appropriately for smaller screens.

---

# 15. Empty States

Every major collection needs a useful empty state.

Example:

"No events nearby yet."

Then provide an appropriate action:

[Create an Event]

---

# 16. Error States

Errors should be understandable.

Avoid technical messages such as:

"SQLSTATE[23000]..."

Users should see a human-readable message.

Technical details belong in logs, not normal user-facing UI.

---

# 17. Accessibility

UI should consider:

* keyboard navigation
* readable contrast
* semantic HTML
* labels
* focus states
* screen-reader-friendly controls
* touch target sizes

---

# 18. Trust

The visual system should communicate trust through:

* clean hierarchy
* clear identity state
* clear event ownership
* transparent event information
* visible moderation/reporting tools
* consistent feedback

Trust should come from concrete product behavior, not exaggerated marketing claims.

---

# 19. Design Rule

Do not implement a new component or visual pattern if an existing component can be reused.

The design system is a single source of truth for frontend appearance.

Changes to the visual system should be reflected consistently across the application.
