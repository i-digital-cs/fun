import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/discover/presentation/discover_page.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/events/presentation/widgets/event_card.dart';
import 'package:fun_mobile/features/home/presentation/home_nearby_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

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
    Future.microtask(
      () => ref.read(homeNearbyControllerProvider.notifier).load(),
    );
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
    await ref.read(discoverControllerProvider.notifier).loadNationwide(
          q: q,
          categoryId: categoryId,
        );
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

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final nearby = ref.watch(homeNearbyControllerProvider);
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
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
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
                                fontSize: 25,
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
                  padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
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
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                  child: _HeroBanner(
                    searchController: _search,
                    onFind: () => _openDiscoverAll(
                      q: _search.text.trim().isEmpty
                          ? null
                          : _search.text.trim(),
                    ),
                  ),
                ),
              ),
              if (nearby.categories.isNotEmpty)
                SliverToBoxAdapter(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(20, 28, 20, 0),
                    child: _CategoriesSection(
                      categories: nearby.categories,
                      onViewAll: () => _openDiscoverAll(),
                      onTap: (id) => _openDiscoverAll(categoryId: id),
                    ),
                  ),
                ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 12),
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
                                padding:
                                    const EdgeInsets.symmetric(vertical: 2),
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
                    child: Center(
                      child: FunLoader(),
                    ),
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
                    child: _EmptyNearbyCard(
                      onShowAll: () => _openDiscoverAll(),
                      onChangeLocation: () =>
                          context.push('/settings/location'),
                      onCreate: () => _openCreate(canParticipate),
                    ),
                  ),
                )
              else ...[
                if (nearby.nationwideFallback)
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
                    separatorBuilder: (_, _) => const SizedBox(height: 16),
                    itemBuilder: (context, index) {
                      final event = nearby.items[index];
                      return EventCard(
                        event: event,
                        onView: () => context.push('/events/${event.id}'),
                      );
                    },
                  ),
                ),
              ],
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 20, 20, 0),
                  child: Column(
                    children: [
                      _StartingSoonCard(
                        items: nearby.startingSoon,
                        onTap: (id) => context.push('/events/$id'),
                      ),
                      const SizedBox(height: 12),
                      _SafetyCard(
                        onTap: () => context.push('/safety/guidelines'),
                      ),
                    ],
                  ),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 0),
                  child: _HowItWorksSection(brand: l10n.appName),
                ),
              ),
              SliverToBoxAdapter(
                child: Padding(
                  padding: const EdgeInsets.fromLTRB(20, 28, 20, 100),
                  child: _CreateBanner(
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

class _CategoriesSection extends StatelessWidget {
  const _CategoriesSection({
    required this.categories,
    required this.onViewAll,
    required this.onTap,
  });

  final List<CategoryItem> categories;
  final VoidCallback onViewAll;
  final ValueChanged<int> onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.intoTitle,
                    style: const TextStyle(
                      fontSize: 19,
                      fontWeight: FontWeight.w800,
                      letterSpacing: -0.2,
                    ),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    l10n.intoSub,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
                ],
              ),
            ),
            TextButton(
              onPressed: onViewAll,
              style: TextButton.styleFrom(
                padding: EdgeInsets.zero,
                minimumSize: Size.zero,
                tapTargetSize: MaterialTapTargetSize.shrinkWrap,
              ),
              child: Text(
                l10n.viewAllCategories,
                style: const TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 14),
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: categories.length,
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 4,
            mainAxisSpacing: 10,
            crossAxisSpacing: 10,
            childAspectRatio: 0.78,
          ),
          itemBuilder: (context, index) {
            final cat = categories[index];
            return Material(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(15),
              child: InkWell(
                onTap: () => onTap(cat.id),
                borderRadius: BorderRadius.circular(15),
                child: Container(
                  decoration: BoxDecoration(
                    borderRadius: BorderRadius.circular(15),
                    border: Border.all(color: AppColors.borderSoft),
                  ),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 4,
                    vertical: 12,
                  ),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        width: 44,
                        height: 44,
                        alignment: Alignment.center,
                        decoration: const BoxDecoration(
                          color: AppColors.primarySoft,
                          shape: BoxShape.circle,
                        ),
                        child: Text(
                          categoryEmoji(cat.name),
                          style: const TextStyle(fontSize: 20),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        cat.name,
                        textAlign: TextAlign.center,
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.w700,
                          height: 1.15,
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        ),
      ],
    );
  }
}

class _EmptyNearbyCard extends StatelessWidget {
  const _EmptyNearbyCard({
    required this.onShowAll,
    required this.onChangeLocation,
    required this.onCreate,
  });

  final VoidCallback onShowAll;
  final VoidCallback onChangeLocation;
  final VoidCallback onCreate;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(
          color: AppColors.border.withValues(alpha: 0.7),
        ),
      ),
      child: Column(
        children: [
          Text(
            l10n.emptyNearby,
            textAlign: TextAlign.center,
            style: const TextStyle(color: AppColors.muted),
          ),
          const SizedBox(height: 16),
          GradientButton(
            label: l10n.createEvent,
            icon: Icons.add,
            onPressed: onCreate,
          ),
          const SizedBox(height: 8),
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
    );
  }
}

class _StartingSoonCard extends StatelessWidget {
  const _StartingSoonCard({
    required this.items,
    required this.onTap,
  });

  final List<EventItem> items;
  final ValueChanged<int> onTap;

  String _formatWhen(AppLocalizations l10n, DateTime? starts) {
    if (starts == null) return '';
    final local = starts.toLocal();
    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final day = DateTime(local.year, local.month, local.day);
    final time = DateFormat.jm().format(local);
    if (day == today) return '${l10n.today} · $time';
    if (day == today.add(const Duration(days: 1))) {
      return '${l10n.tomorrow} · $time';
    }
    return DateFormat('E · MMM d · ').format(local) + time;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(17),
        border: Border.all(color: AppColors.borderSoft),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            l10n.startingSoon,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          if (items.isEmpty)
            Text(
              l10n.emptyNearby,
              style: const TextStyle(
                fontSize: 11,
                color: AppColors.muted,
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: items.length,
              separatorBuilder: (_, _) => const Divider(
                height: 1,
                color: Color(0xFFF1F5F9),
              ),
              itemBuilder: (context, index) {
                final event = items[index];
                return InkWell(
                  onTap: () => onTap(event.id),
                  child: Padding(
                    padding: const EdgeInsets.symmetric(vertical: 10),
                  child: Row(
                    children: [
                      Container(
                        width: 36,
                        height: 36,
                        alignment: Alignment.center,
                        decoration: BoxDecoration(
                          color: AppColors.primarySoft,
                          borderRadius: BorderRadius.circular(10),
                        ),
                        child: Text(
                          categoryEmoji(event.category?.name),
                          style: const TextStyle(fontSize: 16),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              event.title,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                              style: const TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              _formatWhen(l10n, event.startsAt),
                              style: const TextStyle(
                                fontSize: 10,
                                color: AppColors.muted,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              );
              },
            ),
        ],
      ),
    );
  }
}

class _SafetyCard extends StatelessWidget {
  const _SafetyCard({required this.onTap});

  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(17),
        gradient: const LinearGradient(
          colors: [Color(0xFF4338CA), Color(0xFF4F46E5)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            width: 44,
            height: 44,
            alignment: Alignment.center,
            decoration: BoxDecoration(
              color: Colors.white.withValues(alpha: 0.15),
              borderRadius: BorderRadius.circular(13),
            ),
            child: const Icon(
              Icons.verified_user_outlined,
              color: Colors.white,
              size: 22,
            ),
          ),
          const SizedBox(height: 12),
          Text(
            l10n.safetyTitle,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 15,
              fontWeight: FontWeight.w800,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            l10n.safetyBody,
            style: TextStyle(
              color: Colors.blue.shade100,
              fontSize: 11,
              height: 1.45,
            ),
          ),
          const SizedBox(height: 12),
          Material(
            color: Colors.white,
            borderRadius: BorderRadius.circular(9),
            child: InkWell(
              onTap: onTap,
              borderRadius: BorderRadius.circular(9),
              child: Padding(
                padding: const EdgeInsets.symmetric(
                  horizontal: 12,
                  vertical: 8,
                ),
                child: Text(
                  l10n.safetyCta,
                  style: const TextStyle(
                    color: Color(0xFF4338CA),
                    fontSize: 11,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _HowItWorksSection extends StatelessWidget {
  const _HowItWorksSection({required this.brand});

  final String brand;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final steps = [
      (l10n.how1Title, l10n.how1Body),
      (l10n.how2Title, l10n.how2Body),
      (l10n.how3Title, l10n.how3Body),
    ];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          l10n.howTitle(brand),
          style: const TextStyle(
            fontSize: 19,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.2,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          l10n.howSub,
          style: const TextStyle(fontSize: 11, color: AppColors.muted),
        ),
        const SizedBox(height: 14),
        ...List.generate(steps.length, (i) {
          final step = steps[i];
          return Padding(
            padding: EdgeInsets.only(bottom: i == steps.length - 1 ? 0 : 10),
            child: Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderSoft),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 36,
                    height: 36,
                    alignment: Alignment.center,
                    decoration: BoxDecoration(
                      color: AppColors.primarySoft,
                      borderRadius: BorderRadius.circular(11),
                    ),
                    child: Text(
                      '0${i + 1}',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          step.$1,
                          style: const TextStyle(
                            fontSize: 13,
                            fontWeight: FontWeight.w800,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          step.$2,
                          style: const TextStyle(
                            fontSize: 11,
                            color: AppColors.muted,
                            height: 1.4,
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          );
        }),
      ],
    );
  }
}

class _CreateBanner extends StatelessWidget {
  const _CreateBanner({required this.onCreate});

  final VoidCallback onCreate;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(20, 22, 20, 22),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFDCE7FF)),
        gradient: const LinearGradient(
          colors: [Color(0xFFEFF5FF), Color(0xFFF5F3FF)],
          begin: Alignment.centerLeft,
          end: Alignment.centerRight,
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            l10n.ctaTitle,
            style: const TextStyle(
              fontSize: 22,
              fontWeight: FontWeight.w800,
              letterSpacing: -0.3,
            ),
          ),
          const SizedBox(height: 6),
          Text(
            l10n.ctaBody,
            style: const TextStyle(
              fontSize: 12,
              color: AppColors.muted,
            ),
          ),
          const SizedBox(height: 16),
          GradientButton(
            label: l10n.ctaButton,
            icon: Icons.event_available_outlined,
            onPressed: onCreate,
          ),
        ],
      ),
    );
  }
}

class _HeroBanner extends StatelessWidget {
  const _HeroBanner({
    required this.searchController,
    required this.onFind,
  });

  final TextEditingController searchController;
  final VoidCallback onFind;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return ClipRRect(
      borderRadius: BorderRadius.circular(23),
      child: Container(
        constraints: const BoxConstraints(minHeight: 280),
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [
              Color(0xFF08132C),
              Color(0xFF1E1B4B),
              Color(0xFF312E81),
            ],
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
          ),
        ),
        child: Stack(
          children: [
            Positioned(
              right: -20,
              top: -20,
              child: Container(
                width: 160,
                height: 160,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: AppColors.primary.withValues(alpha: 0.25),
                ),
              ),
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 10,
                      vertical: 6,
                    ),
                    decoration: BoxDecoration(
                      color: Colors.white.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(
                        color: Colors.white.withValues(alpha: 0.2),
                      ),
                    ),
                    child: Text(
                      l10n.heroEyebrow,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),
                  Text(
                    '${l10n.heroTitle1}\n${l10n.heroTitle2}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 32,
                      fontWeight: FontWeight.w800,
                      height: 1.05,
                      letterSpacing: -0.8,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    l10n.heroBody,
                    style: TextStyle(
                      color: Colors.blue.shade100,
                      fontSize: 13,
                      height: 1.45,
                    ),
                  ),
                  const SizedBox(height: 18),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(15),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withValues(alpha: 0.2),
                          blurRadius: 24,
                          offset: const Offset(0, 10),
                        ),
                      ],
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: searchController,
                            textInputAction: TextInputAction.search,
                            onSubmitted: (_) => onFind(),
                            decoration: InputDecoration(
                              hintText: l10n.heroActivityPh,
                              filled: true,
                              fillColor: const Color(0xFFF7F9FC),
                              border: OutlineInputBorder(
                                borderRadius: BorderRadius.circular(10),
                                borderSide: BorderSide.none,
                              ),
                              contentPadding: const EdgeInsets.symmetric(
                                horizontal: 12,
                                vertical: 12,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Material(
                          color: AppColors.primary,
                          borderRadius: BorderRadius.circular(10),
                          child: InkWell(
                            onTap: onFind,
                            borderRadius: BorderRadius.circular(10),
                            child: Padding(
                              padding: const EdgeInsets.symmetric(
                                horizontal: 14,
                                vertical: 14,
                              ),
                              child: Row(
                                children: [
                                  const Icon(
                                    Icons.search,
                                    size: 16,
                                    color: Colors.white,
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    l10n.findEvents,
                                    style: const TextStyle(
                                      color: Colors.white,
                                      fontSize: 12,
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  Wrap(
                    spacing: 16,
                    runSpacing: 8,
                    children: [
                      _TrustChip(
                        icon: Icons.verified_user_outlined,
                        label: l10n.trustIdentity,
                      ),
                      _TrustChip(
                        icon: Icons.people_outline,
                        label: l10n.trustPeople,
                      ),
                      _TrustChip(
                        icon: Icons.flag_outlined,
                        label: l10n.trustReport,
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _TrustChip extends StatelessWidget {
  const _TrustChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Icon(icon, size: 14, color: const Color(0xFF6EE7B7)),
        const SizedBox(width: 6),
        Text(
          label,
          style: TextStyle(
            color: Colors.blue.shade100,
            fontSize: 11,
          ),
        ),
      ],
    );
  }
}

/// Mirrors Blade sidebar location card (`data-location-modal-open`).
class LocationShortcutCard extends StatelessWidget {
  const LocationShortcutCard({
    super.key,
    required this.cityName,
    required this.distanceKm,
    required this.onTap,
  });

  final String? cityName;
  final int distanceKm;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Material(
      color: const Color(0xFFF6F9FF),
      borderRadius: BorderRadius.circular(14),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(14),
        child: Container(
          width: double.infinity,
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(14),
            border: Border.all(color: const Color(0xFFDFE8FB)),
          ),
          child: Row(
            children: [
              const Icon(
                Icons.location_on_outlined,
                color: AppColors.primary,
                size: 20,
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      cityName ?? l10n.setYourLocation,
                      style: const TextStyle(
                        fontSize: 13,
                        fontWeight: FontWeight.w700,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      cityName != null
                          ? l10n.showingWithin(distanceKm)
                          : l10n.tapToChangeLocation,
                      style: const TextStyle(
                        fontSize: 11,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              const Icon(
                Icons.chevron_right,
                color: AppColors.muted,
                size: 20,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
