import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Mirrors Blade `x-dashboard.mobile-nav`. Guests may only use Discover.
class MainShell extends ConsumerWidget {
  const MainShell({super.key, required this.navigationShell});

  final StatefulNavigationShell navigationShell;

  void _onTap(BuildContext context, WidgetRef ref, int index) {
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

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final index = navigationShell.currentIndex;
    final isGuest = ref.watch(authControllerProvider) is! Authenticated;

    return Scaffold(
      body: navigationShell,
      bottomNavigationBar: Container(
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
                onTap: () => _onTap(context, ref, 0),
              ),
              _NavItem(
                icon: Icons.explore_outlined,
                label: l10n.navDiscover,
                selected: index == 1,
                onTap: () => _onTap(context, ref, 1),
              ),
              _NavItem(
                icon: Icons.add_circle_outline,
                label: l10n.navCreate,
                selected: index == 2,
                onTap: () => _onTap(context, ref, 2),
              ),
              _NavItem(
                icon: Icons.event_note_outlined,
                label: l10n.navMyEvents,
                selected: index == 3,
                onTap: () => _onTap(context, ref, 3),
              ),
              _NavItem(
                icon: isGuest ? Icons.login : Icons.person_outline,
                label: isGuest ? l10n.login : l10n.navProfile,
                selected: index == 4,
                onTap: () => _onTap(context, ref, 4),
              ),
            ],
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
