import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:package_info_plus/package_info_plus.dart';
import 'package:url_launcher/url_launcher.dart';

enum UpdateKind { none, soft, force }

class AppUpdateInfo {
  const AppUpdateInfo({
    required this.kind,
    this.message,
    this.storeUrl,
  });

  final UpdateKind kind;
  final String? message;
  final String? storeUrl;

  static const none = AppUpdateInfo(kind: UpdateKind.none);
}

int compareSemver(String a, String b) {
  List<int> parts(String v) =>
      v.split('.').map((e) => int.tryParse(e.trim()) ?? 0).toList();
  final pa = parts(a);
  final pb = parts(b);
  final n = pa.length > pb.length ? pa.length : pb.length;
  for (var i = 0; i < n; i++) {
    final x = i < pa.length ? pa[i] : 0;
    final y = i < pb.length ? pb[i] : 0;
    if (x != y) return x.compareTo(y);
  }
  return 0;
}

Future<AppUpdateInfo> checkForUpdate(
  SiteRemoteSource site, {
  String? locale,
}) async {
  try {
    final result = await site.fetch(locale: locale);
    final app = result.data?.app;
    if (app == null) return AppUpdateInfo.none;

    final platform = !kIsWeb && Platform.isIOS ? app.ios : app.android;
    if (platform == null) return AppUpdateInfo.none;

    final info = await PackageInfo.fromPlatform();
    final current = info.version;
    final latest = platform.latest;
    final min = platform.min;

    if (compareSemver(current, min) < 0) {
      return AppUpdateInfo(
        kind: UpdateKind.force,
        message: app.message,
        storeUrl: platform.storeUrl,
      );
    }
    if (app.forceUpdate && compareSemver(current, latest) < 0) {
      return AppUpdateInfo(
        kind: UpdateKind.force,
        message: app.message,
        storeUrl: platform.storeUrl,
      );
    }
    if (compareSemver(current, latest) < 0) {
      return AppUpdateInfo(
        kind: UpdateKind.soft,
        message: app.message,
        storeUrl: platform.storeUrl,
      );
    }
  } on DioException {
    // fail open
  } catch (_) {
    // fail open
  }
  return AppUpdateInfo.none;
}

Future<void> openStoreUrl(String? url) async {
  if (url == null || url.isEmpty) return;
  final uri = Uri.tryParse(url);
  if (uri == null) return;
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}

String get appUpdateApiHint => Env.apiBaseUrl;
