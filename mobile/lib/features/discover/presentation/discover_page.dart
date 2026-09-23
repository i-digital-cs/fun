import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Mirrors Blade `events/index` discover filters + nationwide notice.
class DiscoverPage extends ConsumerStatefulWidget {
  const DiscoverPage({super.key});

  @override
  ConsumerState<DiscoverPage> createState() => _DiscoverPageState();
}

class _DiscoverPageState extends ConsumerState<DiscoverPage> {
  final _search = TextEditingController();
  List<CityItem> _cities = const [];
  List<CategoryItem> _categories = const [];
  List<int> _distances = const [5, 10, 25, 50];
  int? _draftCityId;
  int? _draftCategoryId;
  int? _draftDistanceKm;
  bool _draftAllCities = true;
  bool _catalogLoaded = false;

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      await _loadCatalog();
      await ref.read(discoverControllerProvider.notifier).ensureLoaded();
      _syncDraftFromState();
    });
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  Future<void> _loadCatalog() async {
    final citiesResult = await ref.read(catalogRepositoryProvider).catalog();
    final categoriesResult =
        await ref.read(catalogRepositoryProvider).categories();
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
    setState(() {
      _search.text = filters.q ?? '';
      _draftAllCities = filters.allCities;
      _draftCityId = filters.cityId;
      _draftCategoryId = filters.categoryId;
      _draftDistanceKm = filters.distanceKm ?? _distances.firstOrNull ?? 25;
    });
  }

  Future<void> _apply() async {
    await ref.read(discoverControllerProvider.notifier).applyFilters(
          q: _search.text.trim().isEmpty ? null : _search.text.trim(),
          cityId: _draftCityId,
          categoryId: _draftCategoryId,
          distanceKm: _draftDistanceKm,
          allCities: _draftAllCities,
        );
  }

  Future<void> _clear() async {
    setState(() {
      _search.clear();
      _draftAllCities = true;
      _draftCityId = null;
      _draftCategoryId = null;
      _draftDistanceKm = _distances.firstOrNull ?? 25;
    });
    await ref.read(discoverControllerProvider.notifier).clearFilters();
  }

  Future<void> _useMyLocation() async {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated || auth.user.cityId == null) {
      await context.push('/settings/location');
      return;
    }
    setState(() {
      _draftAllCities = false;
      _draftCityId = auth.user.cityId;
      _draftDistanceKm = auth.user.distanceKm ?? _distances.firstOrNull ?? 25;
    });
    await _apply();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final state = ref.watch(discoverControllerProvider);
    final auth = ref.watch(authControllerProvider);
    final canCreate =
        auth is Authenticated && auth.user.canParticipate;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: SafeArea(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          l10n.discoverTitle,
                          style: const TextStyle(
                            fontSize: 25,
                            fontWeight: FontWeight.w800,
                            letterSpacing: -0.4,
                          ),
                        ),
                        const SizedBox(height: 4),
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
                  if (canCreate)
                    GradientButton(
                      label: l10n.createEvent,
                      icon: Icons.add,
                      onPressed: () => context.go('/create'),
                    ),
                ],
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                color: AppColors.primary,
                onRefresh: () =>
                    ref.read(discoverControllerProvider.notifier).load(),
                child: CustomScrollView(
                  slivers: [
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
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
                          onApply: _apply,
                          onClear: _clear,
                        ),
                      ),
                    ),
                    if (state.isSearching)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 8),
                          child: Row(
                            children: [
                              Icon(
                                Icons.wifi_find,
                                size: 16,
                                color: AppColors.primary.withValues(alpha: 0.8),
                              ),
                              const SizedBox(width: 8),
                              Text(
                                l10n.searching,
                                style: const TextStyle(
                                  fontSize: 12,
                                  fontWeight: FontWeight.w600,
                                  color: AppColors.muted,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    if (state.isNationwide)
                      SliverToBoxAdapter(
                        child: Padding(
                          padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                          child: NationwideNotice(text: l10n.nationwideNotice),
                        ),
                      ),
                    if (state.isLoading && state.items.isEmpty)
                      const SliverFillRemaining(
                        hasScrollBody: false,
                        child: Center(
                          child: FunLoader(),
                        ),
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
                          onCreate: canCreate
                              ? () => context.go('/create')
                              : null,
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
                            final authed =
                                ref.watch(authControllerProvider) is Authenticated;
                            return EventCard(
                              event: event,
                              onView: () =>
                                  context.push('/events/${event.id}'),
                              onFavorite: authed
                                  ? () => ref
                                      .read(discoverControllerProvider.notifier)
                                      .toggleFavorite(event.id)
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
    required this.onAllCitiesChanged,
    required this.onCityChanged,
    required this.onCategoryChanged,
    required this.onDistanceChanged,
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
  final ValueChanged<bool> onAllCitiesChanged;
  final ValueChanged<int?> onCityChanged;
  final ValueChanged<int?> onCategoryChanged;
  final ValueChanged<int?> onDistanceChanged;
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
          TextField(
            controller: searchController,
            textInputAction: TextInputAction.search,
            onSubmitted: (_) => onApply(),
            decoration: InputDecoration(
              labelText: l10n.search,
              hintText: l10n.heroActivityPh,
              prefixIcon: const Icon(Icons.search),
            ),
          ),
          const SizedBox(height: 12),
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
                (c) => DropdownMenuItem<int?>(
                  value: c.id,
                  child: Text(c.name),
                ),
              ),
            ],
            onChanged: catalogLoaded ? onCategoryChanged : null,
          ),
          const SizedBox(height: 12),
          DropdownButtonFormField<int?>(
            // ignore: deprecated_member_use
            value: allCities ? null : cityId,
            decoration: InputDecoration(labelText: l10n.city),
            items: [
              DropdownMenuItem<int?>(
                value: null,
                child: Text(l10n.allCities),
              ),
              ...cities.map(
                (c) => DropdownMenuItem<int?>(
                  value: c.id,
                  child: Text(c.name),
                ),
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
            TextButton(
              onPressed: onShowAll,
              child: Text(l10n.showAllEvents),
            ),
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
