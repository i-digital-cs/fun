import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/fun_searching.dart';
import 'package:fun_mobile/features/auth/domain/user.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/chat/presentation/chats_page.dart';
import 'package:fun_mobile/features/discover/presentation/discover_controller.dart';
import 'package:fun_mobile/features/home/domain/search_suggest_item.dart';
import 'package:fun_mobile/features/site/presentation/widgets/site_brand_lockup.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

/// Blade `x-dashboard.topbar` — live search, locale, chat unread, avatar menu.
class DashTopbar extends ConsumerStatefulWidget {
  const DashTopbar({super.key, this.compact = false});

  /// Narrower actions (phone): hide name + locale chip; avatar → profile.
  final bool compact;

  @override
  ConsumerState<DashTopbar> createState() => _DashTopbarState();
}

class _DashTopbarState extends ConsumerState<DashTopbar> {
  final _search = TextEditingController();
  final _focus = FocusNode();
  final _link = LayerLink();
  final _portal = OverlayPortalController();
  Timer? _debounce;
  List<SearchSuggestItem> _suggestions = const [];
  bool _suggesting = false;
  String _lastQ = '';

  @override
  void initState() {
    super.initState();
    _search.addListener(_onQueryChanged);
    _focus.addListener(() {
      if (!_focus.hasFocus) {
        Future<void>.delayed(const Duration(milliseconds: 120), () {
          if (mounted && !_focus.hasFocus) _portal.hide();
        });
      } else if (_suggestions.isNotEmpty || _suggesting) {
        _portal.show();
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.removeListener(_onQueryChanged);
    _search.dispose();
    _focus.dispose();
    super.dispose();
  }

  void _onQueryChanged() {
    final q = _search.text.trim();
    _debounce?.cancel();
    if (q.length < 2) {
      setState(() {
        _suggestions = const [];
        _suggesting = false;
        _lastQ = q;
      });
      _portal.hide();
      return;
    }
    setState(() => _suggesting = true);
    _portal.show();
    _debounce = Timer(const Duration(milliseconds: 280), () async {
      final result = await ref.read(homeRepositoryProvider).suggest(q);
      if (!mounted || _search.text.trim() != q) return;
      setState(() {
        _lastQ = q;
        _suggesting = false;
        _suggestions = result.data;
      });
      if (_focus.hasFocus) _portal.show();
    });
  }

  Future<void> _seeAll() async {
    final q = _search.text.trim();
    _portal.hide();
    _focus.unfocus();
    await ref
        .read(discoverControllerProvider.notifier)
        .loadNationwide(q: q.isEmpty ? null : q);
    if (!mounted) return;
    context.go('/discover');
  }

  Future<void> _toggleLocale() async {
    final current = ref.read(localeCodeProvider);
    final next = current == 'en' ? 'ar' : 'en';
    ref.read(localeCodeProvider.notifier).state = next;
    await ref.read(sharedPreferencesProvider).setString('locale', next);
    ref.read(apiClientProvider).setLocale(next);
  }

  Future<void> _logout() async {
    await ref.read(authControllerProvider.notifier).logout();
    if (mounted) context.go('/login');
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final locale = ref.watch(localeCodeProvider);
    final unread = ref.watch(chatUnreadTotalProvider);
    final oppositeGlyph = locale == 'en' ? 'ع' : 'EN';
    final oppositeLabel = locale == 'en' ? 'AR' : 'EN';

    return Material(
      color: Colors.white.withValues(alpha: 0.92),
      child: Container(
        height: 70,
        decoration: const BoxDecoration(
          border: Border(bottom: BorderSide(color: AppColors.borderSoft)),
        ),
        padding: EdgeInsets.symmetric(horizontal: widget.compact ? 12 : 20),
        child: Row(
          children: [
            SiteBrandLockup(
              markSize: widget.compact ? 34 : 36,
              maxNameWidth: widget.compact ? 96 : 160,
              dense: widget.compact,
              onTap: () => context.go('/home'),
            ),
            SizedBox(width: widget.compact ? 8 : 12),
            Expanded(
              child: OverlayPortal(
                controller: _portal,
                overlayChildBuilder: (context) {
                  return UnconstrainedBox(
                    alignment: AlignmentDirectional.topStart,
                    child: CompositedTransformFollower(
                      link: _link,
                      showWhenUnlinked: false,
                      offset: const Offset(0, 46),
                      child: Material(
                        elevation: 8,
                        borderRadius: BorderRadius.circular(14),
                        color: Colors.white,
                        child: ConstrainedBox(
                          constraints: const BoxConstraints(
                            minWidth: 280,
                            maxWidth: 540,
                            maxHeight: 320,
                          ),
                          child: _SuggestPanel(
                            suggesting: _suggesting,
                            suggestions: _suggestions,
                            lastQ: _lastQ,
                            onTapItem: (id) {
                              _portal.hide();
                              _focus.unfocus();
                              context.push('/events/$id');
                            },
                            onSeeAll: _seeAll,
                          ),
                        ),
                      ),
                    ),
                  );
                },
                child: CompositedTransformTarget(
                  link: _link,
                  child: Container(
                    height: 42,
                    decoration: BoxDecoration(
                      color: const Color(0xFFF7F9FC),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: AppColors.borderSoft),
                    ),
                    padding: const EdgeInsets.symmetric(horizontal: 12),
                    child: Row(
                      children: [
                        const Icon(
                          Icons.search,
                          size: 18,
                          color: AppColors.muted,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: TextField(
                            controller: _search,
                            focusNode: _focus,
                            textInputAction: TextInputAction.search,
                            onSubmitted: (_) => _seeAll(),
                            decoration: InputDecoration(
                              isDense: true,
                              hintText: l10n.searchPlaceholder,
                              hintStyle: const TextStyle(
                                fontSize: 13,
                                color: AppColors.muted,
                              ),
                              border: InputBorder.none,
                              contentPadding: const EdgeInsets.symmetric(
                                vertical: 10,
                              ),
                            ),
                            style: const TextStyle(
                              fontSize: 13,
                              color: AppColors.text,
                            ),
                          ),
                        ),
                        if (_suggesting)
                          const Padding(
                            padding: EdgeInsetsDirectional.only(start: 4),
                            child: FunSearching(
                              size: FunSearchingSize.sm,
                              showLabel: false,
                              compact: true,
                            ),
                          ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
            const SizedBox(width: 10),
            if (!widget.compact) ...[
              Semantics(
                button: true,
                label: oppositeLabel,
                child: InkWell(
                  onTap: _toggleLocale,
                  borderRadius: BorderRadius.circular(999),
                  child: Container(
                    height: 32,
                    padding: const EdgeInsets.symmetric(horizontal: 10),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(999),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Row(
                      children: [
                        Text(
                          oppositeGlyph,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w900,
                            color: AppColors.primary,
                          ),
                        ),
                        const SizedBox(width: 4),
                        Text(
                          oppositeLabel,
                          style: const TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF475569),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(width: 8),
            ],
            IconButton(
              tooltip: l10n.chatsTitle,
              onPressed: () {
                if (user == null) {
                  context.push('/login');
                  return;
                }
                context.push('/chats');
              },
              icon: Badge(
                isLabelVisible: unread > 0,
                smallSize: 8,
                backgroundColor: AppColors.primary,
                child: const Icon(
                  Icons.chat_bubble_outline,
                  size: 20,
                  color: Color(0xFF64748B),
                ),
              ),
            ),
            if (user == null)
              TextButton(
                onPressed: () => context.push('/login'),
                child: Text(l10n.login),
              )
            else if (widget.compact)
              IconButton(
                tooltip: l10n.navProfile,
                onPressed: () => context.go('/profile'),
                icon: _Avatar(user: user),
              )
            else
              PopupMenuButton<String>(
                offset: const Offset(0, 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                ),
                onSelected: (value) async {
                  switch (value) {
                    case 'home':
                      context.go('/home');
                    case 'profile':
                      context.push('/settings/profile');
                    case 'location':
                      context.push('/settings/location');
                    case 'identity':
                      context.push('/identity');
                    case 'blocks':
                      context.push('/blocks');
                    case 'logout':
                      await _logout();
                  }
                },
                itemBuilder: (context) => [
                  PopupMenuItem(value: 'home', child: Text(l10n.navHome)),
                  PopupMenuItem(value: 'profile', child: Text(l10n.navProfile)),
                  PopupMenuItem(value: 'location', child: Text(l10n.location)),
                  PopupMenuItem(
                    value: 'identity',
                    child: Row(
                      children: [
                        Text(l10n.identity),
                        if (user.needsIdentity) ...[
                          const SizedBox(width: 8),
                          Container(
                            width: 6,
                            height: 6,
                            decoration: const BoxDecoration(
                              color: AppColors.accent,
                              shape: BoxShape.circle,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  PopupMenuItem(value: 'blocks', child: Text(l10n.blocks)),
                  const PopupMenuDivider(),
                  PopupMenuItem(value: 'logout', child: Text(l10n.logout)),
                ],
                child: Row(
                  children: [
                    _Avatar(user: user),
                    const SizedBox(width: 8),
                    Text(
                      user.firstName,
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w700,
                        color: AppColors.text,
                      ),
                    ),
                    const Icon(
                      Icons.expand_more,
                      size: 16,
                      color: AppColors.muted,
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

class _Avatar extends StatelessWidget {
  const _Avatar({required this.user});

  final User user;

  @override
  Widget build(BuildContext context) {
    final photo = user.profileImagePath;
    if (photo != null && photo.isNotEmpty) {
      return ClipOval(
        child: SizedBox(
          width: 36,
          height: 36,
          child: AppNetworkImage(
            url: photo,
            logicalWidth: 36,
            logicalHeight: 36,
            fit: BoxFit.cover,
          ),
        ),
      );
    }
    return CircleAvatar(
      radius: 18,
      backgroundColor: const Color(0xFFE0E7FF),
      child: Text(
        user.initials,
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w800,
          color: Color(0xFF4338CA),
        ),
      ),
    );
  }
}

class _SuggestPanel extends StatelessWidget {
  const _SuggestPanel({
    required this.suggesting,
    required this.suggestions,
    required this.lastQ,
    required this.onTapItem,
    required this.onSeeAll,
  });

  final bool suggesting;
  final List<SearchSuggestItem> suggestions;
  final String lastQ;
  final ValueChanged<int> onTapItem;
  final VoidCallback onSeeAll;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    if (suggesting && suggestions.isEmpty) {
      return const Padding(
        padding: EdgeInsets.symmetric(vertical: 20, horizontal: 16),
        child: FunSearching(size: FunSearchingSize.sm),
      );
    }

    if (suggestions.isEmpty) {
      return const SizedBox.shrink();
    }

    return ListView(
      shrinkWrap: true,
      padding: const EdgeInsets.symmetric(vertical: 6),
      children: [
        for (final s in suggestions.take(6))
          ListTile(
            dense: true,
            title: Text(
              s.title,
              style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13),
            ),
            subtitle: s.subtitle == null
                ? null
                : Text(s.subtitle!, style: const TextStyle(fontSize: 11)),
            trailing: s.meta == null
                ? null
                : Text(
                    s.meta!,
                    style: const TextStyle(
                      fontSize: 11,
                      color: AppColors.muted,
                    ),
                  ),
            onTap: () => onTapItem(s.id),
          ),
        TextButton(
          onPressed: onSeeAll,
          child: Text(
            '${l10n.seeAllResults}${lastQ.isEmpty ? '' : ' · $lastQ'}',
          ),
        ),
      ],
    );
  }
}
