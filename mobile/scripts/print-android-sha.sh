#!/usr/bin/env bash
# Print debug keystore SHA-1 / SHA-256 for Firebase Android app registration.
set -euo pipefail
KS="${1:-$HOME/.android/debug.keystore}"
KEYTOOL="${KEYTOOL:-keytool}"
if ! command -v "$KEYTOOL" >/dev/null 2>&1; then
  for c in "$HOME/jdk/bin/keytool" /usr/lib/jvm/*/bin/keytool; do
    [ -x "$c" ] && KEYTOOL="$c" && break
  done
fi
echo "Keystore: $KS"
"$KEYTOOL" -list -v -keystore "$KS" -alias androiddebugkey -storepass android -keypass android 2>/dev/null \
  | grep -E 'SHA1:|SHA256:'
