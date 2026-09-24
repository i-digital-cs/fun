import 'package:url_launcher/url_launcher.dart';

/// Opens only http(s) URLs externally (SEC-URL-001).
Future<bool> launchSafeExternalUrl(String? raw) async {
  if (raw == null || raw.trim().isEmpty) return false;
  final uri = Uri.tryParse(raw.trim());
  if (uri == null) return false;
  if (uri.scheme != 'http' && uri.scheme != 'https') return false;
  if (!await canLaunchUrl(uri)) return false;
  return launchUrl(uri, mode: LaunchMode.externalApplication);
}
