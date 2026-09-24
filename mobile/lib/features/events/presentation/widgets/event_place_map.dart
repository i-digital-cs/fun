import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:latlong2/latlong.dart';

/// Default Cairo center (matches Blade Leaflet picker).
const kDefaultPlaceCenter = LatLng(30.0444, 31.2357);

String osmMapsUrl(double lat, double lng) =>
    'https://www.openstreetmap.org/?mlat=$lat&mlon=$lng#map=16/$lat/$lng';

/// Interactive OSM pin picker — Blade `data-event-place-picker` / Leaflet.
class EventPlaceMapPicker extends StatefulWidget {
  const EventPlaceMapPicker({
    super.key,
    this.latitude,
    this.longitude,
    required this.onChanged,
    this.height = 220,
  });

  final double? latitude;
  final double? longitude;
  final void Function(double lat, double lng) onChanged;
  final double height;

  @override
  State<EventPlaceMapPicker> createState() => _EventPlaceMapPickerState();
}

class _EventPlaceMapPickerState extends State<EventPlaceMapPicker> {
  late final MapController _map = MapController();
  LatLng? _pin;

  @override
  void initState() {
    super.initState();
    if (widget.latitude != null && widget.longitude != null) {
      _pin = LatLng(widget.latitude!, widget.longitude!);
    }
  }

  @override
  void didUpdateWidget(covariant EventPlaceMapPicker oldWidget) {
    super.didUpdateWidget(oldWidget);
    final lat = widget.latitude;
    final lng = widget.longitude;
    if (lat == null || lng == null) {
      if (_pin != null) setState(() => _pin = null);
      return;
    }
    final next = LatLng(lat, lng);
    if (_pin == null ||
        (_pin!.latitude - lat).abs() > 1e-6 ||
        (_pin!.longitude - lng).abs() > 1e-6) {
      setState(() => _pin = next);
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (!mounted) return;
        try {
          _map.move(next, _map.camera.zoom < 14 ? 15 : _map.camera.zoom);
        } catch (_) {}
      });
    }
  }

  void _place(LatLng point) {
    setState(() => _pin = point);
    widget.onChanged(point.latitude, point.longitude);
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final center = _pin ?? kDefaultPlaceCenter;
    final zoom = _pin != null ? 15.0 : 11.0;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(14),
          child: SizedBox(
            height: widget.height,
            child: FlutterMap(
              mapController: _map,
              options: MapOptions(
                initialCenter: center,
                initialZoom: zoom,
                minZoom: 3,
                maxZoom: 19,
                onTap: (_, point) => _place(point),
              ),
              children: [
                TileLayer(
                  urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                  userAgentPackageName: 'com.idigital.fun',
                  maxZoom: 19,
                ),
                if (_pin != null)
                  MarkerLayer(
                    markers: [
                      Marker(
                        point: _pin!,
                        width: 44,
                        height: 44,
                        alignment: Alignment.topCenter,
                        child: const Icon(
                          Icons.location_on,
                          size: 44,
                          color: Color(0xFFDC2626),
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
          ),
        ),
        const SizedBox(height: 6),
        Text(
          l10n.placeTapHint,
          style: const TextStyle(
            fontSize: 11,
            color: AppColors.muted,
            fontWeight: FontWeight.w500,
          ),
        ),
      ],
    );
  }
}

/// Read-only OSM preview — Blade `data-event-place-viewer`.
class EventPlaceMapViewer extends StatelessWidget {
  const EventPlaceMapViewer({
    super.key,
    required this.latitude,
    required this.longitude,
    this.height = 200,
  });

  final double latitude;
  final double longitude;
  final double height;

  @override
  Widget build(BuildContext context) {
    final point = LatLng(latitude, longitude);

    return ClipRRect(
      borderRadius: BorderRadius.circular(16),
      child: SizedBox(
        height: height,
        child: IgnorePointer(
          child: FlutterMap(
            options: MapOptions(
              initialCenter: point,
              initialZoom: 15,
              interactionOptions: const InteractionOptions(
                flags: InteractiveFlag.none,
              ),
            ),
            children: [
              TileLayer(
                urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                userAgentPackageName: 'com.idigital.fun',
                maxZoom: 19,
              ),
              MarkerLayer(
                markers: [
                  Marker(
                    point: point,
                    width: 44,
                    height: 44,
                    alignment: Alignment.topCenter,
                    child: const Icon(
                      Icons.location_on,
                      size: 44,
                      color: Color(0xFFDC2626),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}
