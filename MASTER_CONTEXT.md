# MASTER_CONTEXT.md

# Community Activity Platform

## 1. Project Vision

The platform connects people who want to participate in the same real-world activity.

A user can create an event around an activity and location, and other users nearby can discover the event and request to join.

Examples:

* Football
* PlayStation
* Basketball
* Running
* Gaming
* Board games
* Cinema
* Social activities
* Outdoor activities

The platform is primarily a community coordination platform.

It does not process event payments in V1.

---

# 2. Core Product Principle

The platform answers:

> "I want to do something with other people nearby. Who else wants to join?"

The platform facilitates:

1. Discovery
2. Event creation
3. Join requests
4. Membership
5. Event administration
6. Event communication
7. Moderation
8. Community safety

---

# 3. Technology

## Backend

* PHP
* Laravel 12
* MySQL

## Frontend

* Laravel Blade
* Tailwind CSS
* Responsive frontend

## Mobile

* Flutter client in `mobile/`
* Consumes the Laravel Sanctum API (production default: `https://fun.idigital-cs.com/api`)
* UI must mirror member Blade screens + `DESIGN.md` / `mobile/docs/DESIGN.md`
* Flutter documentation (source of truth for the mobile app):
  * `mobile/ROADMAP.md` — Flutter phases & tasks (session continuity for mobile)
  * `mobile/docs/FLUTTER_PRINCIPLES.md` — Flutter principles, stack rules, Blade-parity rules
  * `mobile/docs/FLUTTER_BEST_PRACTICES.md` — deep Flutter/Dart production standards
  * `mobile/docs/DESIGN.md` — design tokens copied for the mobile tree
  * `mobile/docs/postman/` — API collection used by the client
  * `mobile/README.md` — how to run the app

## API

* Laravel API
* Laravel Sanctum

## Realtime

* Laravel Reverb
* WebSockets

## Media

* V1 scaffold: local disk storage first
* Production target: Cloudflare R2 (wire after scaffold)
* All media abstractions must remain storage-driver agnostic so switching to R2 does not rewrite business logic

## Testing

* PHPUnit / Laravel testing tools
* Feature tests
* Unit tests
* UI/browser testing

## Brand / Site Settings

The product does not yet have a fixed public brand name.

Administrators can configure:

* application display name
* domain/title-related display settings

UI and emails must read the display name from admin-configurable settings, not hard-coded product strings.

---

# 4. Architecture

The application is API-first at the application/business-logic level.

Business logic must not be duplicated between:

* Blade
* API
* Flutter mobile (`mobile/`)
* Admin

Preferred flow:

Controller
→ Form Request
→ Authorization
→ Service
→ Repository/query layer where justified
→ Model
→ Database

Controllers remain thin.

Services contain business rules.

Models contain:

* relationships
* casts
* scopes
* model-specific behavior

Blade must not contain business logic.

---

# 5. Users

Users register and create an account.

Supported locales:

* English (`en`) — LTR
* Arabic (`ar`) — RTL

The application must support both languages across Blade and API user-facing messages.

Locale selection persists per user preference / session and must switch document direction (`dir="ltr"` / `dir="rtl"`) accordingly.

A user has:

* profile information
* profile image
* city
* location preferences
* preferred locale
* identity documents
* account status
* optional Google account linkage

Users can participate in events after completing the required identity-document flow.

---

# 5A. Authentication (V1)

V1 authentication methods:

1. Email + password
2. Google (Gmail) OAuth login

Rules:

* Google-only accounts may have a null password
* Linking/creating via Google still requires the identity-document flow before protected participation
* Never trust client-provided role, status, or verification flags
* Auth endpoints must be rate-limited

---

# 5B. Internationalization

The product is bilingual from V1:

* Arabic and English content/translations
* RTL for Arabic, LTR for English
* Forms, validation messages, flash messages, and emails should respect locale
* Hard-coded user-facing strings in a single language are not acceptable for shipped screens

---

# 6. Identity Documents

The initial product flow requires:

* ID front
* ID back
* selfie

The initial product requirement is **immediate acceptance** without waiting for manual administrator approval on first upload.

Members can open the identity screen and **view their own submitted images** (private, owner-only stream). Images are never public.

Administrators with appropriate permissions can:

* view and download identity documents
* **request documents again** — sets the user back to `pending_documents` so they must re-upload all three files before participating again (previous files remain until replaced)

Administrators can also deactivate a user.

Identity documents are private sensitive media and must be stored in Cloudflare R2 (V1 may use the configured private disk).

Identity documents must never be publicly accessible.

Important:

Uploading identity documents alone must not technically be represented as verified government KYC unless an actual identity/KYC verification mechanism validates them.

The architecture must allow future automated KYC verification.

---

# 7. Account Status

Account status values:

* `pending_documents` — registered without documents, **or** admin requested a resubmit; identity upload required
* `active` — identity documents uploaded; immediate acceptance; full participation allowed
* `deactivated` — administratively deactivated

Identity-document upload transitions the user from `pending_documents` to `active` without waiting for manual administrator approval.

When an administrator requests documents again, the user returns to `pending_documents` until a full new upload succeeds.

Administrators with `SUPER_ADMIN` or `MODERATOR` permissions can deactivate a user.

Deactivation must be fully implemented as server-side functionality in the auth/account task (services, authorization, API/admin endpoints, tests), even before the full Task 11 admin UI is built.

Task 11 connects/polishes the admin UI on top of this completed functionality.

A deactivated user:

* cannot participate in protected platform actions
* cannot create or join events
* cannot use event chat
* cannot bypass the restriction through API requests

A user in `pending_documents` cannot create events, join events, or use event chat until documents are uploaded.

Authorization must be enforced server-side.

---

# 8. Location

The platform supports both:

## User city

A user selects their city from an admin-managed cities table.

V1 ships with a seeded Egypt city list (examples: Obour, Cairo, and other seeded cities).

Administrators can create, edit, and deactivate cities.

Each city stores center coordinates (latitude/longitude) used for distance discovery in V1.

## Distance preference

Users can select how far from their city they want to discover events.

Examples:

* 5 km
* 10 km
* 25 km
* 50 km

Exact available distance values are configurable by product decision.

V1 distance calculation uses the selected city's center coordinates.

The platform must not require users to expose their home address.

---

# 9. Event Location

Events have a city/location and may have a specific venue.

Example:

Football — Obour

Al Ahly Club Playground

Event location may contain:

* city
* venue name
* coordinates where required
* display location

Exact personal home addresses must never be required for normal event participation.

---

# 10. Events

A user can create an event.

An event may contain:

* title
* description
* category
* city
* venue
* date
* start time
* end time where applicable
* maximum participants
* requirements
* expected cost
* cost per person
* event image
* status

Event status values:

* `draft`
* `published`
* `cancelled`
* `completed`

The event creator becomes the owner and is automatically an accepted OWNER member of the event (no join request required for the creator).

---

# 11. Event Capacity

Each event has a maximum participant capacity.

Example:

10 maximum participants.

When capacity is reached:

10 / 10

the event becomes full for joining purposes.

Capacity must be enforced server-side.

Client-side capacity checks are not security controls.

Race conditions around simultaneous join approvals must be handled transactionally.

---

# 12. Event Joining

Joining is owner-approved.

Flow:

User
→ Request to Join
→ Owner/Admin
→ Accept or Reject

Only accepted users become members.

A user must not be able to force membership by manipulating API parameters.

---

# 13. Event Roles

Event roles:

* OWNER
* ADMIN
* MEMBER

The owner can promote a member to event admin.

The owner can transfer ownership.

The owner can leave after assigning another responsible owner.

The application must prevent an event from entering an invalid ownership state.

---

# 14. Owner Leaving

When the owner attempts to leave:

The application informs the owner that ownership must be transferred.

The owner selects another appropriate member.

Ownership is transferred.

The previous owner then leaves.

The event continues.

Other affected members should receive an appropriate notification/system message.

---

# 15. Event Chat

V1 contains one public chat per event.

There is no general private messaging system in V1.

Only current authorized event members can access the event chat.

Chat is realtime using Laravel Reverb/WebSockets.

Channel authorization must be performed server-side.

Changing an event ID in a frontend request must never grant access to another event's chat.

---

# 16. Chat Security

Chat must protect against:

* spam
* flooding
* XSS
* malicious HTML
* malicious links where applicable
* oversized messages
* unauthorized channel access
* deleted/deactivated account abuse

Messages must be treated as untrusted user input.

---

# 17. Money

Events may contain informational financial details.

Example:

Football event

Expected cost:

80 EGP / person

The application only communicates the information.

Payment happens outside the application.

V1 does not include:

* payment gateways
* wallet
* escrow
* payment processing
* transaction settlement

No payment database structures should be created unless a future requirement explicitly introduces them.

---

# 18. Categories

Categories are admin-managed.

Administrators can:

* create
* edit
* deactivate
* manage

categories.

The application must not hard-code category IDs.

---

# 19. Ratings

V1 supports rating the event creator after participation/event completion.

Initial rating model:

* 1–5 stars
* optional comment

Rating is allowed only when:

* the event end time has passed (or event status is `completed`)
* the rater was an accepted event member
* the rater is not rating themselves as the creator in an invalid way (creator does not rate themselves)

The rating system must not become a generic social ranking system unless explicitly expanded later.

---

# 20. Reports

Users can report:

* users
* events
* messages

Reports contain appropriate:

* reporter
* target
* reason
* description
* status
* moderation action
* timestamps

Moderators/admins can investigate and act.

---

# 20A. User Blocking (V1)

V1 includes user blocking.

A user may block another user.

Blocked-user rules (server-enforced):

* a blocked user must not appear as a join-request candidate in ways that violate product privacy expectations
* blocked relationships must be checked before join-request acceptance paths where applicable
* blocked users must not be able to bypass restrictions through API requests

Exact UI and membership edge cases around already-accepted co-membership must follow approved product rules during implementation planning for that feature.

---

# 21. Administration

Administrative roles:

* SUPER_ADMIN
* MODERATOR

Permissions must be explicit.

Administrative actions should be auditable.

Admin functionality includes:

* users
* identity documents
* account deactivation
* events
* reports
* chat moderation
* categories
* moderation actions

---

# 22. Media

All application media must use a single storage abstraction.

V1 implementation order:

1. Local disk driver during scaffold/development
2. Cloudflare R2 for production media

Examples:

* profile images
* identity documents
* event images
* future media types

Uploaded files are untrusted.

The application must validate:

* file size
* MIME type
* extension
* purpose
* authorization

Original filenames must not be trusted as storage paths.

Private sensitive files must use private storage and controlled access.

---

# 23. Security

Security is a first-class requirement.

Protect against:

* SQL injection
* XSS
* CSRF
* IDOR
* authorization bypass
* mass assignment
* privilege escalation
* path traversal
* malicious uploads
* executable uploads
* MIME spoofing
* spam
* brute force
* credential attacks
* enumeration
* malicious links
* WebSocket authorization bypass
* race conditions
* excessive requests

Never trust client-provided:

* user_id
* owner_id
* role
* status
* verification status
* event membership
* permissions

---

# 24. Database Rules

No speculative database structures.

Never create:

* unused tables
* unused columns
* unused indexes
* duplicate tables
* duplicate fields
* future placeholders without an approved requirement

Every table and column must have an identified purpose.

Before modifying the database:

1. Inspect the existing schema.
2. Search for existing representations.
3. Verify that the requirement cannot use an existing structure.
4. Add only the minimum required structure.

---

# 25. No Duplicate Business Logic

Every important business rule must have one source of truth.

Do not duplicate rules across:

* controllers
* models
* services
* Blade
* JavaScript
* API endpoints
* policies

Presentation may display a rule, but it must not become the authority for that rule.

---

# 26. Performance

The application must:

* prevent N+1 queries
* use appropriate indexes
* paginate large lists
* avoid unnecessary queries
* avoid loading unnecessary relationships
* use queues for appropriate expensive operations
* optimize media processing
* avoid unnecessary realtime traffic
* avoid premature caching

Performance decisions must be based on actual application behavior.

---

# 27. Spam and Abuse

Rate limits are required for abuse-sensitive actions.

Examples:

* registration
* login
* password reset
* event creation
* join requests
* messages
* reports
* identity-document uploads

Limits must be server-side.

---

# 28. Testing

Every feature must have appropriate automated tests.

Important business flows require feature tests.

Examples:

* registration
* authentication
* event creation
* joining
* approval
* capacity
* ownership transfer
* leaving
* chat authorization
* moderation
* deactivation
* upload authorization
* report creation

Security-sensitive behavior must be explicitly tested.

---

# 29. Mandatory Development Workflow

Every task follows:

PLAN
→ ASK QUESTIONS
→ CONFIRM BUSINESS RULES
→ IMPLEMENT
→ TEST
→ UI TEST
→ SECURITY REVIEW
→ PERFORMANCE REVIEW
→ REPORT

AI must never silently invent missing business requirements.

If an ambiguity can change:

* database structure
* security
* permissions
* money
* identity verification
* event behavior
* membership
* moderation

the implementation must stop and ask.

---

# 30. Scope Discipline

V1 intentionally excludes:

* private messaging
* in-app payments
* wallet
* escrow
* complex recommendation engine
* unnecessary social-network functionality
* unnecessary analytics infrastructure

Future features must not be implemented speculatively.

---

# 31. Source of Truth

The project documentation hierarchy is:

1. MASTER_CONTEXT.md — product/business source of truth
2. AGENT.md — engineering/AI execution rules
3. DESIGN.md — UI/UX/design source of truth (Blade + Flutter)
4. ROADMAP.md — **Laravel / backend / Blade** implementation task roadmap and session continuity
5. Actual tested implementation

### Flutter mobile documentation

When the task is Flutter / `mobile/` work, the mobile docs are authoritative for client scope:

1. `mobile/ROADMAP.md` — Flutter phases, Blade-parity tasks, session continuity for mobile
2. `mobile/docs/FLUTTER_PRINCIPLES.md` — Flutter principles & locked stack for this app
3. `mobile/docs/FLUTTER_BEST_PRACTICES.md` — detailed Flutter/Dart standards
4. `mobile/docs/DESIGN.md` — design system copy used by the Flutter tree (same tokens as root `DESIGN.md`)
5. `mobile/docs/postman/` — HTTP API contracts for the client
6. `mobile/README.md` — run instructions

Do not invent Flutter architecture or screens outside `mobile/ROADMAP.md` and `mobile/docs/FLUTTER_PRINCIPLES.md`.

If documentation conflicts with implementation, stop and resolve the conflict instead of silently choosing one.

Any new AI/agent session must:

* For **Laravel / Blade / API** work: read root `ROADMAP.md` and continue from the first incomplete task.
* For **Flutter / `mobile/`** work: read `mobile/ROADMAP.md` and `mobile/docs/FLUTTER_PRINCIPLES.md`, then continue from the first incomplete mobile task.
