<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Import Cost (ISK per m3)
    |--------------------------------------------------------------------------
    | Used to calculate freight/import cost for items. Can be overridden
    | per-hub via the settings UI or the market_settings table.
    */
    'default_isk_per_m3' => (float) env('SEAT_IMPORTING_ISK_PER_M3', 1000.0),

    /*
    |--------------------------------------------------------------------------
    | Default Jita Region & Station IDs
    |--------------------------------------------------------------------------
    */
    'jita_region_id'  => (int) env('SEAT_IMPORTING_JITA_REGION', 10000002),
    'jita_station_id' => (int) env('SEAT_IMPORTING_JITA_STATION', 60003760),

    /*
    |--------------------------------------------------------------------------
    | Markup Thresholds
    |--------------------------------------------------------------------------
    */
    'markup_threshold_pct'    => (float) env('SEAT_IMPORTING_MARKUP_THRESHOLD', 25.0),
    'stock_low_threshold_pct' => (float) env('SEAT_IMPORTING_STOCK_LOW_THRESHOLD', 50.0),

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'prefix'      => 'seat-importing',
        'metrics'     => (int) env('SEAT_IMPORTING_CACHE_METRICS', 600),   // 10 min
        'hub_list'    => (int) env('SEAT_IMPORTING_CACHE_HUBS', 3600),     // 1 hour
        'item_prices' => (int) env('SEAT_IMPORTING_CACHE_PRICES', 900),    // 15 min
    ],

    /*
    |--------------------------------------------------------------------------
    | Import Sources
    |--------------------------------------------------------------------------
    | Supported: 'fuzzwork_csv', 'tycoon_csv'
    */
    'import' => [
        'default_source' => env('SEAT_IMPORTING_SOURCE', 'fuzzwork_csv'),
        'fuzzwork_url'   => env('SEAT_IMPORTING_FUZZWORK_URL', 'https://market.fuzzwork.co.uk/aggregates/'),
        'import_path'    => env('SEAT_IMPORTING_PATH', storage_path('app/seat-importing')),
        'batch_size'     => (int) env('SEAT_IMPORTING_BATCH', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'view'   => 'seat-importing.view',
        'manage' => 'seat-importing.manage',
        'import' => 'seat-importing.import',
    ],
];
