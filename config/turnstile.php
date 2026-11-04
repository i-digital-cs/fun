<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile (anti-bot)
    |--------------------------------------------------------------------------
    |
    | Server-side Siteverify only. Never trust a client-provided "passed" flag.
    |
    | CRITICAL: keep enabled_api=false until released Flutter clients can satisfy
    | the challenge. Do not invent a permanent UA / app-version bypass.
    |
    */

    'site_key' => env('TURNSTILE_SITE_KEY'),

    'secret_key' => env('TURNSTILE_SECRET_KEY'),

    'verify_url' => env('TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),

    'timeout_seconds' => (int) env('TURNSTILE_TIMEOUT_SECONDS', 5),

    /** Web Blade registration (POST /register). */
    'enabled_web' => filter_var(env('TURNSTILE_ENABLED_WEB', false), FILTER_VALIDATE_BOOLEAN),

    /** API registration (POST /api/register). Default OFF for Flutter compatibility. */
    'enabled_api' => filter_var(env('TURNSTILE_ENABLED_API', false), FILTER_VALIDATE_BOOLEAN),

    /** Expected action claim when the widget is configured with an action. */
    'expected_action' => env('TURNSTILE_EXPECTED_ACTION', 'register'),

    /**
     * Hostnames allowed in Siteverify `hostname` (comma-separated).
     * Empty = skip hostname check (local/dev only — production should set this).
     */
    'expected_hostnames' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TURNSTILE_EXPECTED_HOSTNAMES', ''))
    ))),

    /** Reject reused tokens for this many seconds after first successful verify. */
    'token_reuse_ttl_seconds' => (int) env('TURNSTILE_TOKEN_REUSE_TTL', 300),

];
