import 'dart:math' as math;

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/features/discover/domain/map_discovery.dart';
import 'package:fun_mobile/features/discover/presentation/discover_map_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';

/// Egypt-centered map-first Discover surface (flutter_map + OSM tiles).
class DiscoverMapView extends ConsumerStatefulWidget {
  const DiscoverMapView({
    super.key,
    this.categoryId,
    this.query,
    this.when,
  });

  final int? categoryId;
  final String? query;
  final String? when;

  @override
  ConsumerState<DiscoverMapView> createState() => _DiscoverMapViewState();
}

class _DiscoverMapViewState extends ConsumerState<DiscoverMapView> {
  final _map = MapController();
  bool _loadedOnce = false;

  /// Nationwide / unknown-origin framing — used for first paint and resets.
  static final _egyptBounds = LatLngBounds(
    const LatLng(22.0, 24.9),
    const LatLng(31.75, 35.0),
  );

  static final _egyptInitialFit = CameraFit.bounds(
    bounds: _egyptBounds,
    padding: const EdgeInsets.all(28),
    maxZoom: 7,
  );

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _initialLoad());
  }

  @override
  void dispose() {
    _map.dispose();
    super.dispose();
  }

  double _zoomForRadius(int km) {
    if (km <= 5) return 13;
    if (km <= 10) return 12;
    if (km <= 25) return 11;
    if (km <= 50) return 10;
    if (km <= 100) return 9;
    return 8;
  }

  void _fitOrigin(double lat, double lng, int distanceKm) {
    final pad = (distanceKm <= 0 ? 25 : distanceKm) * 1.15;
    final latPad = pad / 111.0;
    final lngPad = pad / (111.0 * math.max(0.2, math.cos(lat * math.pi / 180)));
    _map.fitCamera(
      CameraFit.bounds(
        bounds: LatLngBounds(
          LatLng(lat - latPad, lng - lngPad),
          LatLng(lat + latPad, lng + lngPad),
        ),
        padding: const EdgeInsets.all(28),
        maxZoom: _zoomForRadius(distanceKm <= 0 ? 25 : distanceKm),
      ),
    );
  }

  Future<void> _initialLoad() async {
    if (_loadedOnce || !mounted) return;
    _loadedOnce = true;
    final loc = ref.read(discoveryLocationControllerProvider);
    if (loc.hasDeviceFix ||
        (loc.mode == DiscoveryLocationMode.manual &&
            loc.deviceLat != null &&
            loc.deviceLng != null)) {
      _fitOrigin(loc.deviceLat!, loc.deviceLng!, loc.distanceKm);
    } else {
      // Manual city without coords, or nationwide — Egypt envelope.
      _map.fitCamera(_egyptInitialFit);
    }
    await _searchCurrentArea();
  }

  MapBounds _boundsFromCamera() {
    final bounds = _map.camera.visibleBounds;
    return MapBounds(
      north: bounds.north,
      south: bounds.south,
      east: bounds.east,
      west: bounds.west,
    );
  }

  Future<void> _searchCurrentArea() async {
    final zoom = _map.camera.zoom.round().clamp(4, 16);
    final loc = ref.read(discoveryLocationControllerProvider);
    final originAware = (loc.mode == DiscoveryLocationMode.device ||
            loc.mode == DiscoveryLocationMode.manual) &&
        loc.deviceLat != null &&
        loc.deviceLng != null;
    await ref.read(discoverMapControllerProvider.notifier).searchArea(
          _boundsFromCamera(),
          zoom: zoom,
          categoryId: widget.categoryId,
          q: widget.query,
          when: widget.when,
          includeApproximate: false,
          discoveryMode: originAware ? loc.mode.name : null,
          latitude: originAware ? loc.deviceLat : null,
          longitude: originAware ? loc.deviceLng : null,
          distanceKm: originAware ? loc.distanceKm : null,
        );
  }

  void _onMapEvent(MapEvent event) {
    if (event is MapEventMoveEnd || event is MapEventFlingAnimationEnd) {
      ref.read(discoverMapControllerProvider.notifier).markMapMoved();
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(discoveryLocationControllerProvider, (prev, next) {
      if (!_loadedOnce || !mounted) return;
      final movedOrigin = prev?.deviceLat != next.deviceLat ||
          prev?.deviceLng != next.deviceLng ||
          prev?.mode != next.mode ||
          prev?.distanceKm != next.distanceKm;
      if (!movedOrigin) return;
      if ((next.mode == DiscoveryLocationMode.device ||
              next.mode == DiscoveryLocationMode.manual) &&
          next.deviceLat != null &&
          next.deviceLng != null) {
        _fitOrigin(next.deviceLat!, next.deviceLng!, next.distanceKm);
        _searchCurrentArea();
      } else if (next.mode == DiscoveryLocationMode.nationwide) {
        _map.fitCamera(_egyptInitialFit);
        _searchCurrentArea();
      }
    });

    final mapState = ref.watch(discoverMapControllerProvider);
    final loc = ref.watch(discoveryLocationControllerProvider);
    final selected = mapState.selectedMarker;

    // Single origin-sync path via the listen above (_fitOrigin). A second
    // listen that jumped to zoom 12 fought CameraFit and caused Africa→Egypt flash.

    return Stack(
      children: [
        FlutterMap(
          mapController: _map,
          options: MapOptions(
            // First paint already Egypt-framed (avoids continent flash before
            // post-frame fitCamera).
            initialCameraFit: _egyptInitialFit,
            minZoom: 4,
            maxZoom: 17,
            onMapEvent: _onMapEvent,
            onTap: (_, _) =>
                ref.read(discoverMapControllerProvider.notifier).clearSelection(),
          ),
          children: [
            TileLayer(
              urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
              // OSM requires an identifying UA; flutter_map prefixes
              // `flutter_map (<package>)`.
              userAgentPackageName: 'com.funnity.app',
              maxZoom: 19,
              errorTileCallback: (tile, error, stackTrace) {
                // Surface tile failures (gray map) in logcat — not a UX change.
                assert(() {
                  // ignore: avoid_print
                  print(
                    'FUNNITY_MAP_TILE_ERROR z=${tile.coordinates.z} '
                    'x=${tile.coordinates.x} y=${tile.coordinates.y} err=$error',
                  );
                  return true;
                }());
              },
            ),
            if ((loc.hasDeviceFix ||
                    (loc.mode == DiscoveryLocationMode.manual &&
                        loc.deviceLat != null &&
                        loc.deviceLng != null)) &&
                loc.distanceKm > 0)
              CircleLayer(
                circles: [
                  CircleMarker(
                    point: LatLng(loc.deviceLat!, loc.deviceLng!),
                    radius: loc.distanceKm * 1000.0,
                    useRadiusInMeter: true,
                    color: const Color(0x184F46E5),
                    borderStrokeWidth: 2,
                    borderColor: const Color(0x884F46E5),
                  ),
                  if (loc.hasDeviceFix &&
                      loc.accuracyM != null &&
                      loc.accuracyM! < 5000)
                    CircleMarker(
                      point: LatLng(loc.deviceLat!, loc.deviceLng!),
                      radius: loc.accuracyM!,
                      useRadiusInMeter: true,
                      color: const Color(0x223B82F6),
                      borderStrokeWidth: 1,
                      borderColor: const Color(0x883B82F6),
                    ),
                ],
              ),
            MarkerLayer(
              markers: [
                for (final c in mapState.clusters)
                  Marker(
                    point: LatLng(c.latitude, c.longitude),
                    width: 44,
                    height: 44,
                    child: GestureDetector(
                      onTap: () => _map.move(
                        LatLng(c.latitude, c.longitude),
                        c.expansionZoom.toDouble(),
                      ),
                      child: _ClusterBubble(count: c.count),
                    ),
                  ),
                for (final a in mapState.cityAggregates)
                  Marker(
                    point: LatLng(a.latitude, a.longitude),
                    width: a.isCluster ? 48 : 52,
                    height: a.isCluster ? 48 : 52,
                    child: GestureDetector(
                      onTap: () {
                        if (a.isCluster) {
                          _map.move(
                            LatLng(a.latitude, a.longitude),
                            a.expansionZoom.toDouble(),
                          );
                          return;
                        }
                        _map.move(LatLng(a.latitude, a.longitude), 11);
                        ref
                            .read(discoverMapControllerProvider.notifier)
                            .selectCityAggregate(
                              a,
                              categoryId: widget.categoryId,
                              q: widget.query,
                            );
                      },
                      child: Tooltip(
                        message: a.label ??
                            '${a.cityName} · ${a.count} city-level',
                        child: a.isCluster
                            ? _ClusterBubble(count: a.count)
                            : _CityAggregateBubble(
                                count: a.count,
                                name: a.cityName,
                              ),
                      ),
                    ),
                  ),
                for (final m in mapState.markers)
                  Marker(
                    point: LatLng(m.latitude, m.longitude),
                    width: 36,
                    height: 36,
                    child: GestureDetector(
                      onTap: () => ref
                          .read(discoverMapControllerProvider.notifier)
                          .selectMarker(m),
                      child: Icon(
                        Icons.location_on,
                        color: m.locationPrecision == 'approximate'
                            ? Colors.orange.shade800
                            : const Color(0xFFFF762E),
                        size: 34,
                      ),
                    ),
                  ),
                if ((loc.hasDeviceFix ||
                        (loc.mode == DiscoveryLocationMode.manual &&
                            loc.deviceLat != null &&
                            loc.deviceLng != null)))
                  Marker(
                    point: LatLng(loc.deviceLat!, loc.deviceLng!),
                    width: 28,
                    height: 28,
                    child: Tooltip(
                      message: loc.shortLabel ??
                          loc.locationLabel ??
                          'Current location',
                      child: Container(
                        decoration: BoxDecoration(
                          color: loc.hasDeviceFix
                              ? const Color(0xFF2563EB)
                              : const Color(0xFF4F46E5),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 3),
                          boxShadow: const [
                            BoxShadow(blurRadius: 4, color: Color(0x44000000)),
                          ],
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            const RichAttributionWidget(
              attributions: [
                TextSourceAttribution('OpenStreetMap contributors'),
              ],
            ),
          ],
        ),
        if (mapState.showSearchArea)
          Positioned(
            top: 12,
            left: 0,
            right: 0,
            child: Center(
              child: FilledButton.tonal(
                onPressed: mapState.isLoading ? null : _searchCurrentArea,
                child: Text(mapState.isLoading ? 'Searching…' : 'Search this area'),
              ),
            ),
          ),
        if (mapState.isLoading && !mapState.showSearchArea)
          const Positioned(
            top: 12,
            right: 12,
            child: SizedBox(
              width: 22,
              height: 22,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
          ),
        if (mapState.error != null)
          Positioned(
            left: 12,
            right: 12,
            top: mapState.showSearchArea ? 56 : 12,
            child: Material(
              elevation: 4,
              borderRadius: BorderRadius.circular(12),
              color: Colors.white,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 10),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      mapState.error!,
                      style: const TextStyle(
                        color: Color(0xFF991B1B),
                        fontWeight: FontWeight.w700,
                        fontSize: 13,
                      ),
                    ),
                    const SizedBox(height: 8),
                    FilledButton(
                      onPressed: mapState.isLoading ? null : _searchCurrentArea,
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            ),
          ),
        if (selected != null)
          Positioned(
            left: 12,
            right: 12,
            bottom: 16,
            child: _EventPreviewSheet(
              marker: selected,
              onClose: () =>
                  ref.read(discoverMapControllerProvider.notifier).clearSelection(),
              onOpen: () => context.push('/events/${selected.id}'),
            ),
          )
        else if (mapState.selectedCity != null &&
            !mapState.selectedCity!.isCluster)
          Positioned(
            left: 12,
            right: 12,
            bottom: 16,
            child: _CityEventsSheet(
              cityName: mapState.selectedCity!.cityName,
              events: mapState.cityEvents,
              loading: mapState.isLoading,
              onClose: () =>
                  ref.read(discoverMapControllerProvider.notifier).clearSelection(),
              onSelect: (ev) {
                if (ev.hasExactCoordinates &&
                    ev.latitude != null &&
                    ev.longitude != null) {
                  _map.move(LatLng(ev.latitude!, ev.longitude!), 14);
                  ref.read(discoverMapControllerProvider.notifier).selectMarker(
                        MapDiscoveryMarker(
                          id: ev.id,
                          latitude: ev.latitude!,
                          longitude: ev.longitude!,
                          title: ev.title,
                          locationPrecision: 'exact',
                          venueLabel: ev.venueLabel,
                        ),
                      );
                }
              },
              onOpen: (ev) => context.push('/events/${ev.id}'),
            ),
          ),
      ],
    );
  }
}

class _ClusterBubble extends StatelessWidget {
  const _ClusterBubble({required this.count});

  final int count;

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 40,
      height: 40,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: const Color(0xFF4C1D95),
        shape: BoxShape.circle,
        border: Border.all(color: Colors.white, width: 2.5),
        boxShadow: const [
          BoxShadow(blurRadius: 8, color: Color(0x470F172A)),
        ],
      ),
      child: Text(
        '$count',
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.w800,
          fontSize: 12,
        ),
      ),
    );
  }
}

class _CityAggregateBubble extends StatelessWidget {
  const _CityAggregateBubble({required this.count, required this.name});

  final int count;
  final String name;

  @override
  Widget build(BuildContext context) {
    final size = count >= 10 ? 42.0 : 36.0;
    return Semantics(
      label: '$count events in $name (city-level)',
      child: Container(
        width: size,
        height: size,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFF7138F5),
          shape: BoxShape.circle,
          border: Border.all(color: Colors.white, width: 2.5),
          boxShadow: const [
            BoxShadow(blurRadius: 8, color: Color(0x470F172A)),
          ],
        ),
        child: Text(
          '$count',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w800,
            fontSize: count >= 10 ? 13 : 12,
            letterSpacing: -0.2,
          ),
        ),
      ),
    );
  }
}

class _EventPreviewSheet extends StatelessWidget {
  const _EventPreviewSheet({
    required this.marker,
    required this.onClose,
    required this.onOpen,
  });

  final MapDiscoveryMarker marker;
  final VoidCallback onClose;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    return Material(
      elevation: 8,
      borderRadius: BorderRadius.circular(18),
      color: Colors.white,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    marker.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontWeight: FontWeight.w800,
                      fontSize: 16,
                    ),
                  ),
                ),
                IconButton(
                  onPressed: onClose,
                  icon: const Icon(Icons.close_rounded),
                ),
              ],
            ),
            if (marker.venueLabel != null && marker.venueLabel!.isNotEmpty)
              Text(
                marker.venueLabel!,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: AppColors.muted, fontSize: 13),
              ),
            const SizedBox(height: 6),
            Text(
              [
                if (marker.categoryName != null) marker.categoryName!,
                if (marker.membersCount > 0) '${marker.membersCount} going',
                if (marker.locationPrecision == 'approximate') 'Approximate area',
              ].join(' · '),
              style: const TextStyle(fontSize: 12, color: AppColors.muted),
            ),
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              child: FilledButton(
                onPressed: onOpen,
                child: const Text('View details'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _CityEventsSheet extends StatelessWidget {
  const _CityEventsSheet({
    required this.cityName,
    required this.events,
    required this.loading,
    required this.onClose,
    required this.onSelect,
    required this.onOpen,
  });

  final String cityName;
  final List<MapCityEvent> events;
  final bool loading;
  final VoidCallback onClose;
  final void Function(MapCityEvent event) onSelect;
  final void Function(MapCityEvent event) onOpen;

  @override
  Widget build(BuildContext context) {
    return Material(
      elevation: 8,
      borderRadius: BorderRadius.circular(18),
      color: Colors.white,
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxHeight: 280),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(14, 12, 14, 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      'Events in $cityName',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 16,
                      ),
                    ),
                  ),
                  IconButton(
                    onPressed: onClose,
                    icon: const Icon(Icons.close_rounded),
                  ),
                ],
              ),
              const Text(
                'City-level location (not an exact venue pin)',
                style: TextStyle(fontSize: 12, color: AppColors.muted),
              ),
              const SizedBox(height: 8),
              if (loading)
                const Padding(
                  padding: EdgeInsets.all(16),
                  child: Center(child: CircularProgressIndicator(strokeWidth: 2)),
                )
              else
                Expanded(
                  child: ListView.separated(
                    itemCount: events.length,
                    separatorBuilder: (_, _) => const Divider(height: 1),
                    itemBuilder: (context, index) {
                      final ev = events[index];
                      return ListTile(
                        dense: true,
                        contentPadding: EdgeInsets.zero,
                        title: Text(
                          ev.title,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          ev.hasExactCoordinates
                              ? (ev.venueLabel ?? 'Exact venue')
                              : 'Exact venue location unavailable — city-level only',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 11),
                        ),
                        onTap: () => onSelect(ev),
                        trailing: TextButton(
                          onPressed: () => onOpen(ev),
                          child: const Text('Open'),
                        ),
                      );
                    },
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
