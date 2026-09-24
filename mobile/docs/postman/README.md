# Postman — Fun API

## Import
1. Postman → **Import**
2. Select:
   - `Fun_API.postman_collection.json`
   - `Fun_API.postman_environment.json`
3. Choose environment **Fun API — Production**

## Quick start
1. Open **Auth → Login**
2. Defaults: `admin@example.com` / `password` (if seeded)
3. Send — `token` is saved automatically
4. Call any authenticated request

## Variables
| Variable | Meaning |
|----------|---------|
| `baseUrl` | `https://www.funnity.com/api` |
| `token` | Sanctum Bearer token (auto from Login/Register) |
| `locale` | `en` or `ar` (`Accept-Language`) |
| `eventId` / `cityId` / `categoryId` / `userId` | Path IDs |

Local: set `baseUrl` to your local `APP_URL` + `/api`.
