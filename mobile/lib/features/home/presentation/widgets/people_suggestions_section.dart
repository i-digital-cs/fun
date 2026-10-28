import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/app_network_image.dart';
import 'package:fun_mobile/core/widgets/fun_loader.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/friends/domain/friend_user.dart';
import 'package:fun_mobile/features/friends/domain/friendship_state.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class PeopleSuggestionsState {
  const PeopleSuggestionsState({
    this.items = const [],
    this.isLoading = false,
    this.error,
  });

  final List<FriendUser> items;
  final bool isLoading;
  final String? error;

  PeopleSuggestionsState copyWith({
    List<FriendUser>? items,
    bool? isLoading,
    String? error,
    bool clearError = false,
  }) {
    return PeopleSuggestionsState(
      items: items ?? this.items,
      isLoading: isLoading ?? this.isLoading,
      error: clearError ? null : (error ?? this.error),
    );
  }
}

class PeopleSuggestionsController extends StateNotifier<PeopleSuggestionsState> {
  PeopleSuggestionsController(this._ref) : super(const PeopleSuggestionsState());

  final Ref _ref;

  Future<void> load() async {
    final auth = _ref.read(authControllerProvider);
    if (auth is! Authenticated || !auth.user.canParticipate) {
      state = const PeopleSuggestionsState();
      return;
    }
    state = state.copyWith(isLoading: true, clearError: true);
    final result = await _ref
        .read(friendsRepositoryProvider)
        .peopleSuggestions(limit: 8);
    if (result.failure != null) {
      state = state.copyWith(
        isLoading: false,
        error: result.failure!.message,
        items: const [],
      );
      return;
    }
    state = state.copyWith(
      isLoading: false,
      items: result.data,
      clearError: true,
    );
  }

  Future<String?> requestFriend(FriendUser user) async {
    if (!user.friendship.canRequest) return null;
    final result = await _ref
        .read(friendsRepositoryProvider)
        .requestFriend(user.publicId);
    if (result.failure != null) return result.failure!.message;
    state = state.copyWith(
      items: [
        for (final item in state.items)
          if (item.id == user.id)
            item.copyWith(
              friendship: FriendshipState(
                status: 'pending',
                direction: 'outgoing',
                canRequest: false,
                canMessage: false,
              ),
            )
          else
            item,
      ],
    );
    return null;
  }
}

final peopleSuggestionsControllerProvider =
    StateNotifierProvider<PeopleSuggestionsController, PeopleSuggestionsState>(
      (ref) => PeopleSuggestionsController(ref),
    );

/// Homepage “People You May Like to Meet” (V2.1) — mobile-compact cards.
class PeopleSuggestionsSection extends ConsumerWidget {
  const PeopleSuggestionsSection({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authControllerProvider);
    if (auth is! Authenticated || !auth.user.canParticipate) {
      return const SizedBox.shrink();
    }

    final l10n = AppLocalizations.of(context);
    final isAr = Localizations.localeOf(context).languageCode == 'ar';
    final state = ref.watch(peopleSuggestionsControllerProvider);

    final title = isAr ? 'ناس ممكن تحب تتعرف عليهم' : 'People You May Like to Meet';
    final sub = isAr
        ? 'أعضاء قريبون يمكنك التواصل معهم — بدون مشاركة مواقع دقيقة.'
        : 'Nearby members you can connect with — no precise locations shared.';

    return Padding(
      padding: const EdgeInsets.fromLTRB(20, 16, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.w800,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      sub,
                      style: const TextStyle(
                        fontSize: 12,
                        height: 1.35,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: () => context.push('/friends'),
                style: TextButton.styleFrom(
                  visualDensity: VisualDensity.compact,
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                ),
                child: Text(l10n.friendsTitle),
              ),
            ],
          ),
          const SizedBox(height: 10),
          if (state.isLoading && state.items.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 20),
              child: Center(child: FunLoader()),
            )
          else if (state.error != null && state.items.isEmpty)
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderSoft),
              ),
              child: Column(
                children: [
                  Text(state.error!, textAlign: TextAlign.center),
                  TextButton(
                    onPressed: () => ref
                        .read(peopleSuggestionsControllerProvider.notifier)
                        .load(),
                    child: Text(l10n.retry),
                  ),
                ],
              ),
            )
          else if (state.items.isEmpty)
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderSoft),
              ),
              child: Text(
                isAr
                    ? 'لا توجد اقتراحات الآن. جرب البحث بالمعرّف العام.'
                    : 'No suggestions right now. Try searching by public ID.',
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.muted),
              ),
            )
          else
            // Intrinsic height — never clip with a fixed SizedBox (overflow root cause).
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  for (var i = 0; i < state.items.length; i++) ...[
                    if (i > 0) const SizedBox(width: 10),
                    PeopleSuggestionCard(
                      user: state.items[i],
                      onProfile: () =>
                          context.push('/u/${state.items[i].publicId}'),
                      onAdd: state.items[i].friendship.canRequest
                          ? () async {
                              final err = await ref
                                  .read(
                                    peopleSuggestionsControllerProvider
                                        .notifier,
                                  )
                                  .requestFriend(state.items[i]);
                              if (!context.mounted) return;
                              ScaffoldMessenger.of(context).showSnackBar(
                                SnackBar(
                                  content: Text(
                                    err ?? l10n.friendsRequestSent,
                                  ),
                                ),
                              );
                            }
                          : null,
                    ),
                  ],
                ],
              ),
            ),
        ],
      ),
    );
  }
}

/// Compact suggestion card — public for widget tests.
class PeopleSuggestionCard extends StatelessWidget {
  const PeopleSuggestionCard({
    super.key,
    required this.user,
    required this.onProfile,
    this.onAdd,
    this.sharedInterests,
  });

  final FriendUser user;
  final VoidCallback onProfile;
  final VoidCallback? onAdd;

  /// Optional interest labels when API provides them; null/empty → muted hint.
  final List<String>? sharedInterests;

  static const double cardWidth = 148;
  static const double avatarSize = 56;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isAr = Localizations.localeOf(context).languageCode == 'ar';
    final pendingOut =
        user.friendship.status == 'pending' &&
        user.friendship.direction == 'outgoing';
    final interests = (sharedInterests ?? const [])
        .where((s) => s.trim().isNotEmpty)
        .toList();

    final buttonStyle = ButtonStyle(
      visualDensity: VisualDensity.compact,
      tapTargetSize: MaterialTapTargetSize.shrinkWrap,
      minimumSize: const WidgetStatePropertyAll(Size(0, 36)),
      padding: const WidgetStatePropertyAll(
        EdgeInsets.symmetric(horizontal: 8, vertical: 8),
      ),
      textStyle: const WidgetStatePropertyAll(
        TextStyle(fontSize: 11, fontWeight: FontWeight.w800, height: 1.1),
      ),
    );

    return SizedBox(
      width: cardWidth,
      child: Material(
        color: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(18),
          side: const BorderSide(color: Color(0xFFE8EAF2)),
        ),
        child: Padding(
          padding: const EdgeInsets.fromLTRB(12, 12, 12, 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              InkWell(
                onTap: onProfile,
                borderRadius: BorderRadius.circular(12),
                child: Column(
                  children: [
                    _Avatar(user: user, size: avatarSize),
                    const SizedBox(height: 8),
                    Text(
                      user.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 13,
                        height: 1.2,
                        color: Color(0xFF19213D),
                      ),
                    ),
                    if (user.cityName != null && user.cityName!.isNotEmpty) ...[
                      const SizedBox(height: 2),
                      Text(
                        user.cityName!,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          fontSize: 11,
                          color: Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
              const SizedBox(height: 6),
              Text(
                interests.isEmpty
                    ? (isAr
                          ? 'لا اهتمامات مشتركة محفوظة بعد'
                          : 'No shared interests yet')
                    : interests.take(2).join(' · '),
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 10,
                  height: 1.25,
                  color: interests.isEmpty
                      ? const Color(0xFF94A3B8)
                      : const Color(0xFF64748B),
                ),
              ),
              const SizedBox(height: 10),
              if (pendingOut)
                Container(
                  alignment: Alignment.center,
                  constraints: const BoxConstraints(minHeight: 36),
                  padding: const EdgeInsets.symmetric(horizontal: 6),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    isAr ? 'تم إرسال الطلب' : 'Request sent',
                    textAlign: TextAlign.center,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                      color: AppColors.muted,
                    ),
                  ),
                )
              else if (onAdd != null)
                FilledButton(
                  onPressed: onAdd,
                  style: buttonStyle.copyWith(
                    backgroundColor: const WidgetStatePropertyAll(
                      Color(0xFF7138F5),
                    ),
                    foregroundColor: const WidgetStatePropertyAll(Colors.white),
                  ),
                  child: Text(
                    l10n.friendsAdd,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                )
              else
                const SizedBox(height: 36),
              const SizedBox(height: 6),
              OutlinedButton(
                onPressed: onProfile,
                style: buttonStyle.copyWith(
                  side: const WidgetStatePropertyAll(
                    BorderSide(color: Color(0xFFE8EAF2)),
                  ),
                  foregroundColor: const WidgetStatePropertyAll(
                    Color(0xFF19213D),
                  ),
                ),
                child: Text(
                  isAr ? 'الملف' : 'Profile',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.user, required this.size});

  final FriendUser user;
  final double size;

  @override
  Widget build(BuildContext context) {
    final photo = user.profileImagePath;
    if (photo != null && photo.isNotEmpty) {
      return ClipOval(
        child: AppNetworkImage(
          url: photo,
          logicalWidth: size,
          logicalHeight: size,
          fit: BoxFit.cover,
        ),
      );
    }
    return CircleAvatar(
      radius: size / 2,
      backgroundColor: const Color(0xFF7138F5).withValues(alpha: 0.12),
      foregroundColor: const Color(0xFF7138F5),
      child: Text(
        user.initials,
        style: TextStyle(
          fontWeight: FontWeight.w800,
          fontSize: size * 0.28,
        ),
      ),
    );
  }
}
