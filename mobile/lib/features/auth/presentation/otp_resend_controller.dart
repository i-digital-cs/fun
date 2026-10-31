import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:fun_mobile/features/auth/domain/otp_issue_meta.dart';

/// Survives verify-screen rebuilds / navigation so cooldown is not reset locally.
class OtpResendState {
  const OtpResendState({
    this.availableAt,
    this.expiresInMinutes = 15,
  });

  final DateTime? availableAt;
  final int expiresInMinutes;

  int remainingSeconds({DateTime? now}) {
    if (availableAt == null) return 0;
    final diff = availableAt!.difference(now ?? DateTime.now()).inSeconds;
    return diff > 0 ? diff : 0;
  }

  OtpResendState copyWith({
    DateTime? availableAt,
    int? expiresInMinutes,
    bool clearAvailableAt = false,
  }) {
    return OtpResendState(
      availableAt: clearAvailableAt ? null : (availableAt ?? this.availableAt),
      expiresInMinutes: expiresInMinutes ?? this.expiresInMinutes,
    );
  }
}

class OtpResendController extends StateNotifier<OtpResendState> {
  OtpResendController() : super(const OtpResendState());

  void applyMeta(OtpIssueMeta meta) {
    final seconds = meta.remainingSeconds();
    final at = meta.resendAvailableAt ??
        (seconds > 0 ? DateTime.now().add(Duration(seconds: seconds)) : null);
    state = state.copyWith(
      availableAt: at,
      expiresInMinutes: meta.expiresInMinutes,
      clearAvailableAt: at == null,
    );
  }

  void applyRetryAfter(int? seconds) {
    if (seconds == null || seconds <= 0) {
      state = state.copyWith(clearAvailableAt: true);
      return;
    }
    state = state.copyWith(
      availableAt: DateTime.now().add(Duration(seconds: seconds)),
    );
  }

  void clear() {
    state = const OtpResendState();
  }
}

final emailVerifyResendProvider =
    StateNotifierProvider<OtpResendController, OtpResendState>((ref) {
  return OtpResendController();
});

final passwordResetResendProvider =
    StateNotifierProvider<OtpResendController, OtpResendState>((ref) {
  return OtpResendController();
});
