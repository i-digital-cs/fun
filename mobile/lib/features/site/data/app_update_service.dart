import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';
import 'package:fun_mobile/core/utils/safe_url.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/features/site/domain/app_update.dart';
import 'package:package_info_plus/package_info_plus.dart';

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

Future<void> openStoreUrl(String? url) => launchSafeExternalUrl(url);
