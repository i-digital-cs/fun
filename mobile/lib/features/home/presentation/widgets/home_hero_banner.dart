import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/espiral_glow.dart';
import 'package:fun_mobile/core/widgets/fun_searching.dart';
import 'package:fun_mobile/features/home/domain/search_suggest_item.dart';
import 'package:fun_mobile/features/site/presentation/widgets/site_brand_lockup.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class HomeHeroBanner extends ConsumerStatefulWidget {
  const HomeHeroBanner({
    super.key,
    required this.searchController,
    required this.onFind,
  });

  final TextEditingController searchController;
  final VoidCallback onFind;

  @override
  ConsumerState<HomeHeroBanner> createState() => _HomeHeroBannerState();
}

class _HomeHeroBannerState extends ConsumerState<HomeHeroBanner> {
  Timer? _debounce;
  List<SearchSuggestItem> _suggestions = const [];
  bool _suggesting = false;
  String _lastQ = '';

  @override
  void initState() {
    super.initState();
    widget.searchController.addListener(_onQueryChanged);
  }

  @override
  void dispose() {
    _debounce?.cancel();
    widget.searchController.removeListener(_onQueryChanged);
    super.dispose();
  }

  void _onQueryChanged() {
    final q = widget.searchController.text.trim();
    _debounce?.cancel();
    if (q.length < 2) {
      setState(() {
        _suggestions = const [];
        _suggesting = false;
        _lastQ = q;
      });
      return;
    }
    setState(() => _suggesting = true);
    _debounce = Timer(const Duration(milliseconds: 280), () async {
      final result = await ref.read(homeRepositoryProvider).suggest(q);
      if (!mounted || widget.searchController.text.trim() != q) return;
      setState(() {
        _lastQ = q;
        _suggesting = false;
        _suggestions = result.data;
      });
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final searchController = widget.searchController;
    final onFind = widget.onFind;

    return ClipRRect(
      borderRadius: BorderRadius.circular(23),
      child: Container(
        constraints: const BoxConstraints(minHeight: 280),
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF08132C), Color(0xFF1E1B4B), Color(0xFF312E81)],
            begin: Alignment.centerLeft,
            end: Alignment.centerRight,
          ),
        ),
        child: Stack(
          children: [
            const EspiralAnchorEnd(
              tone: EspiralTone.amber,
              size: EspiralSize.lg,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  SiteBrandLockup(
                    markSize: 40,
                    maxNameWidth: 200,
                    dense: true,
                    onDark: true,
                  ),
                  const SizedBox(height: 14),
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
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        Row(
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
                                  suffixIcon: _suggesting
                                      ? const Padding(
                                          padding: EdgeInsets.all(8),
                                          child: FunSearching(
                                            size: FunSearchingSize.sm,
                                            showLabel: false,
                                            compact: true,
                                          ),
                                        )
                                      : null,
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
                        if (_suggestions.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          ..._suggestions
                              .take(5)
                              .map(
                                (s) => ListTile(
                                  dense: true,
                                  contentPadding: const EdgeInsets.symmetric(
                                    horizontal: 8,
                                  ),
                                  title: Text(
                                    s.title,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 13,
                                    ),
                                  ),
                                  subtitle: s.subtitle == null
                                      ? null
                                      : Text(
                                          s.subtitle!,
                                          style: const TextStyle(fontSize: 11),
                                        ),
                                  trailing: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: [
                                      if (s.badge != null &&
                                          s.badge!.isNotEmpty)
                                        Container(
                                          margin:
                                              const EdgeInsetsDirectional.only(
                                            end: 8,
                                          ),
                                          padding: const EdgeInsets.symmetric(
                                            horizontal: 8,
                                            vertical: 3,
                                          ),
                                          decoration: BoxDecoration(
                                            color: switch (s.joinAction) {
                                              'owned' =>
                                                const Color(0xFFEEF2FF),
                                              'joined' =>
                                                const Color(0xFFECFDF5),
                                              'request_pending' =>
                                                const Color(0xFFFFFBEB),
                                              _ => AppColors.primarySoft,
                                            },
                                            borderRadius: BorderRadius.circular(
                                              999,
                                            ),
                                          ),
                                          child: Text(
                                            s.badge!,
                                            style: TextStyle(
                                              fontSize: 10,
                                              fontWeight: FontWeight.w800,
                                              color: switch (s.joinAction) {
                                                'owned' =>
                                                  const Color(0xFF4338CA),
                                                'joined' =>
                                                  const Color(0xFF047857),
                                                'request_pending' =>
                                                  const Color(0xFFB45309),
                                                _ => AppColors.primary,
                                              },
                                            ),
                                          ),
                                        ),
                                      if (s.meta != null)
                                        Text(
                                          s.meta!,
                                          style: const TextStyle(
                                            fontSize: 11,
                                            color: AppColors.muted,
                                          ),
                                        ),
                                    ],
                                  ),
                                  onTap: () => context.push('/events/${s.id}'),
                                ),
                              ),
                          TextButton(
                            onPressed: onFind,
                            child: Text(
                              '${l10n.seeAllResults}${_lastQ.isEmpty ? '' : ' · $_lastQ'}',
                            ),
                          ),
                        ],
                      ],
                    ),
                  ),
                  const SizedBox(height: 14),
                  Wrap(
                    spacing: 16,
                    runSpacing: 8,
                    children: [
                      HomeTrustChip(
                        icon: Icons.verified_user_outlined,
                        label: l10n.trustIdentity,
                      ),
                      HomeTrustChip(
                        icon: Icons.people_outline,
                        label: l10n.trustPeople,
                      ),
                      HomeTrustChip(
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

class HomeTrustChip extends StatelessWidget {
  const HomeTrustChip({super.key, required this.icon, required this.label});

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
          style: TextStyle(color: Colors.blue.shade100, fontSize: 11),
        ),
      ],
    );
  }
}
