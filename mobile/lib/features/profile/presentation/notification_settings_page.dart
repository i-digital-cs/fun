import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/providers/core_providers.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

class NotificationSettingsPage extends ConsumerStatefulWidget {
  const NotificationSettingsPage({super.key});

  @override
  ConsumerState<NotificationSettingsPage> createState() =>
      _NotificationSettingsPageState();
}

class _NotificationSettingsPageState
    extends ConsumerState<NotificationSettingsPage> {
  bool? _notifyFriendEvents;
  bool _saving = false;

  Future<void> _save(bool value) async {
    setState(() {
      _notifyFriendEvents = value;
      _saving = true;
    });
    final result = await ref
        .read(profileRepositoryProvider)
        .updateNotificationSettings(notifyFriendEvents: value);
    if (!mounted) return;
    setState(() => _saving = false);
    if (result.failure != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(result.failure!.message)),
      );
      return;
    }
    await ref.read(authControllerProvider.notifier).refreshMe();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    if (_notifyFriendEvents == null && auth is Authenticated) {
      _notifyFriendEvents = auth.user.notifyFriendEvents;
    }
    final value = _notifyFriendEvents;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(title: Text(l10n.notifyFriendEventsTitle)),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          Text(
            l10n.notifyFriendEventsHelp,
            style: const TextStyle(color: AppColors.muted),
          ),
          const SizedBox(height: 16),
          SwitchListTile(
            contentPadding: EdgeInsets.zero,
            title: Text(
              l10n.notifyFriendEventsTitle,
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            subtitle: Text(l10n.notifyFriendEventsHelp),
            value: value ?? true,
            onChanged: _saving || value == null ? null : _save,
          ),
        ],
      ),
    );
  }
}
