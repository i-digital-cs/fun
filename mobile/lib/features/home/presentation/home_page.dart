import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discover_page.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/features/events/presentation/widgets/now_playing_card.dart';
import 'package:fun_mobile/features/home/presentation/home_nearby_controller.dart';
import 'package:fun_mobile/features/home/presentation/widgets/home_hero_banner.dart';
import 'package:fun_mobile/features/home/presentation/widgets/home_sections.dart';
import 'package:fun_mobile/features/home/presentation/widgets/location_shortcut_card.dart';
import 'package:fun_mobile/features/home/presentation/widgets/participations_section.dart';
import 'package:fun_mobile/features/home/presentation/widgets/people_suggestions_section.dart';
import 'package:fun_mobile/features/home/presentation/widgets/weather_card.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class HomePage extends ConsumerStatefulWidget {
  const HomePage({super.key});

  @override
  ConsumerState<HomePage> createState() => _HomePageState();
}

class _HomePageState extends ConsumerState<HomePage> {
  final _search = TextEditingController();

  @override
  void initState() {
    super.initState();
    Future.microtask(() async {
      await ref.read(homeNearbyControllerProvider.notifier).load();
      if (!mounted) return;
      await ref.read(peopleSuggestionsControllerProvider.notifier).load();
    });
  }

  @override
  void dispose() {
    _search.dispose();
    super.dispose();
  }

  String _greeting(AppLocalizations l10n, String name) {
    final hour = DateTime.now().hour;
    if (hour < 12) return l10n.greetingMorning(name);
    if (hour < 17) return l10n.greetingAfternoon(name);
    return l10n.greetingEvening(name);
  }

  Future<void> _openDiscoverAll({String? q, int? categoryId}) async {
    await ref
        .read(discoverControllerProvider.notifier)
        .loadNationwide(q: q, categoryId: categoryId);
    if (!mounted) return;
    context.go('/discover');
  }

  void _openCreate(bool canParticipate) {
    if (canParticipate) {
      context.go('/create');
    } else {
      context.push('/identity');
    }
  }

  Future<void> _cancelJoin(int eventId) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmCancelJoin,
    );
    if (confirm != true || !mounted) return;
    final error = await ref
        .read(homeNearbyControllerProvider.notifier)
        .cancelJoinRequest(eventId);
    if (!mounted) return;
    if (error != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(error)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final nearby = ref.watch(homeNearbyControllerProvider);
    final brand = ref.watch(siteBrandingProvider).maybeWhen(
      data: (s) => s,
      orElse: () => SiteBranding.fallback,
    );
    final user = auth is Authenticated ? auth.user : null;
    final firstName = user?.firstName ?? '';
    final canParticipate = user?.canParticipate ?? false;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      body: SafeArea(
        child: RefreshIndicator(
          color: AppColors.primary,
          onRefresh: () =>
              ref.read(homeNearbyControllerProvider.notifier).load(),
          child: CustomScrollView(
            slivers: [
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.end,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              _greeting(l10n, firstName),
                              style: const TextStyle(
                                fontSize: 22,
                                fontWeight: FontWeight.w800,
                                letterSpacing: -0.4,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              l10n.greetingSub,
                              style: const TextStyle(
                                fontSize: 13,
                                color: AppColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 12),
                      GradientButton(
                        label: l10n.createEvent,
                        icon: Icons.add,
                        onPressed: () => _openCreate(canParticipate),
                      ),
                    ],
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 0),
                  child: LocationShortcutCard(
                    cityName: user?.cityName,
                    distanceKm: user?.distanceKm ?? 25,
                    onTap: () async {
                      await context.push('/settings/location');
                      if (!mounted) return;
                      await ref
                          .read(homeNearbyControllerProvider.notifier)
                          .load();
                    },
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 0),
                  child: HomeHeroBanner(
                    searchController: _search,
                    onFind: () => _openDiscoverAll(
                      q: _search.text.trim().isEmpty
                          ? null
                          : _search.text.trim(),
                    ),
                  ),
                ),
              ),
              if (canParticipate)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                    child: ParticipationsSection(
                      data: nearby.participations ?? const HomeParticipations(),
                      onDiscover: () => _openDiscoverAll(),
                      onManageAll: () => context.go('/my-events'),
                      onOpenEvent: (id) => context.push('/events/$id'),
                      onOpenChat: (id) => context.push('/events/$id/chat'),
                      onOpenTicket: (id) => context.push('/events/$id/ticket'),
                    ),
                  ),
                ),
              if (nearby.categories.isNotEmpty)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                    child: HomeCategoriesSection(
                      categories: nearby.categories,
                      onViewAll: () => _openDiscoverAll(),
                      onTap: (id) => _openDiscoverAll(categoryId: id),
                    ),
                  ),
                ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                  child: LayoutBuilder(
                    builder: (context, constraints) {
                      final sideBySide = constraints.maxWidth >= 640;
                      final weather = WeatherCard(
                        weather: nearby.weather,
                        onSetLocation: () => context.push('/settings/location'),
                      );
                      final nowPlaying = nearby.nowPlaying == null
                          ? null
                          : NowPlayingCard(
                              feed: nearby.nowPlaying!,
                              brand: brand.displayName,
                              logoUrl: brand.logoUrl,
                              onOpenEvent: (e) =>
                                  context.push('/events/${e.id}'),
                              onDiscover: () => _openDiscoverAll(),
                            );

                      if (!sideBySide || nowPlaying == null) {
                        return Column(
                          children: [
                            weather,
                            if (nowPlaying != null) ...[
                              const SizedBox(height: 12),
                              nowPlaying,
                            ],
                          ],
                        );
                      }

                      return IntrinsicHeight(
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Expanded(child: weather),
                            const SizedBox(width: 12),
                            Expanded(child: nowPlaying),
                          ],
                        ),
                      );
                    },
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              l10n.nearbyTitle,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.w800,
                              ),
                            ),
                            const SizedBox(height: 2),
                            InkWell(
                              onTap: () => context.push('/settings/location'),
                              borderRadius: BorderRadius.circular(8),
                              child: Padding(
                                padding: const EdgeInsets.symmetric(
                                  vertical: 2,
                                ),
                                child: Row(
                                  children: [
                                    Flexible(
                                      child: Text(
                                        user?.cityName != null
                                            ? l10n.nearbySub(
                                                user!.cityName!,
                                                user.distanceKm ?? 25,
                                              )
                                            : l10n.nearbySubGeneric,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppColors.primary,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 4),
                                    const Icon(
                                      Icons.edit_location_alt_outlined,
                                      size: 14,
                                      color: AppColors.primary,
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      TextButton(
                        onPressed: () => _openDiscoverAll(),
                        child: Text(l10n.seeAll),
                      ),
                    ],
                  ),
                ),
              ),
              if (nearby.isLoading && nearby.items.isEmpty)
                const SliverToBoxAdapter(
                  child: Padding(
                    padding: EdgeInsets.symmetric(vertical: 40),
                    child: Center(child: FunLoader()),
                  ),
                )
              else if (nearby.error != null && nearby.items.isEmpty)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                    child: AsyncBody(
                      isLoading: false,
                      error: nearby.error,
                      onRetry: () => ref
                          .read(homeNearbyControllerProvider.notifier)
                          .load(),
                      child: const SizedBox.shrink(),
                    ),
                  ),
                )
              else if (nearby.items.isEmpty)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                    child: HomeEmptyNearbyCard(
                      onShowAll: () => _openDiscoverAll(),
                      onChangeLocation: () =>
                          context.push('/settings/location'),
                      onCreate: () => _openCreate(canParticipate),
                    ),
                  ),
                )
              else ...[
                if (nearby.discoveryExpanded)
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                      child: NationwideNotice(
                        text: Localizations.localeOf(context).languageCode ==
                                'ar'
                            ? 'لا توجد فعاليات ضمن النطاق المحدد. هذه أقرب الفعاليات المتاحة.'
                            : 'No events within your selected radius. Showing the nearest available.',
                      ),
                    ),
                  )
                else if (nearby.nationwideFallback)
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(20, 0, 20, 12),
                      child: NationwideNotice(text: l10n.nationwideNotice),
                    ),
                  ),
                SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                  sliver: SliverList.separated(
                    itemCount: nearby.items.take(6).length,
                    separatorBuilder: (_, _) => const SizedBox(height: 12),
                    itemBuilder: (context, index) {
                      final event = nearby.items[index];
                      final authed = user != null;
                      return EventCard(
                        event: event,
                        onView: () => context.push('/events/${event.id}'),
                        onFavorite: authed
                            ? () => ref
                                  .read(homeNearbyControllerProvider.notifier)
                                  .toggleFavorite(event.id)
                            : null,
                        onCancelJoin:
                            authed && event.joinAction == 'request_pending'
                            ? () => _cancelJoin(event.id)
                            : null,
                      );
                    },
                  ),
                ),
                if (nearby.worthTheTrip.isNotEmpty) ...[
                  SliverToBoxAdapter(
                    child: Padding(
                      padding: const EdgeInsets.fromLTRB(20, 16, 20, 8),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            Localizations.localeOf(context).languageCode == 'ar'
                                ? 'تستاهل الرحلة'
                                : 'Worth the trip',
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            Localizations.localeOf(context).languageCode == 'ar'
                                ? 'فعاليات أبعد قليلًا — مسافات تقريبية'
                                : 'Farther events — approximate straight-line distance',
                            style: const TextStyle(
                              fontSize: 12,
                              color: AppColors.muted,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  SliverPadding(
                    padding: const EdgeInsets.fromLTRB(20, 0, 20, 0),
                    sliver: SliverList.separated(
                      itemCount: nearby.worthTheTrip.take(4).length,
                      separatorBuilder: (_, _) => const SizedBox(height: 12),
                      itemBuilder: (context, index) {
                        final event = nearby.worthTheTrip[index];
                        return EventCard(
                          event: event,
                          onView: () => context.push('/events/${event.id}'),
                        );
                      },
                    ),
                  ),
                ],
              ],
              const SliverToBoxAdapter(child: PeopleSuggestionsSection()),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 14, 20, 0),
                  child: Column(
                    children: [
                      HomeStartingSoonCard(
                        items: nearby.startingSoon,
                        onTap: (id) => context.push('/events/$id'),
                      ),
                      const SizedBox(height: 12),
                      HomeSafetyCard(
                        onTap: () => context.push('/safety/guidelines'),
                      ),
                    ],
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
                  child: HomeHowItWorksSection(brand: brand.displayName),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 100),
                  child: HomeCreateBanner(
                    onCreate: () => _openCreate(canParticipate),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
