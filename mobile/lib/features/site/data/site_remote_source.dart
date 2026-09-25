import 'package:dio/dio.dart';
import 'package:equatable/equatable.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/core/config/env.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/network/api_client.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';

class SitePlatformUpdate extends Equatable {
  const SitePlatformUpdate({
    required this.latest,
    required this.min,
    this.storeUrl,
  });

  final String latest;
  final String min;
  final String? storeUrl;

  @override
  List<Object?> get props => [latest, min, storeUrl];
}

class SiteAppUpdate extends Equatable {
  const SiteAppUpdate({
    required this.forceUpdate,
    this.message,
    this.android,
    this.ios,
  });

  final bool forceUpdate;
  final String? message;
  final SitePlatformUpdate? android;
  final SitePlatformUpdate? ios;

  @override
  List<Object?> get props => [forceUpdate, message, android, ios];
}

/// Public Reverb / Pusher client options from `GET /api/site` (never secret).
class SiteBroadcastConfig extends Equatable {
  const SiteBroadcastConfig({
    required this.enabled,
    this.driver,
    this.key,
    this.cluster,
    this.host,
    this.port,
    this.scheme,
  });

  final bool enabled;
  final String? driver;
  final String? key;
  final String? cluster;
  final String? host;
  final int? port;
  final String? scheme;

  static const disabled = SiteBroadcastConfig(enabled: false);

  /// Prefer compile-time `--dart-define` overrides, else API payload.
  factory SiteBroadcastConfig.resolve({SiteBroadcastConfig? fromApi}) {
    const defineKey = Env.reverbAppKey;
    const defineHost = Env.reverbHost;
    if (defineKey.isNotEmpty && defineHost.isNotEmpty) {
      final port = Env.reverbPort > 0
          ? Env.reverbPort
          : (fromApi?.port ?? 8080);
      final scheme = Env.reverbScheme.isNotEmpty
          ? Env.reverbScheme
          : (fromApi?.scheme ?? 'http');
      return SiteBroadcastConfig(
        enabled: true,
        driver: 'reverb',
        key: defineKey,
        host: defineHost,
        port: port,
        scheme: scheme,
      );
    }
    return fromApi ?? disabled;
  }

  /// WebSocket host for Reverb or Pusher Channels (`ws-{cluster}.pusher.com`).
  String? get wsHost {
    if (host != null && host!.isNotEmpty) return host;
    if (cluster != null && cluster!.isNotEmpty) {
      return 'ws-$cluster.pusher.com';
    }
    return null;
  }

  String get wsScheme => (scheme ?? 'https') == 'https' ? 'wss' : 'ws';

  int get wsPort => port ?? ((scheme ?? 'https') == 'https' ? 443 : 80);

  @override
  List<Object?> get props =>
      [enabled, driver, key, cluster, host, port, scheme];
}

class SiteBranding extends Equatable {
  const SiteBranding({
    required this.displayName,
    this.logoUrl,
    this.app,
    this.broadcast = SiteBroadcastConfig.disabled,
  });

  final String displayName;
  final String? logoUrl;
  final SiteAppUpdate? app;
  final SiteBroadcastConfig broadcast;

  /// Offline / error fallback — official wordmark only; mark comes from
  /// [SiteBrandMark] bundled asset until `logo_url` arrives from the API.
  static const fallback = SiteBranding(displayName: 'Funity');

  @override
  List<Object?> get props => [displayName, logoUrl, app, broadcast];
}

/// Prefer the live domain when the API still emits legacy asset hosts.
String? normalizeSiteLogoUrl(String? raw) {
  final url = raw?.trim();
  if (url == null || url.isEmpty) return null;
  final uri = Uri.tryParse(url);
  if (uri == null || !uri.hasScheme) return url;
  final host = uri.host.toLowerCase();
  if (host == 'fun.idigital-cs.com' || host == 'idigital-cs.com') {
    return uri.replace(host: 'www.funnity.com').toString();
  }
  return url;
}

class SiteRemoteSource {
  SiteRemoteSource(this._client);

  final ApiClient _client;

  Future<({Failure? failure, SiteBranding? data})> fetch({
    String? locale,
  }) async {
    try {
      final response = await _client.dio.get<Map<String, dynamic>>(
        '/site',
        queryParameters: {
          if (locale != null && locale.isNotEmpty) 'locale': locale,
        },
      );
      final json = response.data!;
      final appRaw = json['app'];
      SiteAppUpdate? app;
      if (appRaw is Map) {
        final map = Map<String, dynamic>.from(appRaw);
        SitePlatformUpdate? parsePlatform(dynamic raw) {
          if (raw is! Map) return null;
          final p = Map<String, dynamic>.from(raw);
          return SitePlatformUpdate(
            latest: '${p['latest'] ?? '1.0.0'}',
            min: '${p['min'] ?? '1.0.0'}',
            storeUrl: p['store_url'] as String?,
          );
        }

        app = SiteAppUpdate(
          forceUpdate: map['force_update'] == true,
          message: map['message'] as String?,
          android: parsePlatform(map['android']),
          ios: parsePlatform(map['ios']),
        );
      }

      SiteBroadcastConfig? broadcast;
      final broadcastRaw = json['broadcast'];
      if (broadcastRaw is Map) {
        final b = Map<String, dynamic>.from(broadcastRaw);
        broadcast = SiteBroadcastConfig(
          enabled: b['enabled'] == true,
          driver: b['driver'] as String?,
          key: b['key'] as String?,
          cluster: b['cluster'] as String?,
          host: b['host'] as String?,
          port: (b['port'] as num?)?.toInt(),
          scheme: b['scheme'] as String?,
        );
      }

      return (
        failure: null,
        data: SiteBranding(
          displayName:
              json['display_name'] as String? ?? SiteBranding.fallback.displayName,
          logoUrl: normalizeSiteLogoUrl(json['logo_url'] as String?),
          app: app,
          broadcast: SiteBroadcastConfig.resolve(fromApi: broadcast),
        ),
      );
    } on DioException catch (e) {
      return (failure: mapDioException(e), data: null);
    }
  }
}

final siteRemoteSourceProvider = Provider<SiteRemoteSource>((ref) {
  return SiteRemoteSource(ref.watch(apiClientProvider));
});

final siteBrandingProvider = FutureProvider<SiteBranding>((ref) async {
  final locale = ref.watch(localeCodeProvider);
  final result = await ref.read(siteRemoteSourceProvider).fetch(locale: locale);
  return result.data ??
      SiteBranding(
        displayName: SiteBranding.fallback.displayName,
        broadcast: SiteBroadcastConfig.resolve(),
      );
});
