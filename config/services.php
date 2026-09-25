<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI', env('APP_URL').'/auth/google/callback'),
        // All OAuth client IDs that may appear as `aud` on Flutter/web ID tokens.
        'client_ids' => array_values(array_filter([
            env('GOOGLE_CLIENT_ID'),
            env('GOOGLE_ANDROID_CLIENT_ID'),
            env('GOOGLE_IOS_CLIENT_ID'),
        ])),
    ],

    'weather' => [
        // Open-Meteo — free, no API key required: https://open-meteo.com/
        'driver' => env('WEATHER_DRIVER', 'open_meteo'),
        'base_url' => env('WEATHER_BASE_URL', 'https://api.open-meteo.com/v1/forecast'),
        'cache_minutes' => (int) env('WEATHER_CACHE_MINUTES', 30),
        'forecast_days' => (int) env('WEATHER_FORECAST_DAYS', 5),
    ],

    /*
    | Firebase Cloud Messaging (HTTP v1)
    | Place the service-account JSON on the server (outside public/) and point
    | FIREBASE_CREDENTIALS at its absolute path. Without these, in-app DB
    | notifications still work; push is skipped and logged.
    */
    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
    ],

];
