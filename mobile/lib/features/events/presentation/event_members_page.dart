import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/core/widgets/async_body.dart';
import 'package:fun_mobile/core/widgets/fun_confirm_dialog.dart';
import 'package:fun_mobile/features/auth/domain/auth_repository.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/features/events/domain/event_item.dart';
import 'package:fun_mobile/features/safety/presentation/report_sheet.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

/// Mirrors Blade `events/members` — list + promote/demote/transfer + report/block.
class EventMembersPage extends ConsumerStatefulWidget {
  const EventMembersPage({super.key, required this.eventId});

  final int eventId;

  @override
  ConsumerState<EventMembersPage> createState() => _EventMembersPageState();
}

class _EventMembersPageState extends ConsumerState<EventMembersPage> {
  List<EventMember> _members = const [];
  String? _error;
  bool _loading = true;
  bool _acting = false;

  int? get _currentUserId {
    final auth = ref.read(authControllerProvider);
    return auth is Authenticated ? auth.user.id : null;
  }

  bool get _canManage {
    final uid = _currentUserId;
    if (uid == null) return false;
    return _members.any((m) => m.userId == uid && m.role == 'owner');
  }

  bool get _canParticipate {
    final auth = ref.read(authControllerProvider);
    return auth is Authenticated && auth.user.canParticipate;
  }

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    final result =
        await ref.read(eventsRepositoryProvider).members(widget.eventId);
    if (!mounted) return;
    setState(() {
      _loading = false;
      if (result.failure != null) {
        _error = result.failure!.message;
      } else {
        _members = result.data ?? [];
      }
    });
  }

  Future<void> _runAction(Future<Result<void>> Function() action) async {
    setState(() => _acting = true);
    final result = await action();
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await _load();
  }

  Future<void> _promote(EventMember member) async {
    await _runAction(
      () => ref
          .read(eventsRepositoryProvider)
          .promoteMember(widget.eventId, member.userId),
    );
  }

  Future<void> _demote(EventMember member) async {
    await _runAction(
      () => ref
          .read(eventsRepositoryProvider)
          .demoteMember(widget.eventId, member.userId),
    );
  }

  Future<void> _transfer(EventMember member) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmTransfer(member.name),
    );
    if (confirm != true || !mounted) return;
    await _runAction(
      () => ref
          .read(eventsRepositoryProvider)
          .transferOwnership(widget.eventId, member.userId),
    );
  }

  Future<void> _block(EventMember member) async {
    final l10n = AppLocalizations.of(context);
    final confirm = await showFunConfirm(
      context,
      message: l10n.confirmBlock(member.name),
    );
    if (confirm != true || !mounted) return;
    setState(() => _acting = true);
    final result =
        await ref.read(safetyRemoteSourceProvider).block(member.userId);
    if (!mounted) return;
    setState(() => _acting = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(l10n.userBlocked)),
    );
    await _load();
  }

  String _roleLabel(AppLocalizations l10n, String role) {
    return switch (role) {
      'owner' => l10n.roleOwner,
      'admin' => l10n.roleAdmin,
      _ => l10n.roleMember,
    };
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final uid = _currentUserId;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.viewMembers)),
      body: AsyncBody(
        isLoading: _loading,
        error: _error,
        onRetry: _load,
        isEmpty: _members.isEmpty,
        empty: Center(
          child: Text(
            l10n.emptyMembers,
            style: const TextStyle(color: AppColors.muted),
          ),
        ),
        child: ListView.separated(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 40),
          itemCount: _members.length,
          separatorBuilder: (_, _) => const SizedBox(height: 10),
          itemBuilder: (context, index) {
            final member = _members[index];
            final isSelf = member.userId == uid;
            return Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.borderSoft),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    backgroundColor: AppColors.primarySoft,
                    child: Text(
                      member.name.isNotEmpty
                          ? member.name[0].toUpperCase()
                          : '?',
                      style: const TextStyle(
                        color: AppColors.primary,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          member.name,
                          style: const TextStyle(
                            fontWeight: FontWeight.w700,
                            fontSize: 14,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: AppColors.primarySoft,
                            borderRadius: BorderRadius.circular(999),
                          ),
                          child: Text(
                            _roleLabel(l10n, member.role),
                            style: const TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w700,
                              color: AppColors.primaryInk,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  if (!isSelf && (_canManage || _canParticipate))
                    PopupMenuButton<String>(
                      enabled: !_acting,
                      onSelected: (value) {
                        switch (value) {
                          case 'promote':
                            _promote(member);
                          case 'demote':
                            _demote(member);
                          case 'transfer':
                            _transfer(member);
                          case 'report':
                            showReportSheet(
                              context,
                              reportableType: 'user',
                              reportableId: member.userId,
                            );
                          case 'block':
                            _block(member);
                        }
                      },
                      itemBuilder: (context) => [
                        if (_canManage && member.role == 'member')
                          PopupMenuItem(
                            value: 'promote',
                            child: Text(l10n.promote),
                          ),
                        if (_canManage && member.role == 'admin')
                          PopupMenuItem(
                            value: 'demote',
                            child: Text(l10n.demote),
                          ),
                        if (_canManage && member.role != 'owner')
                          PopupMenuItem(
                            value: 'transfer',
                            child: Text(l10n.transferOwnership),
                          ),
                        if (_canParticipate)
                          PopupMenuItem(
                            value: 'report',
                            child: Text(l10n.report),
                          ),
                        if (_canParticipate)
                          PopupMenuItem(
                            value: 'block',
                            child: Text(l10n.blockUser),
                          ),
                      ],
                    ),
                ],
              ),
            );
          },
        ),
      ),
    );
  }
}
