<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Allowed discovery distances (km)
    |--------------------------------------------------------------------------
    |
    | Use 0 as the sentinel for "All distances" (nationwide / no radius cap).
    |
    */

    'distances_km' => [5, 10, 25, 50, 100, 0],

    'default_distance_km' => 25,

    /*
    |--------------------------------------------------------------------------
    | Expansion ladder (km) when Nearby is empty
    |--------------------------------------------------------------------------
    */

    'expansion_ladder_km' => [10, 25, 50, 100, 0],

];
