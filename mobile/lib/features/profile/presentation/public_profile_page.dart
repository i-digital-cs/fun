import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/errors/error_mapper.dart';
import 'package:fun_mobile/core/errors/failures.dart';
import 'package:fun_mobile/core/navigation/app_navigation.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/utils/bidi_text.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/core/widgets/gradient_button.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/profile/domain/public_profile.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

class PublicProfilePage extends ConsumerStatefulWidget {
  const PublicProfilePage({super.key, required this.publicId});

  final String publicId;

  @override
  ConsumerState<PublicProfilePage> createState() => _PublicProfilePageState();
}

class _PublicProfilePageState extends ConsumerState<PublicProfilePage> {
  PublicProfile? _profile;
  String? _error;
  bool _loading = true;
  bool _blocking = false;
  bool _friendAction = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  String _userError(Failure failure) {
    final l10n = AppLocalizations.of(context);
    if (failure is NotFoundFailure) return l10n.profileUnavailable;
    if (failure is NetworkFailure) return l10n.errorProfileLoad;
    return localizedFailureMessage(l10n, failure);
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result = await ref
        .read(publicProfileRepositoryProvider)
        .show(widget.publicId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result.failure != null) {
        _error = _userError(result.failure!);
        _profile = null;
      } else {
        _profile = result.data;
        if (_profile == null) {
          _error = AppLocalizations.of(context).profileUnavailable;
        }
      }
    });
  }

  Future<void> _runFriendAction(
    Future<({Failure? failure, void data})> Function() action,
  ) async {
    setState(() => _friendAction = true);
    final result = await action();
    if (!mounted) return;
    setState(() => _friendAction = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(_userError(result.failure!))),
      );
      return;
    }
    await _load();
  }

  Future<void> _openMessage(PublicProfile profile) async {
    final id = profile.id;
    if (id == null) return;
    setState(() => _friendAction = true);
    final result = await ref.read(dmsRepositoryProvider).openWith(id);
    if (!mounted) return;
    setState(() => _friendAction = false);
    if (result.failure != null || result.data == null) {
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(
            result.failure != null
                ? _userError(result.failure!)
                : l10n.errorGeneric,
          ),
        ),
      );
      return;
    }
    context.push('/dms/${result.data!.conversationId}');
  }

  Future<void> _block(PublicProfile profile) async {
    final id = profile.id;
    if (id == null) return;
    final l10n = AppLocalizations.of(context);
    final ok = await showFunConfirm(
      context,
      message: l10n.confirmBlock(profile.name),
    );
    if (ok != true || !mounted) return;
    setState(() => _blocking = true);
    final result = await ref.read(safetyRepositoryProvider).block(id);
    if (!mounted) return;
    setState(() => _blocking = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(_userError(result.failure!))));
      return;
    }
    ScaffoldMessenger.of(context)
        .showSnackBar(SnackBar(content: Text(l10n.userBlocked)));
    if (context.canPop()) {
      context.pop();
    } else {
      context.go('/discover');
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final me = auth is Authenticated ? auth.user : null;
    final profile = _profile;
    final isSelf =
        me != null &&
        profile != null &&
        ((profile.id != null && profile.id == me.id) ||
            (me.publicId != null && me.publicId == profile.publicId));
    final canAct = me != null && me.canParticipate && !isSelf;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.publicProfile),
        actions: [
          if (isSelf)
            TextButton(
              onPressed: () => context.push('/settings/profile'),
              child: Text(l10n.editProfile),
            ),
        ],
      ),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        isEmpty: !_loading && _error == null && profile == null,
        empty: Center(
          child: Padding(
            padding: const EdgeInsets.all(24),
            child: Text(
              l10n.profileUnavailable,
              textAlign: TextAlign.center,
              style: const TextStyle(color: AppColors.muted),
            ),
          ),
        ),
        child: profile == null
            ? const SizedBox.shrink()
            : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                PublicProfileCard(profile: profile),
                const SizedBox(height: 16),
                if (me == null)
                  GradientButton.indigo(
                    label: l10n.friendsAdd,
                    onPressed: () => requireAuth(
                      context,
                      auth,
                      returnTo: '/u/${widget.publicId}',
                    ),
                  )
                else if (isSelf)
                  GradientButton.indigo(
                    label: l10n.editProfile,
                    onPressed: () => context.push('/settings/profile'),
                  )
                else if (canAct && profile.id != null) ...[
                  ..._friendshipActions(context, l10n, profile),
                  const SizedBox(height: 10),
                  Row(
                    children: [
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _blocking
                              ? null
                              : () => showReportSheet(
                                  context,
                                  reportableType: 'user',
                                  reportableId: profile.id!,
                                ),
                          style: OutlinedButton.styleFrom(
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: Text(l10n.report),
                        ),
                      ),
                      const SizedBox(width: 10),
                      Expanded(
                        child: OutlinedButton(
                          onPressed: _blocking ? null : () => _block(profile),
                          style: OutlinedButton.styleFrom(
                            foregroundColor: AppColors.danger,
                            side: const BorderSide(color: AppColors.danger),
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: Text(l10n.blockUser),
                        ),
                      ),
                    ],
                  ),
                ],
                if (profile.hostedEvents.isNotEmpty) ...[
                  const SizedBox(height: 24),
                  Text(
                    l10n.hostedEvents,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 12),
                  ...profile.hostedEvents.map((event) {
                    final locale = Localizations.localeOf(context).toString();
                    final when = event.startsAt != null
                        ? DateFormat(
                            'MMM d · h:mm a',
                            locale,
                          ).format(event.startsAt!.toLocal())
                        : '';
                    return Padding(
                      padding: const EdgeInsets.only(bottom: 10),
                      child: ListTile(
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(16),
                          side: const BorderSide(color: AppColors.borderSoft),
                        ),
                        tileColor: AppColors.surface,
                        title: Text(
                          event.title,
                          textDirection: bidiTextDirection(event.title),
                          style: const TextStyle(fontWeight: FontWeight.w700),
                        ),
                        subtitle: Text(
                          [
                            if (when.isNotEmpty) when,
                            if (event.city != null) event.city,
                          ].join(' · '),
                        ),
                        onTap: () => context.push('/events/${event.id}'),
                      ),
                    );
                  }),
                ],
              ],
            ),
      ),
    );
  }

  List<Widget> _friendshipActions(
    BuildContext context,
    AppLocalizations l10n,
    PublicProfile profile,
  ) {
    final fs = profile.friendship;
    final status = fs.status;
    final busy = _friendAction;

    if (status == 'accepted') {
      return [
        if (fs.canMessage || profile.canMessage)
          GradientButton.indigo(
            label: l10n.friendsMessage,
            onPressed: busy ? null : () => _openMessage(profile),
          ),
        const SizedBox(height: 8),
        OutlinedButton(
          onPressed: busy
              ? null
              : () => _runFriendAction(
                  () => ref.read(friendsRepositoryProvider).unfriend(profile.id!),
                ),
          child: Text(l10n.friendsUnfriend),
        ),
      ];
    }
    if (status == 'pending' && fs.direction == 'outgoing') {
      return [
        OutlinedButton(
          onPressed: busy
              ? null
              : () => _runFriendAction(
                  () => ref.read(friendsRepositoryProvider).cancel(profile.id!),
                ),
          child: Text(l10n.friendsCancelRequest),
        ),
      ];
    }
    if (status == 'pending' && fs.direction == 'incoming') {
      return [
        GradientButton.indigo(
          label: l10n.friendsAccept,
          onPressed: busy
              ? null
              : () => _runFriendAction(
                  () => ref.read(friendsRepositoryProvider).accept(profile.id!),
                ),
        ),
        const SizedBox(height: 8),
        OutlinedButton(
          onPressed: busy
              ? null
              : () => _runFriendAction(
                  () => ref.read(friendsRepositoryProvider).decline(profile.id!),
                ),
          child: Text(l10n.friendsDecline),
        ),
      ];
    }
    if (fs.canRequest) {
      return [
        GradientButton.indigo(
          label: l10n.friendsAdd,
          onPressed: busy
              ? null
              : () => _runFriendAction(
                  () => ref
                      .read(friendsRepositoryProvider)
                      .requestFriend(profile.publicId),
                ),
        ),
      ];
    }
    return const [];
  }
}

class PublicProfileCard extends StatelessWidget {
  const PublicProfileCard({super.key, required this.profile});

  final PublicProfile profile;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);

    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(16),
        gradient: const LinearGradient(
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
          colors: [Color(0xFF1E1B4B), Color(0xFF312E81), Color(0xFF4338CA)],
        ),
        boxShadow: [
          BoxShadow(
            color: AppColors.primary.withValues(alpha: 0.28),
            blurRadius: 24,
            offset: const Offset(0, 12),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        mainAxisSize: MainAxisSize.min,
        children: [
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 96,
                height: 96,
                decoration: BoxDecoration(
                  borderRadius: BorderRadius.circular(8),
                  gradient: AppColors.indigoGradient,
                  image: profile.profileImagePath != null
                      ? DecorationImage(
                          image: NetworkImage(profile.profileImagePath!),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
                alignment: Alignment.center,
                child: profile.profileImagePath == null
                    ? Text(
                        profile.initials,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.w800,
                          fontSize: 28,
                        ),
                      )
                    : null,
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text(
                      profile.name,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 20,
                        fontWeight: FontWeight.w700,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      [
                        profile.roleLabel,
                        if (profile.cityName != null) profile.cityName!,
                      ].join(' · '),
                      style: const TextStyle(
                        color: Color(0xFFC7D2FE),
                        fontSize: 12,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      profile.publicId,
                      style: const TextStyle(
                        color: Color(0xFFFDBA74),
                        fontSize: 11,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              children: [
                _Stat(
                  label: l10n.statHosted,
                  value: '${profile.hostedEventsCount}',
                ),
                _Stat(
                  label: l10n.statJoined,
                  value: '${profile.joinedEventsCount}',
                ),
                _Stat(
                  label: l10n.statRating,
                  value: profile.ratingCount > 0
                      ? '${profile.ratingAverage}★'
                      : '—',
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final String value;

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: const TextStyle(fontSize: 10, color: AppColors.muted),
          ),
          const SizedBox(height: 2),
          Text(
            value,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w800,
              color: AppColors.primary,
            ),
          ),
        ],
      ),
    );
  }
}
