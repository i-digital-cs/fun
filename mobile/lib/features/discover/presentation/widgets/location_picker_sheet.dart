import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Searchable location picker: manual city search OR explicit device detect.
Future<void> showLocationPickerSheet(BuildContext context) {
  return showModalBottomSheet<void>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    backgroundColor: AppColors.surface,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (context) => const _LocationPickerSheet(),
  );
}

class _LocationPickerSheet extends ConsumerStatefulWidget {
  const _LocationPickerSheet();

  @override
  ConsumerState<_LocationPickerSheet> createState() =>
      _LocationPickerSheetState();
}

class _LocationPickerSheetState extends ConsumerState<_LocationPickerSheet> {
  final _query = TextEditingController();
  Timer? _debounce;
  List<CityItem> _results = const [];
  bool _searching = false;
  String? _searchError;

  static const _distances = [5, 10, 25, 50, 100, 0];

  @override
  void initState() {
    super.initState();
    _query.addListener(_onQuery);
    Future.microtask(_runSearch);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _query.removeListener(_onQuery);
    _query.dispose();
    super.dispose();
  }

  void _onQuery() {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 280), _runSearch);
  }

  Future<void> _runSearch() async {
    setState(() {
      _searching = true;
      _searchError = null;
    });
    final result = await ref
        .read(catalogRepositoryProvider)
        .searchCities(_query.text.trim());
    if (!mounted) return;
    setState(() {
      _searching = false;
      if (result.failure != null) {
        _searchError = result.failure!.message;
        _results = const [];
      } else {
        _results = result.data ?? const [];
      }
    });
  }

  Future<void> _pickCity(CityItem city) async {
    final km = ref.read(discoveryLocationControllerProvider).distanceKm;
    await ref.read(discoveryLocationControllerProvider.notifier).selectManualCity(
          cityId: city.id,
          name: city.name,
          distanceKm: km,
        );
    if (mounted) Navigator.of(context).pop();
  }

  Future<void> _useDevice() async {
    final err = await ref
        .read(discoveryLocationControllerProvider.notifier)
        .enableDeviceLocation();
    if (!mounted) return;
    final loc = ref.read(discoveryLocationControllerProvider);
    if (loc.hasDeviceFix) {
      if (err != null) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
      }
      Navigator.of(context).pop();
      return;
    }
    if (err != null) {
      ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(err)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final loc = ref.watch(discoveryLocationControllerProvider);
    final bottom = MediaQuery.viewInsetsOf(context).bottom;

    return Padding(
      padding: EdgeInsets.fromLTRB(16, 12, 16, 16 + bottom),
      child: SizedBox(
        height: MediaQuery.sizeOf(context).height * 0.72,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Center(
              child: Container(
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: AppColors.border,
                  borderRadius: BorderRadius.circular(99),
                ),
              ),
            ),
            const SizedBox(height: 12),
            Text(
              l10n.location,
              style: const TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.w800,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              loc.mode == DiscoveryLocationMode.nationwide
                  ? loc.summaryLabel
                  : '${loc.summaryLabel} · ${loc.radiusLabel()}',
              style: const TextStyle(fontSize: 13, color: AppColors.muted),
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _query,
              autofocus: true,
              textInputAction: TextInputAction.search,
              decoration: InputDecoration(
                hintText: 'Search area (e.g. 6 October)',
                prefixIcon: const Icon(Icons.search_rounded),
                filled: true,
                fillColor: const Color(0xFFF7F9FC),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(14),
                  borderSide: const BorderSide(color: AppColors.borderSoft),
                ),
              ),
            ),
            const SizedBox(height: 10),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: [
                for (final km in _distances)
                  ChoiceChip(
                    label: Text(km == 0 ? 'All' : '$km km'),
                    selected: loc.distanceKm == km,
                    onSelected: (_) => ref
                        .read(discoveryLocationControllerProvider.notifier)
                        .setDistanceKm(km),
                  ),
              ],
            ),
            const SizedBox(height: 10),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: loc.isDetecting ? null : _useDevice,
                    icon: loc.isDetecting
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.my_location_rounded, size: 18),
                    label: Text(
                      loc.isDetecting ? 'Detecting…' : 'Use device location',
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: () async {
                    await ref
                        .read(discoveryLocationControllerProvider.notifier)
                        .setNationwide(distanceKm: loc.distanceKm);
                    if (context.mounted) Navigator.of(context).pop();
                  },
                  child: const Text('Nationwide'),
                ),
              ],
            ),
            if (loc.error != null) ...[
              const SizedBox(height: 8),
              Text(
                loc.error!,
                style: const TextStyle(color: AppColors.danger, fontSize: 12),
              ),
            ],
            const SizedBox(height: 8),
            Expanded(
              child: _searching && _results.isEmpty
                  ? const Center(child: FunLoader())
                  : _searchError != null
                  ? Center(child: Text(_searchError!))
                  : ListView.separated(
                      itemCount: _results.length,
                      separatorBuilder: (_, _) => const Divider(height: 1),
                      itemBuilder: (context, index) {
                        final city = _results[index];
                        final selected = loc.cityId == city.id &&
                            loc.mode == DiscoveryLocationMode.manual;
                        return ListTile(
                          leading: Icon(
                            selected
                                ? Icons.check_circle
                                : Icons.place_outlined,
                            color: selected
                                ? AppColors.primary
                                : AppColors.muted,
                          ),
                          title: Text(
                            city.name,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          subtitle: Text(
                            city.subtitle ?? 'Egypt',
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                          onTap: () => _pickCity(city),
                        );
                      },
                    ),
            ),
          ],
        ),
      ),
    );
  }
}
