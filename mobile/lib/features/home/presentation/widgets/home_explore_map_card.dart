import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/features/discover/domain/map_discovery.dart';
import 'package:fun_mobile/features/discover/presentation/discover_map_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

/// Lazy, non-interactive preview of the existing Explore V4 map data.
class HomeExploreMapCard extends ConsumerStatefulWidget {
  const HomeExploreMapCard({super.key, this.locationLabel});

  final String? locationLabel;

  @override
  ConsumerState<HomeExploreMapCard> createState() => _HomeExploreMapCardState();
}

class _HomeExploreMapCardState extends ConsumerState<HomeExploreMapCard> {
  static const _egyptCenter = LatLng(26.8206, 30.8025);
  bool _requested = false;
  bool _tileFailed = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_requested) return;
    WidgetsBinding.instance.addPostFrameCallback((_) => _scheduleLoad());
  }

  void _scheduleLoad() {
    if (!mounted || _requested) return;
    if (Scrollable.recommendDeferredLoadingForContext(context)) {
      Future<void>.delayed(const Duration(milliseconds: 250), _scheduleLoad);
      return;
    }
    _requested = true;
    _load();
  }

  Future<void> _load() async {
    if (!mounted) return;
    final location = ref.read(discoveryLocationControllerProvider);
    final hasOrigin = location.deviceLat != null && location.deviceLng != null;
    final lat = location.deviceLat;
    final lng = location.deviceLng;
    final bounds = hasOrigin
        ? MapBounds(
            north: lat! + 1.1,
            south: lat - 1.1,
            east: lng! + 1.1,
            west: lng - 1.1,
          )
        : const MapBounds(
            north: MapBounds.egyptMaxLat,
            south: MapBounds.egyptMinLat,
            east: MapBounds.egyptMaxLng,
            west: MapBounds.egyptMinLng,
          );
    await ref
        .read(discoverMapControllerProvider.notifier)
        .searchArea(
          bounds,
          zoom: hasOrigin ? 9 : 6,
          includeApproximate: true,
          discoveryMode: location.mode.name,
          latitude: lat,
          longitude: lng,
          distanceKm: location.distanceKm,
        );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final map = ref.watch(discoverMapControllerProvider);
    final location = ref.watch(discoveryLocationControllerProvider);
    final hasOrigin = location.deviceLat != null && location.deviceLng != null;
    final center = hasOrigin
        ? LatLng(location.deviceLat!, location.deviceLng!)
        : _egyptCenter;
    final failed = map.error != null || _tileFailed;

    return Padding(
      padding: const EdgeInsetsDirectional.fromSTEB(20, 8, 20, 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            l10n.homeMapTitle,
            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800),
          ),
          const SizedBox(height: 2),
          Text(
            l10n.homeMapSubtitle,
            style: TextStyle(
              fontSize: 12,
              color: AppColors.muted,
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 10),
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: SizedBox(
                height: 240, // Compact Home preview (220–260)
                child: Stack(
                  children: [
                    Positioned.fill(
                      child: failed
                          ? _MapFallback(
                              label:
                                  widget.locationLabel ?? location.summaryLabel,
                            )
                          : FlutterMap(
                              options: MapOptions(
                                initialCenter: center,
                                initialZoom: hasOrigin ? 9 : 6,
                                minZoom: 4,
                                maxZoom: 12,
                                interactionOptions: const InteractionOptions(
                                  flags: InteractiveFlag.none,
                                ),
                                onTap: (_, _) => context.go('/discover'),
                              ),
                              children: [
                                TileLayer(
                                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                                  userAgentPackageName: 'com.idigital.fun',
                                  maxZoom: 19,
                                  errorTileCallback: (_, _, _) {
                                    if (!mounted || _tileFailed) return;
                                    WidgetsBinding.instance
                                        .addPostFrameCallback((_) {
                                          if (mounted) {
                                            setState(() => _tileFailed = true);
                                          }
                                        });
                                  },
                                ),
                                MarkerLayer(
                                  markers: [
                                    for (final marker in map.markers)
                                      Marker(
                                        point: LatLng(
                                          marker.latitude,
                                          marker.longitude,
                                        ),
                                        width: 32,
                                        height: 32,
                                        child: const Icon(
                                          Icons.location_on,
                                          color: AppColors.accent,
                                          size: 30,
                                        ),
                                      ),
                                    for (final city in map.cityAggregates)
                                      Marker(
                                        point: LatLng(
                                          city.latitude,
                                          city.longitude,
                                        ),
                                        width: 42,
                                        height: 42,
                                        child: _MapCount(count: city.count),
                                      ),
                                    for (final cluster in map.clusters)
                                      Marker(
                                        point: LatLng(
                                          cluster.latitude,
                                          cluster.longitude,
                                        ),
                                        width: 42,
                                        height: 42,
                                        child: _MapCount(count: cluster.count),
                                      ),
                                  ],
                                ),
                              ],
                            ),
                    ),
                    if (map.isLoading)
                      const PositionedDirectional(
                        top: 12,
                        start: 12,
                        child: SizedBox(
                          width: 22,
                          height: 22,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        ),
                      ),
                    PositionedDirectional(
                      start: 12,
                      top: 12,
                      end: 12,
                      child: IgnorePointer(
                        child: Align(
                          alignment: AlignmentDirectional.topEnd,
                          child: Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 9,
                              vertical: 5,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.92),
                              borderRadius: BorderRadius.circular(999),
                            ),
                            child: Text(
                              widget.locationLabel ?? location.summaryLabel,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    PositionedDirectional(
                      end: 12,
                      bottom: 12,
                      child: FilledButton(
                        onPressed: () => context.go('/discover'),
                        style: FilledButton.styleFrom(
                          backgroundColor: AppColors.primary,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(
                            horizontal: 14,
                            vertical: 8,
                          ),
                          textStyle: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        child: Text(l10n.homeMapOpen),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _MapCount extends StatelessWidget {
  const _MapCount({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: BoxDecoration(
        color: AppColors.primary,
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 2),
      ),
      alignment: Alignment.center,
      child: Text(
        count > 99 ? '99+' : '$count',
        style: const TextStyle(
          color: Colors.white,
          fontSize: 11,
          fontWeight: FontWeight.w900,
        ),
      ),
    );
  }
}

class _MapFallback extends StatelessWidget {
  const _MapFallback({required this.label});

  final String label;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => context.go('/discover'),
      child: DecoratedBox(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
            colors: [Color(0xFFF1EBFF), Color(0xFFFFF1E7)],
          ),
        ),
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                Icons.map_outlined,
                size: 36,
                color: AppColors.primary.withValues(alpha: 0.85),
              ),
              const SizedBox(height: 8),
              Text(
                label,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF19213D),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
