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

];
