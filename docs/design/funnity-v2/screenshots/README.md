# Funnity V2 implementation screenshots

## In-repo comparison assets

| File | Description |
| --- | --- |
| `00-approved-reference.png` | Copy of the owner-approved Visual Source of Truth |
| `00-web-baseline-before.png` | Pre-redesign baseline captured for this project |

## Live implementation captures (Cursor browser, 28 Oct 2026)

Captured against local `php artisan serve` at `http://127.0.0.1:8765/` (not production):

- Desktop hero: Cursor session file `web-desktop-v2.png`
- Mobile 390×844 hero: Cursor session file `web-mobile-390-v2.png`

These PNGs were saved by the Cursor browser tool to the IDE client temp screenshots folder. Re-save them into this directory from the IDE media panel if durable repo copies are required.

## Flutter

Device/emulator screenshot was **not** captured in this pass: host Flutter SDK is Dart 3.9.2 while `mobile/pubspec.yaml` requires `^3.13.1`. Flutter V2 token/hero/weather/card code is present; visual QA on device remains pending after SDK upgrade.
