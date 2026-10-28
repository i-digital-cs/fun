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

/// Homepage “People You May Like to Meet” (V2.1).
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
      padding: const EdgeInsets.fromLTRB(20, 28, 20, 0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w800,
                        color: AppColors.text,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      sub,
                      style: const TextStyle(
                        fontSize: 12,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: () => context.push('/friends'),
                child: Text(l10n.friendsTitle),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (state.isLoading && state.items.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 24),
              child: Center(child: FunLoader()),
            )
          else if (state.error != null && state.items.isEmpty)
            Container(
              padding: const EdgeInsets.all(16),
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
              padding: const EdgeInsets.all(16),
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
            SizedBox(
              height: 188,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: state.items.length,
                separatorBuilder: (_, _) => const SizedBox(width: 12),
                itemBuilder: (context, index) {
                  final user = state.items[index];
                  return _PeopleCard(
                    user: user,
                    onProfile: () => context.push('/u/${user.publicId}'),
                    onAdd: user.friendship.canRequest
                        ? () async {
                            final err = await ref
                                .read(
                                  peopleSuggestionsControllerProvider.notifier,
                                )
                                .requestFriend(user);
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
                  );
                },
              ),
            ),
        ],
      ),
    );
  }
}

class _PeopleCard extends StatelessWidget {
  const _PeopleCard({
    required this.user,
    required this.onProfile,
    this.onAdd,
  });

  final FriendUser user;
  final VoidCallback onProfile;
  final VoidCallback? onAdd;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final isAr = Localizations.localeOf(context).languageCode == 'ar';
    final pendingOut =
        user.friendship.status == 'pending' &&
        user.friendship.direction == 'outgoing';

    return Container(
      width: 168,
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE8EAF2)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF19213D).withValues(alpha: 0.06),
            blurRadius: 24,
            offset: const Offset(0, 10),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          InkWell(
            onTap: onProfile,
            borderRadius: BorderRadius.circular(12),
            child: Row(
              children: [
                _Avatar(user: user),
                const SizedBox(width: 10),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        user.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 13,
                          color: Color(0xFF19213D),
                        ),
                      ),
                      if (user.cityName != null && user.cityName!.isNotEmpty)
                        Text(
                          user.cityName!,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 11,
                            color: Color(0xFF64748B),
                          ),
                        ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const Spacer(),
          // Shared interests intentionally omitted until a real interests schema exists.
          Text(
            isAr ? 'لا اهتمامات مشتركة محفوظة بعد' : 'No shared interests yet',
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
          ),
          const SizedBox(height: 10),
          Row(
            children: [
              Expanded(
                child: OutlinedButton(
                  onPressed: onProfile,
                  style: OutlinedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    side: const BorderSide(color: Color(0xFFE8EAF2)),
                    foregroundColor: const Color(0xFF19213D),
                    textStyle: const TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  child: Text(isAr ? 'الملف' : 'Profile'),
                ),
              ),
              if (onAdd != null || pendingOut) ...[
                const SizedBox(width: 6),
                Expanded(
                  child: pendingOut
                      ? Text(
                          isAr ? 'تم إرسال الطلب' : 'Request sent',
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            fontSize: 10,
                            fontWeight: FontWeight.w600,
                            color: AppColors.muted,
                          ),
                        )
                      : FilledButton(
                          onPressed: onAdd,
                          style: FilledButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 8),
                            backgroundColor: const Color(0xFFFF762E),
                            foregroundColor: Colors.white,
                            textStyle: const TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w800,
                            ),
                          ),
                          child: Text(l10n.friendsAdd),
                        ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}

class _Avatar extends StatelessWidget {
  const _Avatar({required this.user});

  final FriendUser user;

  @override
  Widget build(BuildContext context) {
    final photo = user.profileImagePath;
    if (photo != null && photo.isNotEmpty) {
      return ClipOval(
        child: AppNetworkImage(
          url: photo,
          logicalWidth: 44,
          logicalHeight: 44,
          fit: BoxFit.cover,
        ),
      );
    }
    return CircleAvatar(
      radius: 22,
      backgroundColor: const Color(0xFF7138F5).withValues(alpha: 0.12),
      foregroundColor: const Color(0xFF7138F5),
      child: Text(
        user.initials,
        style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 12),
      ),
    );
  }
}
