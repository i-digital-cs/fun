# AGENT.md

# Engineering Agent Rules

## 1. Mandatory Startup

Before changing code:

1. Read `MASTER_CONTEXT.md`.
2. Choose the correct roadmap:
   * **Laravel / Blade / API:** read root `ROADMAP.md` and continue from the first incomplete task (update statuses when a task finishes).
   * **Flutter / `mobile/`:** read `mobile/ROADMAP.md` and `mobile/docs/FLUTTER_PRINCIPLES.md`, then continue from the first incomplete mobile task (update statuses in `mobile/ROADMAP.md` when a task finishes).
3. Read design docs when UI is involved:
   * Blade: root `DESIGN.md`
   * Flutter: `mobile/docs/DESIGN.md` + `mobile/docs/FLUTTER_PRINCIPLES.md` (Blade remains the visual source of truth; Flutter mirrors member Blade screens)
4. For Flutter work, also skim `mobile/docs/FLUTTER_BEST_PRACTICES.md` and `mobile/docs/postman/` when touching architecture or API integration.
5. Inspect the existing implementation.
6. Inspect migrations and database schema (backend tasks).
7. Inspect routes (web/api or `mobile` go_router as relevant).
8. Inspect models / domain entities.
9. Inspect services / repositories.
10. Inspect policies/authorization (backend) or account gates (Flutter).
11. Inspect existing tests.

Do not assume the repository is empty or that a requested feature does not already exist.

---

# 2. Mandatory Workflow

Every task must follow:

## PLAN

Explain:

* what will change
* why it is needed
* affected files
* database impact
* API impact
* UI impact
* security impact
* tests required

## QUESTIONS

Ask questions when requirements are ambiguous.

Never invent business rules involving:

* permissions
* identity
* membership
* ownership
* money
* moderation
* privacy
* security
* database structure

## IMPLEMENT

Implement only approved requirements.

## TEST

Run relevant:

* unit tests
* feature tests
* integration tests

## UI TEST

Verify the feature through the actual UI when UI changes are involved.

## SECURITY REVIEW

Check authorization and untrusted input.

## PERFORMANCE REVIEW

Check queries, indexes, N+1 issues, payloads and unnecessary work.

## REPORT

Report:

* changed files
* behavior implemented
* tests executed
* UI verification
* security checks
* known limitations

Then update the correct roadmap task status before ending the session:

* Laravel / Blade / API → root `ROADMAP.md`
* Flutter → `mobile/ROADMAP.md`

---

# 3. Architecture

Preferred:

Controller
→ Form Request
→ Authorization
→ Service
→ Repository/query layer where justified
→ Model
→ Database

Controllers are thin.

Controllers should not contain complex business logic.

Services own business rules.

Models contain:

* relationships
* casts
* scopes
* model-specific behavior

Blade is presentation only.

JavaScript is presentation/interactivity only unless a documented client-side requirement exists.

---

# 4. SOLID

Apply SOLID principles pragmatically.

Do not create abstractions without a real reason.

Avoid:

* unnecessary interfaces
* unnecessary factories
* unnecessary repositories
* unnecessary managers
* unnecessary wrappers

Prefer simple maintainable code.

---

# 5. OOP

Use meaningful classes with clear responsibilities.

A class should have one understandable purpose.

Avoid giant services.

Avoid utility classes containing unrelated functionality.

---

# 6. Clean Code

Requirements:

* meaningful names
* small methods
* clear control flow
* no dead code
* no commented-out code
* no magic values
* no duplicate logic
* no unnecessary complexity
* no misleading names

---

# 7. Database Discipline

Before creating a migration:

Search the existing schema.

Before creating a model:

Search for an existing model.

Before creating a service:

Search for existing business logic.

Before adding a column:

Identify its exact business purpose.

Never create speculative:

* tables
* columns
* indexes
* relationships
* statuses

No dead database structures are allowed.

---

# 8. No Duplication

Do not duplicate:

* business rules
* validation rules
* authorization logic
* database queries
* services
* models
* components
* API behavior

If similar functionality already exists, extend it where appropriate.

---

# 9. Security

Treat every client request as untrusted.

Never trust client-provided:

* IDs
* roles
* ownership
* membership
* status
* permissions
* verification state

Always authorize server-side.

Use:

* Laravel validation
* policies/gates
* CSRF protection
* Sanctum
* rate limiting
* secure file validation
* secure private storage

---

# 10. API

API responses must be predictable.

Do not expose:

* sensitive identity information
* internal moderation information
* private storage paths
* unnecessary database fields

Use authorization before returning protected resources.

Prevent IDOR.

---

# 11. Realtime

Reverb/WebSockets must never bypass normal authorization.

Every private/event channel requires server-side authorization.

A WebSocket subscription is not proof of permission.

---

# 12. Media

All media goes through the application storage abstraction.

V1 order:

1. Local disk during scaffold/development
2. Cloudflare R2 for production

Never trust:

* filename
* extension
* MIME type supplied by client

Validate uploads server-side.

Generate safe storage keys.

Sensitive identity documents must remain private.

---

# 13. Upload Security

Reject:

* oversized files
* unauthorized file types
* executable content
* suspicious uploads

Do not allow uploaded filenames to determine filesystem paths.

---

# 14. Rate Limiting

Use rate limits for abuse-sensitive endpoints.

At minimum consider:

* login
* registration
* password reset
* event creation
* join requests
* messages
* reports
* uploads

Limits must be enforced server-side.

---

# 15. Authorization

Authorization must be explicit.

Examples:

A user may edit an event only if they have the required event role.

A user may access event chat only if they are authorized for that event.

A moderator may moderate only resources permitted by their permissions.

A deactivated user must not bypass restrictions through API calls.

---

# 16. Testing

Every meaningful business rule requires automated coverage.

Prioritize tests for:

* authentication
* authorization
* event creation
* capacity
* join requests
* approval/rejection
* owner transfer
* event leaving
* chat authorization
* deactivation
* reports
* uploads
* moderation

Do not rely only on happy-path tests.

Test:

* unauthorized
* invalid input
* boundary conditions
* race-sensitive operations
* malicious input where relevant

---

# 17. UI

## Blade

UI must follow root `DESIGN.md`.

Do not create isolated styles that conflict with the design system.

Responsive behavior is required.

RTL/LTR behavior is required. English is LTR; Arabic is RTL. Layouts, icons, and spacing must flip correctly with locale.

## Flutter (`mobile/`)

Flutter UI and client work must follow:

* `mobile/docs/FLUTTER_PRINCIPLES.md` — principles, stack, Blade-parity rules
* `mobile/docs/DESIGN.md` — same design tokens as Blade
* `mobile/ROADMAP.md` — what to implement next
* Member Blade screens as the visual/UX source (adapt to bottom nav; do not invent a second design system)

Deep standards live in `mobile/docs/FLUTTER_BEST_PRACTICES.md`.

API integration must match `mobile/docs/postman/` and the Laravel API — do not invent endpoints or field names.

---

# 18. Performance

Check for:

* N+1 queries
* unnecessary relationships
* missing pagination
* excessive payloads
* unnecessary queries
* expensive synchronous work

Do not optimize blindly.

---

# 19. Dead Code

Before completing a task:

Check for:

* unused imports
* unused methods
* unused classes
* unused routes
* unused components
* unused migrations
* obsolete code

Do not leave commented-out implementations.

---

# 20. Completion Rule

A task is not complete merely because the code compiles.

Definition of done:

* implementation complete
* business rules respected
* automated tests pass
* UI tested when applicable
* authorization tested
* security reviewed
* no obvious dead code
* no duplicate logic introduced
* database changes justified
* final report provided
