# Firebase Cloud Messaging (outside-app push)

Server FCM is already configured (`FIREBASE_*` in `.env`).  
The Flutter app needs a **mobile** Firebase config file so it can obtain an FCM device token and `POST /api/device-tokens`.

## 1. Create / open the Firebase Android app

1. Open [Firebase Console](https://console.firebase.google.com/) → project **funnity-157aa** (same as the service account).
2. Android app package: `com.funnity.app`
3. Place config at:

```text
mobile/android/app/google-services.json
```

(This file was generated for the project — rebuild the app after pull.)

Do **not** commit secrets from service accounts; `google-services.json` is a client config (ok for the app repo if your policy allows).

## 2. iOS (optional)

1. Add an iOS app in the same Firebase project.
2. Download **`GoogleService-Info.plist`** into `mobile/ios/Runner/`.
3. Enable Push Notifications + Background Modes → Remote notifications in Xcode.
4. Upload APNs key in Firebase → Project settings → Cloud Messaging.

## 3. Rebuild

```bash
cd mobile
flutter pub get
flutter run
```

On first launch after login, allow notification permission.  
Then check the API / DB: `device_tokens` should have a row for your user.

## 4. What gets pushed

| Event | Recipient | Channels |
|-------|-----------|----------|
| Join request | Event owner | mail + inbox + **FCM** |
| Join accepted | Applicant | inbox + **FCM** |
| Chat message | Other members | Pusher toast + **FCM** (no message body text) |

## 5. Foreground vs background

| App state | What the user sees |
|-----------|--------------------|
| **Open (foreground)** | No system tray banner. Same sweet in-app toast as web (chat + join accept/request) + badge. Pusher is primary; FCM data is a deduped fallback. |
| **Background / killed** | OS shows the FCM notification (Android: Funnity white status icon + indigo tint). |

## 6. Android notification icon

Android **requires** a white-on-transparent status icon (`ic_stat_funity`). Your full-color app logo cannot appear in the status bar; it can appear as the **large/expanded** image only if the site logo URL is PNG/JPEG/WebP (SVG is skipped).

## 7. Without `google-services.json`

The app still runs. In-app inbox works. Outside push is skipped (debug log: “Firebase not configured”).
