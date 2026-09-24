# Flutter Mobile — ROADMAP.md

# Fun Mobile Client — Implementation Roadmap

1. Read `../docs/FLUTTER_WEB_PARITY.md` + `../docs/FLUTTER_BEST_PRACTICES.md` + `docs/FLUTTER_PRINCIPLES.md`
2. Read **this file** — continue first incomplete WP
3. After each API wire: live smoke → mark done

**API:** `../docs/postman/Fun_API.postman_collection.json` · `https://fun.idigital-cs.com/api`

---

## Current focus

**WP9 — QA / release gate** (in progress): automated analyze+test+public smoke done; remaining = deploy Reverb API + manual device QA + store signing.

See `docs/QA_PASS_2026-09-24.md` and `docs/RELEASE.md`.

---

## Progress snapshot

| Phase | Status |
|-------|--------|
| WP0 Postman + ROADMAP | `done` |
| WP1 Gates + site + Google id_token | `done` |
| WP2 Email verify + forgot/reset | `done` |
| WP3 Force/soft update | `done` |
| WP4 Ticket + Favorites | `done` |
| WP5 Home weather/now-playing/participations | `done` |
| WP6 Profile edit + Identity GET | `done` |
| WP7 Membership/chat smoke | `done` |
| WP8 Searching + analyze | `done` |
| WP8b Chat Reverb toasts (+ poll fallback) | `done` (client); prod deploy pending |
| WP9 QA / release gate | `in_progress` |

---

## Smoke log (production)

| Endpoint | Result |
|----------|--------|
| `GET /site` | 200 — display_name Funity, app.android/ios |
| `GET /site` broadcast | not on prod yet (2026-09-24) |
| `GET /events/now-playing` | 200 — next/previous |
| `GET /categories` | 200 — published_events_count |
| `POST /broadcasting/auth` | 404 on prod — deploy pending |
| `POST /login` | needs credentials — retest with Postman |
| Auth Bearer routes | pending token |

---

## Explicitly NOT in mobile V1

- `/admin/*`

---

*Updated 2026-09-24 — WP8b client shipped; WP9 QA started.*
