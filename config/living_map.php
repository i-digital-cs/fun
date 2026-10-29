<?php

/**
 * Living Map V3 — prototype configuration.
 * Production Home/Discover remain on Leaflet until owner approval.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Preview access
    |--------------------------------------------------------------------------
    | Authenticated users can open /preview/living-map-v3.
    | Optional shared secret allows guest review without admin rights:
    |   /preview/living-map-v3?preview_token=...
    */
    'preview_token' => env('LIVING_MAP_PREVIEW_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | MapLibre style (OpenFreeMap — free vector tiles + attribution)
    |--------------------------------------------------------------------------
    | Not OSM raster public tiles. Verify OpenFreeMap terms before heavy traffic.
    | Production may later self-host Protomaps / private vector tiles.
    */
    /*
    | Base OpenFreeMap Liberty JSON is fetched then remapped by
    | resources/js/living-map/funnity-style.js into the Funnity Living palette.
    */
    'style_url' => env(
        'LIVING_MAP_STYLE_URL',
        'https://tiles.openfreemap.org/styles/liberty'
    ),

    'attribution' => '© OpenMapTiles © OpenStreetMap contributors · OpenFreeMap · Funnity style',

    /*
    |--------------------------------------------------------------------------
    | Production Home replacement (owner gate)
    |--------------------------------------------------------------------------
    | When false (default): Home Mini Map stays on Leaflet.
    | Living Map V3.3 is available only via /preview/living-map-v3 (and signed links).
    | Flip only after explicit owner visual approval.
    */
    'replace_home_mini_map' => (bool) env('LIVING_MAP_REPLACE_HOME', false),

    /*
    | Default display mode for the reusable component: home|discover|public|preview
    */
    'default_mode' => env('LIVING_MAP_DEFAULT_MODE', 'preview'),
];
