<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email OTP policy (authoritative)
    |--------------------------------------------------------------------------
    |
    | Used by EmailAuthCodeService for email verification and password reset.
    | Client countdowns must follow these values — do not hardcode elsewhere.
    |
    */

    /** Seconds before another code may be issued for the same user+purpose. */
    'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),

    /** Minutes until an issued code expires. */
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 15),

    /** Failed verify attempts allowed per active code before it is invalidated. */
    'max_verify_attempts' => (int) env('OTP_MAX_VERIFY_ATTEMPTS', 5),

    /*
    |--------------------------------------------------------------------------
    | Outbound OTP volume circuit breaker (application-level)
    |--------------------------------------------------------------------------
    |
    | Soft limits to reduce relay lock risk during automated registration floods.
    | Abuse-prone (register / login auto-send / forgot-password) and member
    | (authenticated resend / email change) lanes are separate so one attacker
    | cannot indefinitely starve legitimate resends. Windows roll hour/day.
    |
    */

    /** Abuse-prone lane (legacy env keys preserved). */
    'outbound_per_hour' => (int) env('OTP_OUTBOUND_PER_HOUR', 120),

    'outbound_per_day' => (int) env('OTP_OUTBOUND_PER_DAY', 800),

    /** Authenticated member resend / profile email-change lane. */
    'outbound_member_per_hour' => (int) env('OTP_OUTBOUND_MEMBER_PER_HOUR', 80),

    'outbound_member_per_day' => (int) env('OTP_OUTBOUND_MEMBER_PER_DAY', 400),

];
