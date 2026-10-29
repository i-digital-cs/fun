# Android Closed Testing — Windows Handoff

Static preparation only. This document does **not** claim a local AAB was built, signed, or uploaded.

**App identity (do not change):** `applicationId` / namespace `com.funnity.app`  
**Current `pubspec.yaml` version:** `1.0.2+3` (`versionName` 1.0.2 / `versionCode` 3)  
**Flutter project directory:** `mobile/` (repository root is the Laravel monorepo)

---

## 1. Environment requirements

| Tool | Requirement |
|------|-------------|
| Flutter SDK | **3.47.x** (or any stable that ships **Dart ≥ 3.13.1** — `pubspec.yaml` requires `sdk: ^3.13.1`) |
| Dart SDK | Comes with Flutter; must satisfy `^3.13.1` |
| JDK | **17** (Android `compileOptions` / Kotlin `jvmTarget` are Java 17) |
| Android SDK | Install via Android Studio; set `ANDROID_HOME` / `sdk.dir` |
| Gradle | Wrapper uses **Gradle 9.3.1**; AGP **9.1.0**; Kotlin **2.4.0** (do not silently upgrade) |
| Firebase | Keep existing `mobile/android/app/google-services.json` (do not regenerate) |

### Environment variables / defines

| Name | Purpose |
|------|---------|
| `API_BASE_URL` | Default `https://funnity.com/api` — use this for store builds |
| `FLAVOR` | Default `prod` |
| `GOOGLE_SERVER_CLIENT_ID` | Optional override; production Web client ID is already defaulted in `Env` |

Release builds throw if `API_BASE_URL` points at localhost / `10.0.2.2` (`Env.assertSafeForRelease()`).

---

## 2. Project setup (Windows)

```bat
git clone <repo-url> funnity.com
cd funnity.com
git checkout main
git pull

cd mobile
flutter --version
flutter pub get
```

Create `mobile/android/local.properties` if missing (Android Studio usually writes this):

```properties
sdk.dir=C:\\Users\\<YOU>\\AppData\\Local\\Android\\Sdk
flutter.sdk=C:\\path\\to\\flutter
```

Do **not** commit `local.properties` or `key.properties`.

---

## 3. Signing

- Template: `mobile/android/key.properties.example`
- Expected file (local only): `mobile/android/key.properties`
- Release packaging **fails closed** without `key.properties` (BUILD-001)
- Local non-store smoke only: `-PallowDebugReleaseSigning=true` (never for Play)

Provide keystore path + passwords out of band (password manager). Never paste secrets into git, chat logs, or this file.

---

## 4. Versioning

1. Open Google Play Console → Funnity → Closed testing track.
2. Note the **highest versionCode** already uploaded.
3. Set the next code in `mobile/pubspec.yaml` as `version: X.Y.Z+N` where `N` is **strictly greater** than that Play value.
4. Do **not** guess `N` from this handoff. Current source is `1.0.2+3` only as a repo baseline.

---

## 5. Validation commands (run on Windows before build)

```bat
cd mobile
flutter pub get
dart format --output=none --set-exit-if-changed .
flutter analyze
flutter test
```

Expect `flutter analyze` to report zero **errors** (existing infos/warnings may remain).

---

## 6. Release build (do this on Windows)

```bat
cd mobile
flutter build appbundle --release --obfuscate --split-debug-info=build/debug-info ^
  --dart-define=FLAVOR=prod ^
  --dart-define=API_BASE_URL=https://funnity.com/api
```

Requires valid `android/key.properties` + upload keystore.

---

## 7. Artifact

Expected AAB path:

```text
mobile/build/app/outputs/bundle/release/app-release.aab
```

Keep `build/debug-info/` for crash deobfuscation uploads if you use Play / Crashlytics symbolication.

---

## 8. Google Play Closed Testing

1. Play Console → the **existing** Funnity Android app (`com.funnity.app`).
2. Testing → Closed testing → create a **new release** on the existing track (do not create a new app or a new track).
3. Upload `app-release.aab`.
4. Paste release notes (EN + AR below).
5. Review → roll out to testers.

---

## 9. Release notes (implemented Flutter features)

### English

- More reliable chat voice notes: pause/resume while recording, preview before send, and retry with the same message id so duplicates are avoided.
- Event chat now supports voice notes and images with authenticated private media playback.
- Discover / Living Map continues to use Explore V4 (exact venues vs approximate city-level events).
- Stability fixes for authenticated multipart uploads and session handling.

### العربية

- ملاحظات صوتية أكثر موثوقية في المحادثات: إيقاف مؤقت/استئناف أثناء التسجيل، معاينة قبل الإرسال، وإعادة المحاولة دون تكرار الرسالة.
- دردشة الفعاليات تدعم الآن الملاحظات الصوتية والصور مع تشغيل الوسائط الخاصة بشكل آمن.
- الاكتشاف / الخريطة الحية يستمران عبر Explore V4 (إحداثيات دقيقة مقابل تقريبي على مستوى المدينة).
- إصلاحات لاستقرار رفع الملفات والمصادقة.

---

## 10. Known limitations (require device testing)

- Physical Android voice record → upload → peer playback (DM + event) — **not verified on this server**.
- FCM foreground/background/cold-start deep links — static wiring only.
- Living Map visual parity vs web MapLibre V3.3 — Flutter uses `flutter_map`; device review required.
- Communities product surface is **not** implemented in Flutter (no member Communities API in current Laravel `api.php` either).
- Optimistic “pending bubble” UX on Flutter is lighter than web (retry + draft retention; not full progress chrome).
- Do not claim iOS readiness from this Android Closed Testing handoff.
