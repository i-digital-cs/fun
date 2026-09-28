import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/core/utils/safe_url.dart';
import 'package:fun_mobile/features/auth/presentation/auth_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';
import 'package:go_router/go_router.dart';

class DeleteAccountPage extends ConsumerStatefulWidget {
  const DeleteAccountPage({super.key});

  @override
  ConsumerState<DeleteAccountPage> createState() => _DeleteAccountPageState();
}

class _DeleteAccountPageState extends ConsumerState<DeleteAccountPage> {
  final _password = TextEditingController();
  String? _error;
  bool _busy = false;

  static const _graceDays = 30;

  @override
  void dispose() {
    _password.dispose();
    super.dispose();
  }

  Future<void> _delete() async {
    final auth = ref.read(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    setState(() {
      _busy = true;
      _error = null;
    });
    final err = await ref
        .read(authControllerProvider.notifier)
        .requestAccountDeletion(
          password: user?.hasPassword == true ? _password.text : null,
          providerReauth: user?.hasPassword != true,
        );
    if (!mounted) return;
    if (err != null) {
      setState(() {
        _busy = false;
        _error = err;
      });
      return;
    }
    context.go('/login');
  }

  Future<void> _cancel() async {
    setState(() {
      _busy = true;
      _error = null;
    });
    final err = await ref
        .read(authControllerProvider.notifier)
        .cancelAccountDeletion();
    if (!mounted) return;
    setState(() => _busy = false);
    if (err != null) {
      setState(() => _error = err);
      return;
    }
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(AppLocalizations.of(context).deletionCancelled)),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final auth = ref.watch(authControllerProvider);
    final user = auth is Authenticated ? auth.user : null;
    final pending = user?.hasPendingDeletion == true;

    return Scaffold(
      backgroundColor: AppColors.canvas,
      appBar: AppBar(
        title: Text(l10n.deleteAccount),
        backgroundColor: AppColors.canvas,
      ),
      body: ListView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 40),
        children: [
          Text(
            l10n.deleteAccountIntro(_graceDays),
            style: const TextStyle(
              color: AppColors.muted,
              fontSize: 14,
              height: 1.45,
            ),
          ),
          const SizedBox(height: 20),
          if (pending) ...[
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: const Color(0xFFFFFBEB),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(color: const Color(0xFFFDE68A)),
              ),
              child: Text(
                l10n.deletionPendingBanner,
                style: const TextStyle(fontWeight: FontWeight.w600),
              ),
            ),
            const SizedBox(height: 16),
            FilledButton(
              onPressed: _busy ? null : _cancel,
              child: Text(l10n.cancelDeletion),
            ),
          ] else ...[
            if (user?.hasPassword == true) ...[
              TextField(
                controller: _password,
                obscureText: true,
                decoration: InputDecoration(labelText: l10n.currentPassword),
              ),
              const SizedBox(height: 16),
            ] else
              Text(
                l10n.deletionReauthHint,
                style: const TextStyle(color: AppColors.muted, height: 1.4),
              ),
            if (_error != null) ...[
              const SizedBox(height: 8),
              Text(_error!, style: const TextStyle(color: AppColors.danger)),
            ],
            const SizedBox(height: 16),
            FilledButton(
              style: FilledButton.styleFrom(backgroundColor: AppColors.danger),
              onPressed: _busy ? null : _delete,
              child: Text(l10n.confirmDeleteAccount),
            ),
          ],
          const SizedBox(height: 24),
          TextButton(
            onPressed: () =>
                launchSafeExternalUrl('https://funnity.com/account-deletion'),
            child: Text(l10n.accountDeletionHelp),
          ),
        ],
      ),
    );
  }
}
