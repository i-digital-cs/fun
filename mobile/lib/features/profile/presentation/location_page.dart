import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class LocationPage extends ConsumerStatefulWidget {
  const LocationPage({super.key});

  @override
  ConsumerState<LocationPage> createState() => _LocationPageState();
}

class _LocationPageState extends ConsumerState<LocationPage> {
  CatalogBundle? _catalog;
  String? _error;
  bool _loading = true;
  int? _cityId;
  int? _distanceKm;
  bool _saving = false;
  bool _useDevice = false;
  bool _requestingGps = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final auth = ref.read(authControllerProvider);
    if (auth is Authenticated) {
      _cityId = auth.user.cityId;
      _distanceKm = auth.user.distanceKm;
    }
    final location = ref.read(discoveryLocationServiceProvider);
    _useDevice = location.useDeviceLocationPreferred;
    final result = await ref.read(catalogRepositoryProvider).catalog();
    if (!mounted) return;
    setState(() {
      _loading = false;
      _error = result.failure?.message;
      _catalog = result.data;
      _cityId ??= result.data?.cities.firstOrNull?.id;
      _distanceKm ??= result.data?.distancesKm.firstOrNull ?? 10;
    });
  }

  Future<void> _enableDeviceLocation() async {
    setState(() => _requestingGps = true);
    final location = ref.read(discoveryLocationServiceProvider);
    final pos = await location.obtainForegroundPosition();
    if (!mounted) return;
    if (pos == null) {
      setState(() {
        _requestingGps = false;
        _useDevice = false;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            Localizations.localeOf(context).languageCode == 'ar'
                ? 'تعذر الحصول على الموقع. يمكنك الاستمرار باختيار المدينة.'
                : 'Could not get location. You can keep using city selection.',
          ),
        ),
      );
      return;
    }
    await location.setUseDeviceLocationPreferred(true);
    setState(() {
      _requestingGps = false;
      _useDevice = true;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          Localizations.localeOf(context).languageCode == 'ar'
              ? 'سيتم استخدام موقع الجهاز للاستكشاف (مرة واحدة عند الفتح فقط).'
              : 'Device location will be used for discovery (one-shot while open).',
        ),
      ),
    );
  }

  Future<void> _disableDeviceLocation() async {
    await ref
        .read(discoveryLocationServiceProvider)
        .setUseDeviceLocationPreferred(false);
    setState(() => _useDevice = false);
  }

  Future<void> _save() async {
    if (_cityId == null || _distanceKm == null) return;
    setState(() => _saving = true);
    final result = await ref
        .read(profileRepositoryProvider)
        .updateLocation(cityId: _cityId!, distanceKm: _distanceKm!);
    if (!mounted) return;
    if (result.failure != null) {
      setState(() => _saving = false);
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(result.failure!.message)));
      return;
    }
    await ref.read(authControllerProvider.notifier).refreshMe();
    if (!mounted) return;
    setState(() => _saving = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).locationSaved)),
    );
    context.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final catalog = _catalog;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.location)),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            LocationFormCard(
              cities: catalog?.cities ?? const [],
              distancesKm: catalog?.distancesKm ?? const [5, 10, 25, 50],
              cityId: _cityId,
              distanceKm: _distanceKm,
              onCityChanged: (v) => setState(() => _cityId = v),
              onDistanceChanged: (v) => setState(() => _distanceKm = v),
            ),
            const SizedBox(height: 16),
            SwitchListTile.adaptive(
              contentPadding: EdgeInsets.zero,
              title: Text(
                Localizations.localeOf(context).languageCode == 'ar'
                    ? 'استخدم موقعي الحالي للاستكشاف'
                    : 'Use my current location for discovery',
              ),
              subtitle: Text(
                Localizations.localeOf(context).languageCode == 'ar'
                    ? 'إذن المقدمة فقط — بدون تتبع في الخلفية. المدينة تبقى احتياطيًا.'
                    : 'Foreground permission only — no background tracking. City remains the fallback.',
                style: const TextStyle(fontSize: 12, color: AppColors.muted),
              ),
              value: _useDevice,
              onChanged: _requestingGps
                  ? null
                  : (v) async {
                      if (v) {
                        await _enableDeviceLocation();
                      } else {
                        await _disableDeviceLocation();
                      }
                    },
            ),
            if (_requestingGps)
              const Padding(
                padding: EdgeInsets.only(bottom: 12),
                child: LinearProgressIndicator(),
              ),
            const SizedBox(height: 12),
            GradientButton.indigo(
              label: l10n.save,
              isLoading: _saving,
              onPressed: _cityId != null && _distanceKm != null ? _save : null,
            ),
          ],
        ),
      ),
    );
  }
}

class LocationFormCard extends StatelessWidget {
  const LocationFormCard({
    super.key,
    required this.cities,
    required this.distancesKm,
    required this.cityId,
    required this.distanceKm,
    required this.onCityChanged,
    required this.onDistanceChanged,
  });

  final List<CityItem> cities;
  final List<int> distancesKm;
  final int? cityId;
  final int? distanceKm;
  final ValueChanged<int?> onCityChanged;
  final ValueChanged<int?> onDistanceChanged;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.locationSub,
            style: const TextStyle(color: AppColors.muted, height: 1.4),
          ),
          const SizedBox(height: 20),
          DropdownButtonFormField<int>(
            key: ValueKey('city-$cityId'),
            initialValue: cityId,
            decoration: InputDecoration(labelText: l10n.selectCity),
            items: cities
                .map((c) => DropdownMenuItem(value: c.id, child: Text(c.name)))
                .toList(),
            onChanged: onCityChanged,
          ),
          const SizedBox(height: 16),
          Text(
            l10n.distanceKm,
            style: const TextStyle(
              fontWeight: FontWeight.w600,
              color: AppColors.text,
            ),
          ),
          const SizedBox(height: 8),
          DistanceKmSelector(
            values: distancesKm,
            selected: distanceKm,
            onChanged: onDistanceChanged,
          ),
        ],
      ),
    );
  }
}

class DistanceKmSelector extends StatelessWidget {
  const DistanceKmSelector({
    super.key,
    required this.values,
    required this.selected,
    required this.onChanged,
  });

  final List<int> values;
  final int? selected;
  final ValueChanged<int?> onChanged;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: values.map((km) {
        final isSelected = selected == km;
        final label = km == 0
            ? (Localizations.localeOf(context).languageCode == 'ar'
                ? 'كل المسافات'
                : 'All distances')
            : l10n.withinKm(km);
        return ChoiceChip(
          label: Text(label),
          selected: isSelected,
          selectedColor: AppColors.primarySoft,
          onSelected: (_) => onChanged(km),
        );
      }).toList(),
    );
  }
}
