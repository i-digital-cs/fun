<?php

/**
 * Funnity AI Marketing Manager — Phase 1 configuration.
 *
 * Do not store live advertising API tokens here. Credentials belong in
 * encrypted database storage (Phase 2+) after explicit owner connection.
 */
return [

    'enabled' => (bool) env('MARKETING_ENABLED', true),

    /**
     * When true, no provider write mutations may be attempted by application code.
     * Phase 1 has no mutation routes; keep true until Phase 7 is approved.
     */
    'read_only' => (bool) env('MARKETING_READ_ONLY', true),

    /**
     * Hard kill-switch for executing approved mutations against ad platforms.
     */
    'execution_enabled' => (bool) env('MARKETING_EXECUTION_ENABLED', false),

    /**
     * Optional LLM explanations (Phase 5+). Off by default — rules engine only.
     */
    'ai_explanations_enabled' => (bool) env('MARKETING_AI_ENABLED', false),

    'ai_provider' => env('MARKETING_AI_PROVIDER', 'none'),

    'phase' => '1',

    'default_currency' => env('MARKETING_DEFAULT_CURRENCY', 'USD'),

    'approval_ttl_minutes' => (int) env('MARKETING_APPROVAL_TTL_MINUTES', 60),

    'sync' => [
        'insights_interval_minutes' => (int) env('MARKETING_SYNC_INSIGHTS_MINUTES', 60),
        'accounts_interval_minutes' => (int) env('MARKETING_SYNC_ACCOUNTS_MINUTES', 30),
        'stale_after_minutes' => (int) env('MARKETING_SYNC_STALE_MINUTES', 180),
    ],

    'budget_policy_defaults' => [
        'max_daily_spend' => null,
        'max_monthly_spend' => null,
        'max_budget_increase_pct' => 20,
        'max_budget_decrease_pct' => 50,
        'allow_status_pause' => true,
        'allow_status_resume' => true,
        'allow_create' => false,
        'currency' => env('MARKETING_DEFAULT_CURRENCY', 'USD'),
    ],

    /**
     * Provider codes registered in the application. Extensible (e.g. google_ads later).
     * HTTP adapters are Phase 2+; Phase 1 registers capability stubs only.
     */
    'providers' => [
        'meta' => [
            'driver' => 'meta',
            'enabled' => true,
            'display_name' => 'Meta Ads',
        ],
        'tiktok' => [
            'driver' => 'tiktok',
            'enabled' => true,
            'display_name' => 'TikTok Ads',
        ],
    ],

];
