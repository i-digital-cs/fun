# Flutter Mobile — ROADMAP.md

# Fun Mobile Client — Implementation Roadmap

1. Read `../docs/FLUTTER_WEB_PARITY.md` + `../docs/FLUTTER_BEST_PRACTICES.md`
2. Read **this file** — continue first incomplete WP
3. After each API wire: live smoke → mark done

**API:** `../docs/postman/Fun_API.postman_collection.json` · `https://fun.idigital-cs.com/api`

---

## Current focus

**Web ↔ Flutter parity WP0–WP8 complete** for member features. Use Postman for remaining Bearer-route smoke with a real login token.

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

---

## Smoke log (production)

| Endpoint | Result |
|----------|--------|
| `GET /site` | 200 — display_name Funity, app.android/ios |
| `GET /events/now-playing` | 200 — next/previous |
| `GET /categories` | 200 — published_events_count |
| `POST /login` | needs credentials (PowerShell JSON escaping) — retest with Postman |
| Auth Bearer routes | pending token |

---

## Explicitly NOT in mobile V1

- `/admin/*`

---

*Updated 2026-09-23 — WP0–WP6 shipped in session.*
