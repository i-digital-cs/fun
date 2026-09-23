# Release checklist (P5.4)

Use before any store or production APK/IPA.

## Build commands

```bash
cd mobile

# Analyze
flutter analyze

# Tests
flutter test

# Android release (obfuscated)
flutter build apk --release --obfuscate --split-debug-info=build/debug-info \
  --dart-define=FLAVOR=prod \
  --dart-define=API_BASE_URL=https://fun.idigital-cs.com/api

# App Bundle
flutter build appbundle --release --obfuscate --split-debug-info=build/debug-info \
  --dart-define=FLAVOR=prod \
  --dart-define=API_BASE_URL=https://fun.idigital-cs.com/api
```

`Env.assertSafeForRelease()` throws if a **release** binary is compiled with a localhost / `10.0.2.2` API URL.

## Manual QA

- [ ] EN ↔ AR language toggle; Arabic is RTL on login, discover, event show
- [ ] Login → logout → relaunch: stays logged out (secure storage cleared)
- [ ] Guest: Discover + event show; join / create / chat redirect to login
- [ ] Discover nationwide (`All cities`) and city + distance filters
- [ ] Join request → pending → (as owner) accept → chat polling updates
- [ ] Images on Discover scroll do not stutter badly (mem-cache bounds)
- [ ] Google Sign-In only if OAuth clients / SHA-1 are configured

## Do not ship

- `API_BASE_URL` pointing at `localhost`, `127.0.0.1`, or `10.0.2.2`
- Debug signing for Play Store
- Unobfuscated release without a reason
