# Fun Mobile

Flutter client for the Fun community activity platform.

## Docs (start here)

| Doc | Purpose |
|-----|---------|
| [`ROADMAP.md`](ROADMAP.md) | Phases & tasks (Blade parity plan) |
| [`docs/FLUTTER_PRINCIPLES.md`](docs/FLUTTER_PRINCIPLES.md) | App principles & stack rules |
| [`docs/ENGINEERING_CONSTITUTION.md`](docs/ENGINEERING_CONSTITUTION.md) | Locked do-not-regress rules |
| [`docs/DESIGN.md`](docs/DESIGN.md) | Visual system (same as Blade) |
| [`docs/FLUTTER_BEST_PRACTICES.md`](docs/FLUTTER_BEST_PRACTICES.md) | Deep Flutter standards |
| [`docs/RELEASE.md`](docs/RELEASE.md) | Store / release checklist |
| [`docs/postman/`](docs/postman/) | API collection |

## Stack

- Clean Architecture (`features/*/data|domain|presentation`)
- Riverpod, Dio, go_router
- Design tokens from `docs/DESIGN.md` / Blade UI
- EN + AR (RTL)
- Sanctum Bearer auth → `API_BASE_URL`

## Run

Uses the **online** API by default (`https://www.funnity.com/api`).

```bash
cd mobile
flutter pub get
flutter run
```

### Flavors / dart-define

| Define | Default | Notes |
|--------|---------|--------|
| `API_BASE_URL` | `https://www.funnity.com/api` | Never leave localhost on release builds |
| `FLAVOR` | `prod` | Use `dev` for local/staging |

```bash
# Production (same as defaults)
flutter run --dart-define=FLAVOR=prod

# Local API (Android emulator → host machine)
flutter run --dart-define=FLAVOR=dev \
  --dart-define=API_BASE_URL=http://10.0.2.2/Funity/fun/public/api
```

Release builds refuse local API URLs (see `Env.assertSafeForRelease`).

### Tests

```bash
flutter test
flutter analyze
```

## Structure

```
lib/
  app/          # theme, router, FunApp
  core/         # network, storage, errors, shared widgets
  features/     # auth, home, discover, events, catalog, profile, identity, safety
  l10n/         # ARB strings (EN + AR)
```

## Screens (online API)

- Auth: Login / Register / Google (needs OAuth client setup)
- Guest: Discover + event show
- Home + Discover + Event detail (join / leave / owner actions / rate / report)
- Create / Edit / Publish events
- My Events, Chat (polling), Join requests, Members
- Profile: Location, Identity upload, Blocks, Safety guidelines
