<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public profile indexing
    |--------------------------------------------------------------------------
    |
    | True keeps /u/{publicId} index,follow, which is the current behavior.
    | Set SEO_PUBLIC_PROFILES_INDEXABLE=false only after an explicit owner
    | decision. That emits noindex,follow and omits Person JSON-LD. Profiles
    | stay reachable and are never added to the sitemap.
    |
    */

    'public_profiles_indexable' => env('SEO_PUBLIC_PROFILES_INDEXABLE', true),

];
