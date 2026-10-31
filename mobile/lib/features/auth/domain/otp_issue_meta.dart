/// Server-authoritative OTP cooldown / expiry metadata.
class OtpIssueMeta {
  const OtpIssueMeta({
    this.issued = true,
    this.retryAfterSeconds = 0,
    this.resendAvailableAt,
    this.expiresInMinutes = 15,
  });

  final bool issued;
  final int retryAfterSeconds;
  final DateTime? resendAvailableAt;
  final int expiresInMinutes;

  factory OtpIssueMeta.fromJson(Map<String, dynamic>? json) {
    if (json == null) return const OtpIssueMeta();
    final retry = json['retry_after_seconds'];
    final expires = json['expires_in_minutes'];
    DateTime? availableAt;
    final rawAt = json['resend_available_at'];
    if (rawAt is String && rawAt.isNotEmpty) {
      availableAt = DateTime.tryParse(rawAt);
    }
    return OtpIssueMeta(
      issued: json['issued'] is bool ? json['issued'] as bool : true,
      retryAfterSeconds: retry is int
          ? retry
          : int.tryParse(retry?.toString() ?? '') ?? 0,
      resendAvailableAt: availableAt,
      expiresInMinutes: expires is int
          ? expires
          : int.tryParse(expires?.toString() ?? '') ?? 15,
    );
  }

  /// Seconds left until [resendAvailableAt], else [retryAfterSeconds].
  int remainingSeconds({DateTime? now}) {
    final clock = now ?? DateTime.now();
    if (resendAvailableAt != null) {
      final diff = resendAvailableAt!.difference(clock).inSeconds;
      return diff > 0 ? diff : 0;
    }
    return retryAfterSeconds > 0 ? retryAfterSeconds : 0;
  }
}
