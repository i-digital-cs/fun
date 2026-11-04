<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Media Disk
    |--------------------------------------------------------------------------
    |
    | Used for event images and other non-sensitive media.
    | Local default: "media". Production: set MEDIA_DISK=r2.
    |
    */

    'disk' => env('MEDIA_DISK', 'media'),

    /*
    |--------------------------------------------------------------------------
    | Private Media Disk
    |--------------------------------------------------------------------------
    |
    | Used for identity documents and other sensitive uploads.
    | Must never be publicly accessible.
    | Local default: "media_private". Production: set MEDIA_PRIVATE_DISK=r2_private.
    |
    */

    'private_disk' => env('MEDIA_PRIVATE_DISK', 'media_private'),

    /*
    |--------------------------------------------------------------------------
    | Allowed Disk Names
    |--------------------------------------------------------------------------
    */

    'public_disks' => ['media', 'r2'],

    'private_disks' => ['media_private', 'r2_private'],

    /*
    |--------------------------------------------------------------------------
    | Object key prefixes (logical layout on R2 / local disks)
    |--------------------------------------------------------------------------
    */

    'prefixes' => [
        'profiles' => 'profiles',
        'event_covers' => 'events',
        'chat' => 'chat',
        'identity' => 'identity',
        'voice' => 'chat',
    ],

    /*
    |--------------------------------------------------------------------------
    | Category policies (server-side validation)
    |--------------------------------------------------------------------------
    */

    'categories' => [
        'profile' => [
            'visibility' => 'public',
            'max_bytes' => 5_242_880,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'strip_exif' => true,
        ],
        'event_cover' => [
            'visibility' => 'public',
            'max_bytes' => 5_242_880,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'strip_exif' => true,
        ],
        'chat_image' => [
            'visibility' => 'private',
            'max_bytes' => 8_388_608,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
            'strip_exif' => true,
        ],
        'chat_video' => [
            'visibility' => 'private',
            'max_bytes' => 52_428_800, // 50 MB
            'mimes' => ['video/mp4', 'video/webm', 'video/quicktime'],
            'extensions' => ['mp4', 'webm', 'mov'],
            'max_duration_ms' => 180_000,
        ],
        'chat_voice' => [
            'visibility' => 'private',
            'max_bytes' => 5_242_880,
            'min_bytes' => 256,
            'mimes' => [
                'audio/webm',
                'video/webm',
                'audio/ogg',
                'video/ogg',
                'audio/mpeg',
                'audio/mp4',
                'audio/m4a',
                'audio/x-m4a',
                'audio/aac',
                'audio/wav',
                'audio/wave',
                'audio/x-wav',
            ],
            'extensions' => ['webm', 'ogg', 'mp3', 'm4a', 'wav'],
            'max_duration_ms' => 120_000,
        ],
        'chat_file' => [
            'visibility' => 'private',
            'max_bytes' => 10_485_760,
            'blocked_mimes' => [
                'application/x-msdownload',
                'application/x-executable',
                'application/x-sh',
                'text/x-shellscript',
                'application/javascript',
                'text/html',
                'image/svg+xml',
            ],
            'blocked_extensions' => ['exe', 'bat', 'cmd', 'sh', 'php', 'js', 'msi', 'apk', 'svg', 'html', 'htm'],
        ],
        'identity' => [
            'visibility' => 'private',
            'max_bytes' => 10_485_760,
            'mimes' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'pdf'],
            'strip_exif' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delivery
    |--------------------------------------------------------------------------
    |
    | Private media is always served through authenticated Laravel gateways
    | (never reusable R2 object URLs in HTML/API payloads).
    |
    */

    'private_cache_control' => 'private, no-store, no-transform',

    'public_cache_control' => 'public, max-age=604800, immutable',

];
