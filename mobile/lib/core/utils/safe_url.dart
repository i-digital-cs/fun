import 'package:url_launcher/url_launcher.dart';

/// Opens only http(s) URLs externally (SEC-URL-001).
Future<bool> launchSafeExternalUrl(String? raw) async {
  if (raw == null || raw.trim().isEmpty) return false;
  final uri = Uri.tryParse(raw.trim());
  if (uri == null) return false;
  if (uri.scheme != 'http' && uri.scheme != 'https') return false;
  // Do not gate on canLaunchUrl — on Android 11+ it often returns false for
  // https unless <queries> lists VIEW intents, which silently blocked Maps.
  try {
    return await launchUrl(uri, mode: LaunchMode.externalApplication);
  } catch (_) {
    return false;
  }
}
