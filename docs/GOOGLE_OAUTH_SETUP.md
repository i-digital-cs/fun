# Google Sign-In / OAuth setup (Web + Flutter)

Use this to configure **Web Socialite**, **Flutter Google Sign-In**, and server verification of ID tokens (`POST /api/auth/google`).

## 1. Google Cloud Console

1. Open [Google Cloud Console](https://console.cloud.google.com/) → create/select a project.
2. **APIs & Services → OAuth consent screen**
   - User type: External (or Internal for Workspace-only)
   - App name, support email, authorized domains (`funnity.com` if production)
   - Scopes: `email`, `profile`, `openid`
3. **APIs & Services → Credentials → Create credentials → OAuth client ID**

Create **three** clients (typical setup):

| Client | Type | Used for |
|--------|------|----------|
| **Web** | Web application | Laravel Socialite + Flutter `serverClientId` (ID token audience) |
| **Android** | Android | Flutter Android app |
| **iOS** | iOS | Flutter iOS app |

### Web client (production: funnity.com)

In Google Cloud Console → Credentials → your **Web** OAuth client, set:

**Authorized JavaScript origins:**
- `https://funnity.com`
- `https://www.funnity.com` (if www is used)
- `http://localhost:8000` (local only)

**Authorized redirect URIs:**
- `https://funnity.com/auth/google/callback`
- `https://www.funnity.com/auth/google/callback` (if www is used)
- `http://localhost:8000/auth/google/callback` (local only)

OAuth consent screen → Authorized domains: `funnity.com`.

Server `.env` must use lowercase `APP_URL=https://funnity.com` so the callback matches exactly.

Copy **Client ID** + **Client Secret** → `.env`:

```env
GOOGLE_CLIENT_ID=xxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=yyyyy
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

### Android client (needs SHA-1)

**Package / applicationId:** `com.funity.fun_mobile` (from `mobile/android/app/build.gradle.kts`).

This is the usual cause of: *“Google sign-in failed. Check that Google is configured for this app.”*  
(`ApiException: 10` / DEVELOPER_ERROR — missing Android OAuth client or wrong SHA-1.)

1. Get **SHA-1** fingerprints:

**Debug keystore (dev / `flutter run`):**
```bash
# Linux/macOS
keytool -list -v \
  -keystore ~/.android/debug.keystore \
  -alias androiddebugkey \
  -storepass android -keypass android
```

Server debug SHA-1 currently registered on Firebase app `funnity-157aa`:
```text
26:1A:5B:7E:A0:20:DC:35:6D:12:77:70:8B:6D:BE:65:22:BE:C1:C2
```
(If your machine’s `~/.android/debug.keystore` differs, use **your** SHA-1 instead.)

**Release keystore (Play / production):**
```bash
keytool -list -v -keystore /path/to/your-release.keystore -alias your-alias
```

**Play App Signing:** also add the **App signing key certificate SHA-1** from Google Play Console → Setup → App integrity (required for store builds).

2. **Create the Android OAuth client** (pick one):

**Option A — Firebase (recommended)**  
[Firebase Console](https://console.firebase.google.com/) → project **funnity-157aa** → Project settings → Your apps → Android `com.funity.fun_mobile`  
- Confirm SHA-1 / SHA-256 fingerprints are listed  
- **Authentication → Sign-in method → Google → Enable** (saves Web client ID/secret)  
- Download a fresh `google-services.json` into `mobile/android/app/`  
- Confirm the file’s `oauth_client` array is **not empty**

**Option B — Google Cloud Console**  
[Credentials](https://console.cloud.google.com/apis/credentials) → Create credentials → OAuth client ID → **Android**  
- Package name: `com.funity.fun_mobile`  
- SHA-1: debug + release/Play as needed  

3. Copy Android **Client ID** → server `.env`:

```env
GOOGLE_ANDROID_CLIENT_ID=xxxxx-android.apps.googleusercontent.com
```

Then `php artisan config:clear`. Without this, tokens whose `aud` is only the Android client ID will fail verification (tokens with Web `serverClientId` audience still work via `GOOGLE_CLIENT_ID`).

4. **Rebuild** the Flutter app (`flutter clean && flutter run`) so the new `google-services.json` / client is picked up.

### iOS client

1. Bundle ID from Xcode / `ios/Runner.xcodeproj` (e.g. `com.idigital.fun`).
2. Create OAuth client → iOS → bundle ID.
3. Copy iOS **Client ID** → `.env`:

```env
GOOGLE_IOS_CLIENT_ID=xxxxx-ios.apps.googleusercontent.com
```

Also add the reversed client ID URL scheme to iOS `Info.plist` (Flutter `google_sign_in` docs).

---

## 2. Laravel `.env`

```env
GOOGLE_CLIENT_ID=           # Web client ID (required)
GOOGLE_CLIENT_SECRET=       # Web client secret (required for web redirect login)
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
GOOGLE_ANDROID_CLIENT_ID=   # optional but recommended for Android ID tokens
GOOGLE_IOS_CLIENT_ID=       # optional but recommended for iOS ID tokens
```

Server accepts ID tokens whose `aud` matches **any** of those client IDs.

Then:
```bash
php artisan config:clear
```

---

## 3. API (Flutter)

`POST /api/auth/google`

```json
{
  "id_token": "<from Google Sign-In>",
  "locale": "en"
}
```

**Success:** Sanctum `token` + `user` (Google users are email-verified; usually `needs_identity_documents: true` for new accounts).

**Flutter sketch:**
```dart
final googleSignIn = GoogleSignIn(
  scopes: ['email', 'profile'],
  // MUST be the Web client ID so you get a usable idToken for the backend
  serverClientId: '<GOOGLE_CLIENT_ID web>',
);

final account = await googleSignIn.signIn();
final auth = await account?.authentication;
final idToken = auth?.idToken;

final res = await dio.post('/auth/google', data: {
  'id_token': idToken,
  'locale': 'en', // or 'ar'
});
// save res.data['token']
```

Package: [`google_sign_in`](https://pub.dev/packages/google_sign_in).

---

## 4. Web (already wired)

- Button: “Continue with Google” on login/register  
- Routes: `/auth/google/redirect` → `/auth/google/callback`  
- Needs `GOOGLE_CLIENT_ID` + `GOOGLE_CLIENT_SECRET` + redirect URI  

---

## 5. Checklist

- [ ] OAuth consent screen published (or test users added while in Testing)
- [ ] Web client ID + secret in `.env`
- [ ] Redirect URI matches `APP_URL` exactly (http vs https, **lowercase** host, no trailing slash mismatch)
- [ ] Production Web client includes `https://funnity.com/auth/google/callback` (and www if used)
- [ ] Android client: correct `applicationId` + **debug SHA-1** + **release/Play SHA-1**
- [ ] iOS client: correct bundle ID + URL scheme
- [ ] Flutter `serverClientId` = **Web** client ID
- [ ] `POST /api/auth/google` returns token (test in Postman with a real `id_token`)
- [ ] New Google user lands on identity upload (pending documents)

---

## 6. Common failures

| Symptom | Likely cause |
|---------|----------------|
| `Google Sign-In is not configured` | Empty `GOOGLE_CLIENT_ID` / client_ids |
| `token is invalid` | Wrong `serverClientId`, expired token, or SHA-1 / package mismatch |
| Android `ApiException: 10` | SHA-1 or package name not registered |
| Android `ApiException: 12500` | Consent / config / Play Services |
| Web redirect `redirect_uri_mismatch` | Callback URL not listed on Web client |
| No `idToken` in Flutter | Missing `serverClientId` (Web client ID) |

---

*Related: `POST /api/auth/google` · `config/services.php` · `docs/FLUTTER_WEB_PARITY.md`*
