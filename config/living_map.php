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
    'style_url' => env(
        'LIVING_MAP_STYLE_URL',
        'https://tiles.openfreemap.org/styles/liberty'
    ),

    'attribution' => '© OpenMapTiles © OpenStreetMap contributors · OpenFreeMap',
];
