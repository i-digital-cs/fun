import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/fun_searching.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/core/location/discovery_location_service.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discover_header_scroll.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_location_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discovery_query_controller.dart';
import 'package:fun_mobile/features/discover/presentation/widgets/discover_map_view.dart';
import 'package:fun_mobile/features/discover/presentation/widgets/location_picker_sheet.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

enum _DiscoverViewMode { map, list }

/// Mirrors Blade `events/index` discover filters + nationwide notice.
/// Explore V4 defaults to map-first with list toggle.
class DiscoverPage extends ConsumerStatefulWidget {
  const DiscoverPage({super.key});

  @override
  ConsumerState<DiscoverPage> createState() => _DiscoverPageState();
}

class _DiscoverPageState extends ConsumerState<DiscoverPage> {
  final _search = TextEditingController();
  final _scroll = ScrollController();
  List<CityItem> _cities = const [];
  List<CategoryItem> _categories = const [];
  List<int> _distances = const [5, 10, 25, 50];
  int? _draftCityId;
  int? _draftCategoryId;
  int? _draftDistanceKm;
  String? _draftWhen;
  bool _draftAllCities = true;
  bool _catalogLoaded = false;
  // Map-first: start collapsed so the map viewport is usable on phone.
  final _headerVisible = ValueNotifier<bool>(false);
  double _lastScroll = 0;
  _DiscoverViewMode _viewMode = _DiscoverViewMode.map;

  static const _headerScrollKey = PageStorageKey<String>('discover-scroll');

  @override
  void initState() {
    super.initState();
    _scroll.addListener(_onScroll);
    Future.microtask(() async {
      await _loadCatalog();
      await ref.read(discoverControllerProvider.notifier).ensureLoaded();
      _syncDraftFromState();
    });
  }

  @override
  void dispose() {
    _scroll.removeListener(_onScroll);
    _scroll.dispose();
    _search.dispose();
    _headerVisible.dispose();
    super.dispose();
  }

  void _onScroll() {
    if (!_scroll.hasClients) return;
    final offset = _scroll.offset;
    final delta = offset - _lastScroll;
    _lastScroll = offset;
    final next = discoverHeaderVisibleAfterScroll(
      currentlyVisible: _headerVisible.value,
      offset: offset,
      delta: delta,
    );
    // ValueNotifier — avoids rebuilding the entire Discover scaffold on scroll.
    if (next != _headerVisible.value) {
      _headerVisible.value = next;
    }
  }

  Future<void> _loadCatalog() async {
    final citiesResult = await ref.read(catalogRepositoryProvider).catalog();
    final categoriesResult = await ref
        .read(catalogRepositoryProvider)
        .categories();
    if (!mounted) return;
    setState(() {
      _catalogLoaded = true;
      if (citiesResult.data != null) {
        _cities = citiesResult.data!.cities;
        _distances = citiesResult.data!.distancesKm.isEmpty
            ? _distances
            : citiesResult.data!.distancesKm;
      }
      if (categoriesResult.data != null) {
        _categories = categoriesResult.data!;
      }
    });
  }

  void _syncDraftFromState() {
    final filters = ref.read(discoverControllerProvider).filters;
    final query = ref.read(discoveryQueryControllerProvider);
    setState(() {
      _search.text = query.normalizedQ ?? filters.q ?? '';
      _draftAllCities = filters.allCities;
      _draftCityId = filters.cityId;
      _draftCategoryId = query.categoryId ?? filters.categoryId;
      _draftDistanceKm = filters.distanceKm ?? _distances.firstOrNull ?? 25;
      _draftWhen = query.when ?? filters.when;
    });
  }

  /// Shared Map+List apply: location SSOT + query SSOT + list reload.
  /// Map listens to location/query providers and refreshes markers.
  Future<void> _apply() async {
    final locNotifier =
        ref.read(discoveryLocationControllerProvider.notifier);
    final before = ref.read(discoveryLocationControllerProvider);

    if (_draftDistanceKm != null) {
      await locNotifier.setDistanceKm(_draftDistanceKm!);
    }

    // Sync filter-panel city edits into the location controller, but never
    // overwrite an active in-region device-GPS mode unless the user cleared to
    // nationwide or picked a different city in the legacy city dropdown.
    // Also never re-select the same manual city without coords — that would
    // wipe centroid lat/lng set by the location picker (Mansoura-under-New-Cairo).
    final deviceActive = before.mode == DiscoveryLocationMode.device &&
        !before.outsideDiscoveryRegion;
    final draftCityChanged = !_draftAllCities &&
        _draftCityId != null &&
        _draftCityId != before.cityId;
    if (_draftAllCities && (before.hasManualCity || deviceActive)) {
      await locNotifier.setNationwide(distanceKm: _draftDistanceKm);
    } else if (draftCityChanged) {
      final city = _cities.where((c) => c.id == _draftCityId).firstOrNull;
      if (city != null) {
        await locNotifier.selectManualCity(
          cityId: city.id,
          name: city.name,
          distanceKm: _draftDistanceKm,
          latitude: city.latitude,
          longitude: city.longitude,
        );
      }
    } else if (!deviceActive &&
        _draftCityId != null &&
        !before.hasInRegionOrigin &&
        before.cityId == _draftCityId) {
      // Same city but missing origin coords — enrich from catalog when possible.
      final city = _cities.where((c) => c.id == _draftCityId).firstOrNull;
      if (city?.latitude != null && city?.longitude != null) {
        await locNotifier.selectManualCity(
          cityId: city!.id,
          name: city.name,
          distanceKm: _draftDistanceKm,
          latitude: city.latitude,
          longitude: city.longitude,
        );
      }
    }

    ref.read(discoveryQueryControllerProvider.notifier).apply(
          q: _search.text,
          categoryId: _draftCategoryId,
          when: _draftWhen,
          clearCategory: _draftCategoryId == null,
          clearWhen: _draftWhen == null || _draftWhen!.isEmpty,
        );

    final query = ref.read(discoveryQueryControllerProvider);
    final loc = ref.read(discoveryLocationControllerProvider);
    final filters = loc.toDiscoverFilters(
      q: query.normalizedQ,
      categoryId: query.categoryId,
      when: query.when,
    );
    await ref.read(discoverControllerProvider.notifier).applyFilters(
          q: filters.q,
          cityId: filters.cityId,
          categoryId: filters.categoryId,
          distanceKm: filters.distanceKm,
          when: filters.when,
          allCities: filters.allCities,
          latitude: filters.latitude,
          longitude: filters.longitude,
          discoveryMode: filters.discoveryMode,
        );
  }

  Future<void> _cancelJoin(int eventId) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmCancelJoin,
    );
    if (confirm != true || !mounted) return;
    final error = await ref
        .read(discoverControllerProvider.notifier)
        .cancelJoinRequest(eventId);
    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error)));
    }
  }

  Future<void> _clear() async {
    final km = _distances.contains(25)
        ? 25
        : (_distances.firstOrNull ?? 25);
    setState(() {
      _search.clear();
      _draftAllCities = true;
      _draftCityId = null;
      _draftCategoryId = null;
      _draftDistanceKm = km;
      _draftWhen = null;
    });
    ref.read(discoveryQueryControllerProvider.notifier).clear();
    await ref
        .read(discoveryLocationControllerProvider.notifier)
        .setNationwide(distanceKm: km);
    await ref.read(discoverControllerProvider.notifier).clearFilters();
  }

  Future<void> _useMyLocation() async {
    await showLocationPickerSheet(context);
    if (!mounted) return;
    final loc = ref.read(discoveryLocationControllerProvider);
    setState(() {
      _draftAllCities = !loc.hasManualCity &&
          loc.mode != DiscoveryLocationMode.device;
      _draftCityId = loc.cityId;
      _draftDistanceKm = loc.distanceKm;
    });
    await _apply();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final state = ref.watch(discoverControllerProvider);
    final auth = ref.watch(authControllerProvider);
    final loc = ref.watch(discoveryLocationControllerProvider);
    final canCreate = auth is Authenticated && auth.user.canParticipate;
    final isAuthed = auth is Authenticated;
    final radiusLabel = loc.radiusLabel();

    // Under MainShell the topbar already applies SafeArea(top). Re-applying
    // top inset here left a large blank band (web Explore V4 has none).
    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: SafeArea(
        top: false,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Compact always-on Map/List control (never steals map viewport).
            Padding(
              padding: const EdgeInsetsDirectional.fromSTEB(20, 8, 20, 0),
              child: Row(
                children: [
                  Expanded(
                    child: ValueListenableBuilder<bool>(
                      valueListenable: _headerVisible,
                      builder: (context, visible, _) {
                        if (!visible) {
                          return Text(
                            l10n.discoverTitle,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.w800,
                            ),
                          );
                        }
                        return const SizedBox.shrink();
                      },
                    ),
                  ),
                  SegmentedButton<_DiscoverViewMode>(
                    style: ButtonStyle(
                      visualDensity: VisualDensity.compact,
                      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                      padding: WidgetStateProperty.all(
                        const EdgeInsets.symmetric(horizontal: 10),
                      ),
                    ),
                    segments: const [
                      ButtonSegment(
                        value: _DiscoverViewMode.map,
                        icon: Icon(Icons.map_outlined, size: 18),
                        label: Text('Map'),
                      ),
                      ButtonSegment(
                        value: _DiscoverViewMode.list,
                        icon: Icon(Icons.view_list_outlined, size: 18),
                        label: Text('List'),
                      ),
                    ],
                    selected: {_viewMode},
                    onSelectionChanged: (next) {
                      setState(() {
                        _viewMode = next.first;
                        // Map defaults to collapsed title chrome for viewport.
                        if (_viewMode == _DiscoverViewMode.map) {
                          _headerVisible.value = false;
                        } else {
                          _headerVisible.value = true;
                        }
                      });
                    },
                  ),
                ],
              ),
            ),
            // List-only title/subtitle collapses on scroll; Map keeps viewport.
            if (_viewMode == _DiscoverViewMode.list)
              ValueListenableBuilder<bool>(
                valueListenable: _headerVisible,
                builder: (context, visible, child) {
                  return AnimatedSize(
                    duration: const Duration(milliseconds: 160),
                    curve: Curves.easeOutCubic,
                    alignment: Alignment.topCenter,
                    child: visible
                        ? child!
                        : const SizedBox(width: double.infinity),
                  );
                },
                child: Padding(
                  padding: const EdgeInsetsDirectional.fromSTEB(20, 8, 20, 0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Text(
                        l10n.discoverTitle,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 22,
                          fontWeight: FontWeight.w800,
                          letterSpacing: -0.4,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        l10n.discoverSub,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppColors.muted,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            // Location + search stay usable on Map and List (single SSOT field).
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
              child: InkWell(
                onTap: _useMyLocation,
                borderRadius: BorderRadius.circular(14),
                child: Ink(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(14),
                    border: Border.all(color: AppColors.borderSoft),
                  ),
                  child: Row(
                    children: [
                      const Icon(
                        Icons.place_outlined,
                        size: 18,
                        color: AppColors.primary,
                      ),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Text(
                          loc.mode == DiscoveryLocationMode.nationwide
                              ? loc.summaryLabel
                              : '${loc.summaryLabel} · $radiusLabel',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      const Icon(
                        Icons.keyboard_arrow_down_rounded,
                        color: AppColors.muted,
                      ),
                    ],
                  ),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 8, 20, 0),
              child: TextField(
                controller: _search,
                textInputAction: TextInputAction.search,
                onSubmitted: (_) => _apply(),
                decoration: InputDecoration(
                  hintText: l10n.searchPlaceholder,
                  prefixIcon: const Icon(Icons.search_rounded, size: 20),
                  suffixIcon: IconButton(
                    tooltip: l10n.applyFilters,
                    onPressed: _apply,
                    icon: const Icon(Icons.tune_rounded, size: 20),
                  ),
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 12,
                    vertical: 10,
                  ),
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: AppColors.borderSoft),
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(14),
                    borderSide: const BorderSide(color: AppColors.borderSoft),
                  ),
                ),
              ),
            ),
            if (loc.outsideDiscoveryRegion)
              Padding(
                padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
                child: Material(
                  color: const Color(0xFFFFF7ED),
                  borderRadius: BorderRadius.circular(12),
                  child: Padding(
                    padding: const EdgeInsets.all(12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Text(
                          l10n.discoveryOutOfRegion,
                          style: const TextStyle(
                            color: Color(0xFF9A3412),
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                        const SizedBox(height: 8),
                        Wrap(
                          spacing: 8,
                          runSpacing: 8,
                          children: [
                            FilledButton.tonal(
                              onPressed: _useMyLocation,
                              child: Text(l10n.chooseCity),
                            ),
                            OutlinedButton(
                              onPressed: () async {
                                await ref
                                    .read(
                                      discoveryLocationControllerProvider
                                          .notifier,
                                    )
                                    .setNationwide(
                                      distanceKm: _draftDistanceKm,
                                    );
                                await _apply();
                              },
                              child: Text(l10n.exploreEgypt),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            Expanded(
              child: _viewMode == _DiscoverViewMode.map
                  ? DiscoverMapView(
                      onOpenLocationPicker: _useMyLocation,
                      onExploreEgypt: () async {
                        await ref
                            .read(discoveryLocationControllerProvider.notifier)
                            .setNationwide(distanceKm: _draftDistanceKm);
                        await _apply();
                      },
                    )
                  : RefreshIndicator(
                color: AppColors.primary,
                onRefresh: () =>
                    ref.read(discoverControllerProvider.notifier).load(),
                child: CustomScrollView(
                  key: _headerScrollKey,
                  controller: _scroll,
                  slivers: [
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
                        child: DiscoverFilterPanel(
                          searchController: _search,
                          catalogLoaded: _catalogLoaded,
                          cities: _cities,
                          categories: _categories,
                          distances: _distances,
                          allCities: _draftAllCities,
                          cityId: _draftCityId,
                          categoryId: _draftCategoryId,
                          distanceKm: _draftDistanceKm,
                          when: _draftWhen,
                          onAllCitiesChanged: (v) => setState(() {
                            _draftAllCities = v;
                            if (v) _draftCityId = null;
                          }),
                          onCityChanged: (id) => setState(() {
                            _draftCityId = id;
                            _draftAllCities = id == null;
                          }),
                          onCategoryChanged: (id) =>
                              setState(() => _draftCategoryId = id),
                          onDistanceChanged: (km) =>
                              setState(() => _draftDistanceKm = km),
                          onWhenChanged: (v) => setState(() => _draftWhen = v),
                          onApply: _apply,
                          onClear: _clear,
                        ),
                      ),
                    ),
                    if (state.isSearching)
                      const SliverToBoxAdapter(
                        child: Padding(
                          padding: EdgeInsets.fromLTRB(20, 0, 20, 12),
                          child: Center(
                            child: FunSearching(
                              size: FunSearchingSize.sm,
                              compact: true,
                            ),
                          ),
                        ),
                      ),
                    if (state.isSuggestion)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                          child: Material(
                            color: const Color(0xFFFFFBEB),
                            borderRadius: BorderRadius.circular(12),
                            child: Padding(
                              padding: const EdgeInsets.all(12),
                              child: Text(
                                (state.effectiveRadiusKm ?? 0) == 0
                                    ? l10n.discoverySuggestionNationwide(
                                        state.selectedRadiusKm ?? 25,
                                      )
                                    : l10n.discoverySuggestionExpanded(
                                        state.selectedRadiusKm ?? 25,
                                        state.effectiveRadiusKm ?? 25,
                                      ),
                                style: const TextStyle(
                                  color: Color(0xFF78350F),
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ),
                          ),
                        ),
                      )
                    else if (state.isNationwide)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                          child: NationwideNotice(text: l10n.nationwideNotice),
                        ),
                      ),
                    if (state.isLoading && state.items.isEmpty)
                      const SliverFillRemaining(
                        hasScrollBody: false,
                        child: Center(child: FunLoader()),
                      )
                    else if (state.error != null && state.items.isEmpty)
                      SliverFillRemaining(
                        hasScrollBody: false,
                        child: Padding(
                          padding: const EdgeInsets.all(24),
                          child: Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Text(
                                state.error!,
                                textAlign: TextAlign.center,
                                style: const TextStyle(color: AppColors.muted),
                              ),
                              TextButton(
                                onPressed: () => ref
                                    .read(discoverControllerProvider.notifier)
                                    .load(),
                                child: Text(l10n.retry),
                              ),
                            ],
                          ),
                        ),
                      )
                    else if (state.items.isEmpty)
                      SliverToBoxAdapter(
                        child: DiscoverEmptyState(
                          onCreate: () {
                            if (canCreate) {
                              context.go('/create');
                              return;
                            }
                            requireAuth(
                              context,
                              auth,
                              returnTo: '/create',
                            );
                          },
                          onShowAll: _clear,
                          onChangeLocation: _useMyLocation,
                        ),
                      )
                    else
                      SliverPadding(
                        padding: const EdgeInsets.fromLTRB(20, 0, 20, 100),
                        sliver: SliverList.separated(
                          itemCount:
                              state.items.length + (state.hasMore ? 1 : 0),
                          separatorBuilder: (_, _) =>
                              const SizedBox(height: 16),
                          itemBuilder: (context, index) {
                            if (index >= state.items.length) {
                              WidgetsBinding.instance.addPostFrameCallback((_) {
                                ref
                                    .read(discoverControllerProvider.notifier)
                                    .loadMore();
                              });
                              return const Padding(
                                padding: EdgeInsets.all(16),
                                child: Center(
                                  child: FunLoader(
                                    size: FunLoaderSize.sm,
                                    showLabel: false,
                                  ),
                                ),
                              );
                            }
                            final event = state.items[index];
                            return EventCard(
                              event: event,
                              onView: () => context.push('/events/${event.id}'),
                              onFavorite: isAuthed
                                  ? () => ref
                                        .read(
                                          discoverControllerProvider.notifier,
                                        )
                                        .toggleFavorite(event.id)
                                  : null,
                              onCancelJoin:
                                  isAuthed &&
                                      event.joinAction == 'request_pending'
                                  ? () => _cancelJoin(event.id)
                                  : null,
                            );
                          },
                        ),
                      ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class DiscoverFilterPanel extends StatelessWidget {
  const DiscoverFilterPanel({
    super.key,
    required this.searchController,
    required this.catalogLoaded,
    required this.cities,
    required this.categories,
    required this.distances,
    required this.allCities,
    required this.cityId,
    required this.categoryId,
    required this.distanceKm,
    required this.when,
    required this.onAllCitiesChanged,
    required this.onCityChanged,
    required this.onCategoryChanged,
    required this.onDistanceChanged,
    required this.onWhenChanged,
    required this.onApply,
    required this.onClear,
  });

  final TextEditingController searchController;
  final bool catalogLoaded;
  final List<CityItem> cities;
  final List<CategoryItem> categories;
  final List<int> distances;
  final bool allCities;
  final int? cityId;
  final int? categoryId;
  final int? distanceKm;
  final String? when;
  final ValueChanged<bool> onAllCitiesChanged;
  final ValueChanged<int?> onCityChanged;
  final ValueChanged<int?> onCategoryChanged;
  final ValueChanged<int?> onDistanceChanged;
  final ValueChanged<String?> onWhenChanged;
  final VoidCallback onApply;
  final VoidCallback onClear;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Search lives in Discover chrome (Map+List SSOT) — avoid duplicate field.
          DropdownButtonFormField<int?>(
            // ignore: deprecated_member_use
            value: categoryId,
            decoration: InputDecoration(labelText: l10n.category),
            items: [
              DropdownMenuItem<int?>(
                value: null,
                child: Text(l10n.allCategories),
              ),
              ...categories.map(
                (c) => DropdownMenuItem<int?>(value: c.id, child: Text(c.name)),
              ),
            ],
            onChanged: catalogLoaded ? onCategoryChanged : null,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<String?>(
            // ignore: deprecated_member_use
            value: when,
            decoration: InputDecoration(labelText: l10n.whenFilter),
            items: [
              DropdownMenuItem<String?>(value: null, child: Text(l10n.whenAny)),
              DropdownMenuItem<String?>(
                value: 'today',
                child: Text(l10n.today),
              ),
              DropdownMenuItem<String?>(
                value: 'tomorrow',
                child: Text(l10n.tomorrow),
              ),
              DropdownMenuItem<String?>(
                value: 'weekend',
                child: Text(l10n.weekend),
              ),
            ],
            onChanged: onWhenChanged,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int?>(
            // ignore: deprecated_member_use
            value: allCities ? null : cityId,
            decoration: InputDecoration(labelText: l10n.city),
            items: [
              DropdownMenuItem<int?>(value: null, child: Text(l10n.allCities)),
              ...cities.map(
                (c) => DropdownMenuItem<int?>(value: c.id, child: Text(c.name)),
              ),
            ],
            onChanged: catalogLoaded
                ? (id) {
                    if (id == null) {
                      onAllCitiesChanged(true);
                      onCityChanged(null);
                    } else {
                      onAllCitiesChanged(false);
                      onCityChanged(id);
                    }
                  }
                : null,
          ),
          if (!allCities) ...[
            const SizedBox(height: 12),
            Text(
              l10n.discoveryDistance,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.muted,
              ),
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: distances.map((km) {
                final selected = distanceKm == km;
                return ChoiceChip(
                  label: Text(l10n.withinKm(km)),
                  selected: selected,
                  selectedColor: AppColors.primarySoft,
                  onSelected: (_) => onDistanceChanged(km),
                );
              }).toList(),
            ),
          ],
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: GradientButton.indigo(
                  label: l10n.applyFilters,
                  onPressed: onApply,
                ),
              ),
              const SizedBox(width: 10),
              OutlinedButton(
                onPressed: onClear,
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 14,
                  ),
                  side: BorderSide(
                    color: AppColors.border.withValues(alpha: 0.8),
                  ),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: Text(l10n.clearFilters),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class NationwideNotice extends StatelessWidget {
  const NationwideNotice({super.key, required this.text});

  final String text;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF0F9FF),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFBAE6FD)),
      ),
      child: Text(
        text,
        style: const TextStyle(
          fontSize: 13,
          color: Color(0xFF075985),
          height: 1.35,
        ),
      ),
    );
  }
}

class DiscoverEmptyState extends StatelessWidget {
  const DiscoverEmptyState({
    super.key,
    this.onCreate,
    required this.onShowAll,
    required this.onChangeLocation,
  });

  final VoidCallback? onCreate;
  final VoidCallback onShowAll;
  final VoidCallback onChangeLocation;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 8, 20, 100),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(28),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(24),
          border: Border.all(color: AppColors.border.withValues(alpha: 0.7)),
        ),
        child: Column(
          children: [
            Text(
              l10n.emptyDiscover,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.muted, fontSize: 15),
            ),
            const SizedBox(height: 20),
            if (onCreate != null) ...[
              GradientButton(
                label: l10n.createEvent,
                icon: Icons.add,
                onPressed: onCreate,
              ),
              const SizedBox(height: 10),
            ],
            TextButton(onPressed: onShowAll, child: Text(l10n.showAllEvents)),
            TextButton(
              onPressed: onChangeLocation,
              child: Text(l10n.changeLocation),
            ),
          ],
        ),
      ),
    );
  }
}
