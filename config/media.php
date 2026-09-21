<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public Media Disk
    |--------------------------------------------------------------------------
    |
    | Used for profile images, event images, and other non-sensitive media.
    | V1: local public disk. Production target: Cloudflare R2 via S3-compatible
    | driver (set MEDIA_DISK=r2 after credentials are configured).
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
    |
    */

    'private_disk' => env('MEDIA_PRIVATE_DISK', 'media_private'),

];
