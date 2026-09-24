# Release checklist

Use before any store or production APK/IPA.

## BUILD-001 — Android signing & ABI (locked)

Source of Truth cross-links:

* Implementation: `android/app/build.gradle.kts`
* Traceability: `docs/ENGINEERING_CONSTITUTION.md` → **BUILD-001**
* Product lock: `../MASTER_CONTEXT.md` → Mobile Android release
* Agent lock: `../AGENT.md` → BUILD-001

### Permanent rules (must match Gradle)

1. **Production/store releases MUST** use a real production/upload keystore via `android/key.properties` (never commit `key.properties` or the keystore; see `android/key.properties.example`).
2. **Production/store releases MUST NOT** silently fall back to debug signing.
3. **Missing production signing MUST fail closed** — Gradle throws `GradleException` and the release build does not succeed.
4. **Debug release signing is allowed ONLY** through the explicit opt-in:
   `-PallowDebugReleaseSigning=true`
5. That override is for **local / non-store testing only** — never Play Store or production distribution.
6. **Do NOT** reintroduce `ndk.abiFilters` / `abiFilters` limited to `x86_64` (or other emulator-only sets) in `defaultConfig` unless an explicit documented architecture decision updates the Source of Truth.
7. Future AI agents **MUST NOT** weaken these rules just to make a build pass.

### Gradle behavior (authoritative)

| Condition | Release `signingConfig` |
|-----------|-------------------------|
| `android/key.properties` exists | `signingConfigs.release` (upload/production keystore) |
| No `key.properties`, but `-PallowDebugReleaseSigning=true` | `signingConfigs.debug` (**local only**) |
| No `key.properties` and no opt-in | **Build fails** (`GradleException`) |

`defaultConfig` must **not** set emulator-only `abiFilters`. Flutter’s default multi-ABI packaging applies so physical ARM devices remain supported.

Also locked (**BUILD-002**):

* `<application android:allowBackup="false" android:fullBackupContent="false">` in `android/app/src/main/AndroidManifest.xml`
* Do **NOT** re-enable Android Auto Backup without an explicit Source-of-Truth security decision
* Agents **MUST NOT** flip backup flags for convenience or tooling warnings

Manifest path: `android/app/src/main/AndroidManifest.xml` (must match the attributes above).

---

## Build commands

```bash
cd mobile

flutter analyze
flutter test

# Local release smoke only (NOT for Play Store):
flutter build apk --release -PallowDebugReleaseSigning=true \
  --dart-define=FLAVOR=prod \
  --dart-define=API_BASE_URL=https://www.funnity.com/api

# Store release (requires android/key.properties + upload keystore):
flutter build appbundle --release --obfuscate --split-debug-info=build/debug-info \
  --dart-define=FLAVOR=prod \
  --dart-define=API_BASE_URL=https://www.funnity.com/api \
  --dart-define=GOOGLE_SERVER_CLIENT_ID=YOUR_WEB_CLIENT_ID
```

Copy `android/key.properties.example` → `android/key.properties` and fill store paths/passwords.

`Env.assertSafeForRelease()` throws if a **release** binary is compiled with a localhost / `10.0.2.2` API URL.

## Manual QA

- [ ] EN ↔ AR language toggle; Arabic is RTL on login, discover, event show
- [ ] Login → logout → relaunch: stays logged out (secure storage cleared)
- [ ] Guest: Discover + event show; join / create / chat redirect to login or identity as appropriate
- [ ] `pending_documents` user cannot join or create (client gate + server)
- [ ] Discover nationwide (`All cities`) and city + distance filters
- [ ] Chat toast shows generic “new message” (not body text)
- [ ] Images on Discover scroll do not stutter badly (mem-cache bounds)
- [ ] Google Sign-In only if OAuth clients / SHA-1 / `GOOGLE_SERVER_CLIENT_ID` are configured
- [ ] Release APK installs on a physical ARM device
- [ ] Store AAB is signed with the **upload/production** keystore (not debug)

## Do not ship

- `API_BASE_URL` pointing at `localhost`, `127.0.0.1`, or `10.0.2.2`
- Debug signing for Play Store / production
- Release builds that only work because `-PallowDebugReleaseSigning=true` was used
- x86_64-only ABI filters in `defaultConfig`
- Unobfuscated Play release without a reason
- Empty `GOOGLE_SERVER_CLIENT_ID` if Google login is a production feature
