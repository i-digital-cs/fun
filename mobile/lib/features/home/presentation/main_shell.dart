import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/category_emoji.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/catalog/domain/catalog_repository.dart';
import 'package:fun_mobile/features/chat/presentation/chats_page.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/home/presentation/widgets/dash_topbar.dart';
import 'package:fun_mobile/features/site/data/site_remote_source.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Blade dashboard shell: bottom nav &lt;850px; left sidebar ≥850px + topbar.
class MainShell extends ConsumerWidget {
  const MainShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  static const wideBreakpoint = 850.0;
  static const sidebarWidth = 248.0;

  void _onBranchTap(BuildContext context, WidgetRef ref, int index) {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated && index != 1) {
      context.push('/login');
      return;
    }
    navigationShell.goBranch(
      index,
      initialLocation: index == navigationShell.currentIndex,
    );
  }

  void _pushOrLogin(
    BuildContext context,
    WidgetRef ref,
    String path, {
    bool guestsAllowed = false,
  }) {
    final auth = ref.read(authControllerProvider);
    if (auth is! Authenticated && !guestsAllowed) {
      context.push('/login');
      return;
    }
    context.push(path);
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final index = navigationShell.currentIndex;
    final isGuest = ref.watch(authControllerProvider) is! Authenticated;
    final width = MediaQuery.sizeOf(context).width;
    final wide = width >= wideBreakpoint;
    final path = GoRouterState.of(context).uri.path;
    final auth = ref.watch(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final unread = ref.watch(chatUnreadTotalProvider);
    final exploreCats =
        ref.watch(exploreCategoriesProvider).maybeWhen(
              data: (c) => c,
              orElse: () => const <CategoryItem>[],
            );

    final content = Column(
      children: [
        SafeArea(
          bottom: false,
          child: DashTopbar(compact: !wide),
        ),
        Expanded(
          child: wide
              ? Row(
                  children: [
                    SizedBox(
                      width: sidebarWidth,
                      child: _DashSidebar(
                        brand: ref.watch(siteBrandingProvider).maybeWhen(
                              data: (s) => s,
                              orElse: () => SiteBranding.fallback,
                            ),
                        path: path,
                        branchIndex: index,
                        user: user,
                        isGuest: isGuest,
                        chatUnread: unread,
                        exploreCategories: exploreCats,
                        onBranch: (i) => _onBranchTap(context, ref, i),
                        onPush: (p) => _pushOrLogin(context, ref, p),
                        onExploreCategory: (id) async {
                          await ref
                              .read(discoverControllerProvider.notifier)
                              .loadNationwide(categoryId: id);
                          if (context.mounted) context.go('/discover');
                        },
                        onHomeBrand: () => _onBranchTap(context, ref, 0),
                      ),
                    ),
                    Expanded(child: navigationShell),
                  ],
                )
              : navigationShell,
        ),
      ],
    );

    return Scaffold(
      body: content,
      bottomNavigationBar: wide
          ? null
          : Container(
              height: 65,
              decoration: const BoxDecoration(
                color: Colors.white,
                border: Border(top: BorderSide(color: AppColors.borderSoft)),
              ),
              child: SafeArea(
                top: false,
                child: Row(
                  children: [
                    _NavItem(
                      icon: Icons.home_outlined,
                      label: l10n.navHome,
                      selected: index == 0,
                      onTap: () => _onBranchTap(context, ref, 0),
                    ),
                    _NavItem(
                      icon: Icons.explore_outlined,
                      label: l10n.navDiscover,
                      selected: index == 1,
                      onTap: () => _onBranchTap(context, ref, 1),
                    ),
                    _NavItem(
                      icon: Icons.add_circle_outline,
                      label: l10n.navCreate,
                      selected: index == 2,
                      onTap: () => _onBranchTap(context, ref, 2),
                    ),
                    _NavItem(
                      icon: Icons.event_note_outlined,
                      label: l10n.navMyEvents,
                      selected: index == 3,
                      onTap: () => _onBranchTap(context, ref, 3),
                    ),
                    _NavItem(
                      icon: isGuest ? Icons.login : Icons.person_outline,
                      label: isGuest ? l10n.login : l10n.navProfile,
                      selected: index == 4,
                      onTap: () => _onBranchTap(context, ref, 4),
                    ),
                  ],
                ),
              ),
            ),
    );
  }
}

class _DashSidebar extends StatelessWidget {
  const _DashSidebar({
    required this.brand,
    required this.path,
    required this.branchIndex,
    required this.user,
    required this.isGuest,
    required this.chatUnread,
    required this.exploreCategories,
    required this.onBranch,
    required this.onPush,
    required this.onExploreCategory,
    required this.onHomeBrand,
  });

  final SiteBranding brand;
  final String path;
  final int branchIndex;
  final User? user;
  final bool isGuest;
  final int chatUnread;
  final List<CategoryItem> exploreCategories;
  final ValueChanged<int> onBranch;
  final ValueChanged<String> onPush;
  final ValueChanged<int> onExploreCategory;
  final VoidCallback onHomeBrand;

  bool _match(String prefix) =>
      path == prefix || path.startsWith('$prefix/');

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final cityLabel = user?.cityName ?? l10n.location;
    final needsIdentity = user?.needsIdentity == true;

    return Material(
      color: Colors.white,
      child: Container(
        decoration: const BoxDecoration(
          border: Border(right: BorderSide(color: AppColors.borderSoft)),
        ),
        child: SafeArea(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 12, 14, 8),
                child: InkWell(
                  onTap: onHomeBrand,
                  borderRadius: BorderRadius.circular(12),
                  child: Row(
                    children: [
                      if (brand.logoUrl != null && brand.logoUrl!.isNotEmpty)
                        SizedBox(
                          width: 40,
                          height: 40,
                          child: ClipRRect(
                            borderRadius: BorderRadius.circular(13),
                            child: AppNetworkImage(
                              url: brand.logoUrl!,
                              logicalWidth: 40,
                              logicalHeight: 40,
                              fit: BoxFit.contain,
                            ),
                          ),
                        )
                      else
                        Container(
                          width: 40,
                          height: 40,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(13),
                            boxShadow: [
                              BoxShadow(
                                color: AppColors.primary.withValues(alpha: 0.2),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: const Icon(
                            Icons.celebration_outlined,
                            color: AppColors.primary,
                          ),
                        ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          brand.displayName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 20,
                            fontWeight: FontWeight.w800,
                            color: AppColors.text,
                            letterSpacing: -0.3,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Expanded(
                child: ListView(
                  padding: const EdgeInsets.symmetric(horizontal: 10),
                  children: [
                    _SideItem(
                      icon: Icons.home_outlined,
                      label: l10n.navHome,
                      selected: branchIndex == 0 && !_match('/chats'),
                      onTap: () => onBranch(0),
                    ),
                    _SideItem(
                      icon: Icons.explore_outlined,
                      label: l10n.navDiscover,
                      selected: branchIndex == 1,
                      onTap: () => onBranch(1),
                    ),
                    _SideItem(
                      icon: Icons.event_note_outlined,
                      label: l10n.navMyEvents,
                      selected: branchIndex == 3,
                      onTap: () => onBranch(3),
                    ),
                    _SideItem(
                      icon: Icons.chat_bubble_outline,
                      label: l10n.chatsTitle,
                      selected: _match('/chats'),
                      badge: chatUnread > 0,
                      onTap: () => onPush('/chats'),
                    ),
                    _SideItem(
                      icon: Icons.bookmark_outline,
                      label: l10n.favorites,
                      selected: _match('/favorites'),
                      onTap: () => onPush('/favorites'),
                    ),
                    _SideItem(
                      icon: Icons.add_circle_outline,
                      label: l10n.navCreate,
                      selected: branchIndex == 2,
                      onTap: () => onBranch(2),
                    ),
                    _SideItem(
                      icon: Icons.verified_user_outlined,
                      label: l10n.identity,
                      selected: _match('/identity'),
                      badge: needsIdentity,
                      onTap: () => onPush('/identity'),
                    ),
                    _SideItem(
                      icon: Icons.block_outlined,
                      label: l10n.blocks,
                      selected: _match('/blocks'),
                      onTap: () => onPush('/blocks'),
                    ),
                    _SideItem(
                      icon: isGuest ? Icons.login : Icons.person_outline,
                      label: isGuest ? l10n.login : l10n.navProfile,
                      selected: branchIndex == 4,
                      onTap: () => onBranch(4),
                    ),
                    if (exploreCategories.isNotEmpty) ...[
                      Padding(
                        padding: const EdgeInsets.fromLTRB(12, 16, 12, 6),
                        child: Text(
                          l10n.navExplore.toUpperCase(),
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w800,
                            letterSpacing: 1.0,
                            color: Color(0xFF94A3B8),
                          ),
                        ),
                      ),
                      for (final cat in exploreCategories)
                        _SideItem(
                          icon: null,
                          emoji: categoryEmoji(cat.name),
                          label: cat.name,
                          selected: false,
                          count: cat.publishedEventsCount > 0
                              ? cat.publishedEventsCount
                              : null,
                          onTap: () => onExploreCategory(cat.id),
                        ),
                    ],
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(14, 8, 14, 16),
                child: InkWell(
                  onTap: () => onPush('/settings/location'),
                  borderRadius: BorderRadius.circular(14),
                  child: Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 12,
                      vertical: 12,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF6F9FF),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: const Color(0xFFDFE8FB)),
                    ),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.location_on_outlined,
                          size: 18,
                          color: AppColors.primary,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            cityLabel,
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                            style: const TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w700,
                              color: AppColors.text,
                            ),
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
      ),
    );
  }
}

class _SideItem extends StatelessWidget {
  const _SideItem({
    this.icon,
    this.emoji,
    required this.label,
    required this.selected,
    required this.onTap,
    this.badge = false,
    this.count,
  });

  final IconData? icon;
  final String? emoji;
  final String label;
  final bool selected;
  final VoidCallback onTap;
  final bool badge;
  final int? count;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.primary : AppColors.muted;
    return Padding(
      padding: const EdgeInsets.only(bottom: 2),
      child: Material(
        color: selected ? const Color(0xFFEEF2FF) : Colors.transparent,
        borderRadius: BorderRadius.circular(11),
        child: InkWell(
          onTap: onTap,
          borderRadius: BorderRadius.circular(11),
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
            child: Row(
              children: [
                if (emoji case final e?)
                  SizedBox(
                    width: 17,
                    child: Text(e, style: const TextStyle(fontSize: 14)),
                  )
                else if (icon != null)
                  Icon(icon, size: 17, color: color),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    label,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.w600,
                      color: color,
                    ),
                  ),
                ),
                if (count != null)
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: 6,
                      vertical: 2,
                    ),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(999),
                    ),
                    child: Text(
                      '$count',
                      style: const TextStyle(
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  )
                else if (badge)
                  Container(
                    width: 8,
                    height: 8,
                    decoration: const BoxDecoration(
                      color: AppColors.accent,
                      shape: BoxShape.circle,
                    ),
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _NavItem extends StatelessWidget {
  const _NavItem({
    required this.icon,
    required this.label,
    required this.selected,
    required this.onTap,
  });

  final IconData icon;
  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final color = selected ? AppColors.primary : AppColors.muted;

    return Expanded(
      child: InkWell(
        onTap: onTap,
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Icon(icon, size: 22, color: color),
            const SizedBox(height: 2),
            Text(
              label,
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                fontSize: 9,
                fontWeight: selected ? FontWeight.w800 : FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
