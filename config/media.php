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

];
