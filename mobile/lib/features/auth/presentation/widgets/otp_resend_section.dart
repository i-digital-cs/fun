import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/app/theme/app_colors.dart';
import 'package:fun_mobile/features/auth/presentation/otp_resend_controller.dart';
import 'package:fun_mobile/l10n/app_localizations.dart';

String formatOtpCountdown(int totalSeconds) {
  final s = totalSeconds < 0 ? 0 : totalSeconds;
  final mm = (s ~/ 60).toString().padLeft(2, '0');
  final ss = (s % 60).toString().padLeft(2, '0');
  return '$mm:$ss';
}

/// Always-visible resend affordance with server-synced cooldown label.
class OtpResendSection extends ConsumerStatefulWidget {
  const OtpResendSection({
    super.key,
    required this.tickerKey,
    required this.onResend,
    this.busy = false,
    this.enabled = true,
  });

  /// `email` or `reset` — selects the surviving Riverpod cooldown store.
  final String tickerKey;
  final Future<void> Function() onResend;
  final bool busy;
  final bool enabled;

  @override
  ConsumerState<OtpResendSection> createState() => _OtpResendSectionState();
}

class _OtpResendSectionState extends ConsumerState<OtpResendSection> {
  Timer? _timer;
  int _remaining = 0;

  StateNotifierProvider<OtpResendController, OtpResendState> get _store =>
      widget.tickerKey == 'reset'
          ? passwordResetResendProvider
          : emailVerifyResendProvider;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted) return;
      setState(_syncFromStore);
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  void _syncFromStore() {
    final remaining = ref.read(_store).remainingSeconds();
    _remaining = remaining;
    _timer?.cancel();
    _timer = null;
    if (remaining > 0) {
      _timer = Timer.periodic(const Duration(seconds: 1), (_) {
        final next = ref.read(_store).remainingSeconds();
        if (!mounted) return;
        setState(() => _remaining = next);
        if (next <= 0) {
          _timer?.cancel();
          _timer = null;
        }
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    ref.listen(_store, (_, _) {
      setState(_syncFromStore);
    });

    final cooling = _remaining > 0;
    final canTap = widget.enabled && !widget.busy && !cooling;

    return Column(
      children: [
        Text(
          l10n.resendCodePrompt,
          textAlign: TextAlign.center,
          style: const TextStyle(color: AppColors.muted, fontSize: 13),
        ),
        TextButton(
          onPressed: canTap ? () => widget.onResend() : null,
          child: widget.busy
              ? Text(l10n.loading)
              : Text(
                  cooling
                      ? l10n.resendCodeIn(formatOtpCountdown(_remaining))
                      : l10n.resendCode,
                ),
        ),
      ],
    );
  }
}
