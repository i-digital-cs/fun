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

    /*
    |--------------------------------------------------------------------------
    | City search aliases (normalized query → preferred name tokens)
    |--------------------------------------------------------------------------
    |
    | Maps common misspellings / shorthand to substrings matched against
    | cities.name_en / name_ar / slug. No third-party geocoder required.
    |
    */

    'search_aliases' => [
        '6 october' => '6th of October',
        '6th october' => '6th of October',
        'october 6' => '6th of October',
        'october' => '6th of October',
        '6th of october city' => '6th of October',
        '٦ أكتوبر' => 'السادس من أكتوبر',
        '6 اكتوبر' => 'السادس من أكتوبر',
        'اكتوبر' => 'السادس من أكتوبر',
        'أكتوبر' => 'السادس من أكتوبر',
        'sheikh zayed' => 'Sheikh Zayed',
        'zayed' => 'Sheikh Zayed',
        'الشيخ زايد' => 'الشيخ زايد',
        'new cairo' => 'New Cairo',
        'القاهرة الجديدة' => 'القاهرة الجديدة',
        'التجمع الخامس' => 'New Cairo',
        'التجمع' => 'New Cairo',
        '5th settlement' => 'New Cairo',
        'fifth settlement' => 'New Cairo',
        'nasr city' => 'Nasr City',
        'maadi' => 'Maadi',
        'heliopolis' => 'Heliopolis',
        'giza' => 'Giza',
        'الجيزة' => 'الجيزة',
        'port said' => 'Port Said',
        'بورسعيد' => 'بورسعيد',
    ],

];
