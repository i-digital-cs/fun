# Postman — Fun API

## Import
1. Postman → **Import**
2. Select:
   - `Fun_API.postman_collection.json`
   - `Fun_API.postman_environment.json`
3. Choose environment **Fun API — Production**

## Quick start
1. Open **Catalog → Site branding** (optional) then **Auth → Login**
2. Defaults: `admin@example.com` / `password` (if seeded)
3. Send — `token` is saved automatically
4. Call any authenticated request — **also send Bearer on Discover/Show event** for correct `join_action`

## Auth & parity flows (Flutter)
| Folder item | Endpoint |
|-------------|----------|
| Site branding | `GET /site` |
| Register | `POST /register` (sends email verify code) |
| Login | `POST /login` (`needs_email_verification` flag) |
| Google Sign-In (ID token) | `POST /auth/google` `{ "id_token" }` — see [`GOOGLE_OAUTH_SETUP.md`](../GOOGLE_OAUTH_SETUP.md) |
| Resend email verify code | `POST /email/verify/resend` |
| Verify email code | `POST /email/verify` `{ "code": "1234" }` |
| Forgot password | `POST /forgot-password` |
| Verify reset code | `POST /forgot-password/verify` → saves `resetToken` |
| Reset password | `POST /reset-password` (uses `resetToken`, saves `token`) |
| Update profile | `PUT /settings/profile` |
| Home participations | `GET /home/participations` |
| Get identity status | `GET /identity` |
| Upload identity | `POST /identity` (multipart id_front, id_back, selfie) |
| Weather | `GET /weather` (Bearer; optional `?city_id={{cityId}}`) |
| Now playing | `GET /events/now-playing` (optional Bearer; optional `?city_id={{cityId}}`) |
| Public profile | `GET /users/{{publicId}}` |
| List favorites | `GET /favorites` (Bearer) |
| Toggle favorite | `POST /events/{{eventId}}/favorite` (Bearer) |
| Event ticket | `GET /events/{{eventId}}/ticket` (Bearer) |
| Cancel join request | `POST /events/{{eventId}}/join/cancel` (Bearer) |
| Chat inbox | `GET /chats` (Bearer) |
| Mark messages read | `POST /events/{{eventId}}/messages/read` (Bearer) |
| Identity document stream | `GET /identity/documents/{{documentId}}` (Bearer) |

See: [`FLUTTER_WEB_PARITY.md`](../FLUTTER_WEB_PARITY.md) §0.1–0.2 (full-project contract).

## Variables
| Variable | Meaning |
|----------|---------|
| `baseUrl` | `https://www.funnity.com/api` |
| `token` | Sanctum Bearer token (auto from Login/Register/Reset/Google) |
| `resetToken` | One-time password-reset token (auto from Verify reset code) |
| `locale` | `en` or `ar` (`Accept-Language`) |
| `eventId` / `cityId` / `categoryId` / `userId` | Path IDs |
| `publicId` | Public profile id (default `Funity-F001007`) |
| `documentId` | Identity document id for stream endpoint |

Local: set `baseUrl` to your local `APP_URL` + `/api`.
