# Store compliance (Google Play + Apple App Store + web)

Source of truth for store-required product surfaces. Implement with **web + Flutter parity** for member features. See also [`FLUTTER_WEB_PARITY.md`](FLUTTER_WEB_PARITY.md) and [`ROADMAP.md`](../ROADMAP.md) Task **S1**.

**Canonical public URLs**

| Field | URL |
|-------|-----|
| Privacy Policy | https://funnity.com/privacy-policy |
| Terms of Service | https://funnity.com/terms |
| Support | https://funnity.com/support |
| Account deletion help | https://funnity.com/account-deletion |
| In-app delete | Profile → Delete account (web `/settings/delete-account`, Flutter `/settings/delete-account`) |

Contact: `privacy@funnity.com` · `support@funnity.com`

---

## Status overview

| Item | Play | App Store | Web | Flutter | Status |
|------|------|-----------|-----|---------|--------|
| Privacy Policy | Required | Required | `/privacy-policy` | `/privacy-policy` | **done** |
| Terms of Service | Strongly expected | Strongly expected | `/terms` | `/terms` | **done** |
| Support URL | Required listing | Required listing | `/support` | `/support` | **done** |
| Account deletion (in-app) | Required | Required | `/settings/delete-account` | `/settings/delete-account` | **done** |
| Account deletion (web help URL) | Required listing | Required listing | `/account-deletion` | links to web/help | **done** |
| Sign in with Apple | N/A | Required if Google Sign-In | — (iOS app P0) | iOS only | **done** |
| iOS usage strings (camera/photos) | N/A | Required | — | Info.plist | **done** |
| Push notification permission copy | Declare | Entitlements | — | FCM + plist | **done** |
| Force/soft update + store URLs | Ops | Ops | Admin + `/api/site` | Splash | **done** |
| No ATT / no ads ID | Declare | Declare | — | No tracking SDK | **done** (policy) |
| Play Data Safety form | Console | — | — | — | answers below |
| Apple App Privacy labels | — | Console | — | — | answers below |
| Age rating questionnaires | Console | Console | — | — | guidance below |
| Export compliance (HTTPS) | — | Console | — | — | guidance below |

---

## P0 product checklist

- [x] Privacy Policy (EN/AR)
- [x] Terms of Service (EN/AR)
- [x] Support page + email
- [x] Account deletion request + 30-day grace + cancel + purge job
- [x] Public `/account-deletion` instructions for store listings
- [x] Sign in with Apple (`POST /api/auth/apple`, iOS button)
- [x] iOS `NSCameraUsageDescription` / photo library strings + remote-notification background mode
- [x] Profile + auth links to Privacy / Terms / Support / Delete
- [x] Privacy copy mentions deletion + Apple Sign-In

---

## Account deletion behavior

1. Authenticated user confirms with **password** (password accounts) or **re-auth id_token** (Google/Apple-only).
2. `POST /api/account/delete` / web form sets `deletion_requested_at`, revokes Sanctum + device tokens, logs out.
3. User may sign in again within **30 days** and `POST /api/account/delete/cancel` (or web cancel) to restore.
4. Daily schedule `account:purge-deletions` anonymizes overdue accounts: purge identity media, clear Google/Apple ids & password, anonymize name/email/public profile, keep event shells for other members.

Grace days: `config('auth.account_deletion_grace_days', 30)`.

---

## Sign in with Apple

- API: `POST /api/auth/apple` with `id_token`, optional `locale`.
- Users: nullable unique `apple_id`.
- Flutter: Apple button on **iOS only** beside Google.
- Configure `APPLE_CLIENT_IDS` (bundle id / Services ID audiences) in `.env`.

---

## P1 — Google Play Data Safety (fill in Console)

**Data collected (app-provided / account)**

| Category | Collected | Shared | Purpose |
|----------|-----------|--------|---------|
| Name | Yes | Yes (public profile / events / chat) | Account, app functionality |
| Email | Yes | No (except providers) | Account |
| User IDs | Yes | Yes (public_id) | Account, functionality |
| Photos | Yes (profile, event covers, ID docs) | Profile/event yes; **ID docs no** (moderation only) | Account, functionality, security |
| Approximate location | Yes (city + distance preference; event pins) | Event venue pins yes | App functionality |
| Messages | Yes (event chat) | Yes (event members) | App functionality |
| Device IDs / push tokens | Yes | With FCM | Notifications |
| Crash logs / diagnostics | Optional via OS / hosting | Infra only | Analytics / stability |

**Sensitive:** Government ID images + selfie — collected for verification; **not** shared with other users; used for security/moderation.

**Encryption:** Data encrypted in transit (HTTPS).

**Deletion:** Users can request deletion in-app and via https://funnity.com/account-deletion.

**Third parties:** Google (Sign-In), Apple (Sign-In), Firebase Cloud Messaging, Pusher/realtime, hosting/email providers — processors under instruction.

**Tracking / advertising:** No ads SDK; no advertising ID collection for ads.

---

## P1 — Apple App Privacy (App Store Connect)

Declare same as Data Safety:

- Contact Info (name, email)
- User Content (messages, photos, other UGC)
- Identifiers (user ID, device ID for push)
- Location (coarse / user-provided city; event coordinates)
- Sensitive Info (ID documents — purpose: app functionality / fraud prevention; not used for tracking)

**Data Not Collected for Tracking.** No third-party advertising.

---

## P1 — Age ratings / content

- User-generated content: **Yes** (events, chat, profiles, photos)
- Unrestricted web access: **No** (unless you add in-app browser to arbitrary URLs)
- Meetups / social networking: **Yes**
- Not Kids Category; minimum age typically 13+ (align privacy policy)

---

## P1 — Export compliance

Uses standard HTTPS/TLS only → answer that the app only uses encryption exempt from export documentation (standard App Store questionnaire path).

---

## P2 polish

- Auth screens: “By continuing you agree to Terms & Privacy”
- Confirm admin Android/iOS store URLs match live listings before submit
- See [`mobile/docs/RELEASE.md`](../mobile/docs/RELEASE.md)

---

## Out of scope

- In-app purchases / subscriptions
- ATT / IDFA advertising
- Kids Category / under-13 product
- Full automated GDPR SAR portal beyond profile edit + deletion

---

*Last updated: 2026-09-28*
