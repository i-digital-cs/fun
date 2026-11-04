<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registration kill switch + shared budgets
    |--------------------------------------------------------------------------
    |
    | Shared across Web POST /register and API POST /api/register.
    | Counters use the application cache store (atomic increment).
    | Set REGISTRATION_ENABLED=false for an emergency hard stop (reversible).
    |
    | IMPORTANT: Do not disable API registration for Google Play testing.
    | Keep TURNSTILE_ENABLED_API=false until a Flutter build ships a challenge.
    |
    */

    'enabled' => filter_var(env('REGISTRATION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    /** Global successful registration slots (all IPs / channels). */
    'global_per_hour' => (int) env('REGISTRATION_GLOBAL_PER_HOUR', 40),

    'global_per_day' => (int) env('REGISTRATION_GLOBAL_PER_DAY', 200),

    /**
     * Per-IP hour/day budgets (in addition to throttle:auth-register).
     * Kept high enough for mobile carrier NAT + Play testers.
     */
    'ip_per_hour' => (int) env('REGISTRATION_IP_PER_HOUR', 12),

    'ip_per_day' => (int) env('REGISTRATION_IP_PER_DAY', 40),

    /**
     * Slightly tighter per-IP day budget for API channel only (Flutter).
     * Still allows normal tester volume on shared carrier IPs.
     */
    'api_ip_per_day' => (int) env('REGISTRATION_API_IP_PER_DAY', 25),

    /** Per normalized-email attempt window (blocks spray of same mailbox). */
    'email_per_hour' => (int) env('REGISTRATION_EMAIL_PER_HOUR', 3),

    /**
     * When true, refuse registration (no user row, no OTP) if the abuse-prone
     * OTP outbound budget cannot accept another send.
     */
    'require_otp_budget' => filter_var(env('REGISTRATION_REQUIRE_OTP_BUDGET', true), FILTER_VALIDATE_BOOLEAN),

    /** Reject Content-Length above this (bytes). Flutter JSON bodies are tiny. */
    'max_content_length' => (int) env('REGISTRATION_MAX_CONTENT_LENGTH', 16384),

    /**
     * Optional honeypot field name. Legitimate Flutter omits it; bots often fill it.
     * Not required — missing field always passes.
     */
    'honeypot_enabled' => filter_var(env('REGISTRATION_HONEYPOT_ENABLED', true), FILTER_VALIDATE_BOOLEAN),

    'honeypot_field' => env('REGISTRATION_HONEYPOT_FIELD', 'website'),

    /** Reject numeric-only display names (historical bot pattern). */
    'reject_digit_names' => filter_var(env('REGISTRATION_REJECT_DIGIT_NAMES', true), FILTER_VALIDATE_BOOLEAN),

    /**
     * Optional disposable-domain deny list (default OFF — avoid blocking testers).
     *
     * @var list<string>
     */
    'reject_disposable_domains' => filter_var(env('REGISTRATION_REJECT_DISPOSABLE', false), FILTER_VALIDATE_BOOLEAN),

    'disposable_domains' => [
        // Intentionally empty by default; populate via config override if needed.
    ],

];
