<?php

return [

    /*
    |--------------------------------------------------------------------------
    | EVE Online Region & Location IDs
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        // The Forge (Jita) region ID — used as default market data source
        'market_region_id' => (int) env('SEAT_ASSETS_MARKET_REGION', 10000002),

        // IDs above this threshold are treated as structure IDs, below as region IDs
        'structure_id_threshold' => 100000000,
    ],

    /*
    |--------------------------------------------------------------------------
    | External API Endpoints
    |--------------------------------------------------------------------------
    */
    'apis' => [
        'fuzzwork_market'    => env('SEAT_ASSETS_FUZZWORK_MARKET_URL', 'https://market.fuzzwork.co.uk/aggregates/'),
        'fuzzwork_blueprint' => env('SEAT_ASSETS_FUZZWORK_BP_URL', 'https://www.fuzzwork.co.uk/blueprint/api/blueprint.php'),
        'eve_industry'       => env('SEAT_ASSETS_EVE_INDUSTRY_URL', 'https://api.eve-industry.org'),
        'esi_base'           => env('SEAT_ASSETS_ESI_URL', 'https://esi.evetech.net/latest'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache TTLs (in seconds)
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'prefix'              => 'seat-assets',
        'market_prices'       => (int) env('SEAT_ASSETS_CACHE_MARKET', 900),      // 15 minutes
        'adjusted_prices'     => (int) env('SEAT_ASSETS_CACHE_ADJUSTED', 3600),   // 1 hour
        'cost_index'          => (int) env('SEAT_ASSETS_CACHE_COSTINDEX', 3600),  // 1 hour
        'reaction_formula'    => (int) env('SEAT_ASSETS_CACHE_FORMULA', 604800),  // 7 days
        'reaction_type_ids'   => (int) env('SEAT_ASSETS_CACHE_TYPEIDS', 86400),   // 1 day
        'volume_sync'         => (int) env('SEAT_ASSETS_CACHE_VOLUME', 86400),    // 1 day
    ],

    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting (microseconds between requests)
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'fuzzwork_delay_us' => 500000,  // 0.5 seconds
        'esi_delay_us'      => 100000,  // 0.1 seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Reaction Product Lists
    |--------------------------------------------------------------------------
    | These map to EVE SDE invTypes.typeName values. Override to add/remove
    | reactions tracked by the calculator.
    |--------------------------------------------------------------------------
    */
    'reactions' => [
        'simple' => [
            'Caesarium Cadmide', 'Carbon Polymers', 'Ceramic Powder',
            'Crystallite Alloy', 'Dysporite', 'Fernite Alloy', 'Ferrofluid',
            'Fluxed Condensates', 'Hexite', 'Hyperflurite', 'Neo Mercurite',
            'Platinum Technite', 'Rolled Tungsten Alloy', 'Silicon Diborite',
            'Solerium', 'Sulfuric Acid', 'Titanium Chromide', 'Vanadium Hafnite',
            'Prometium', 'Thulium Hafnite', 'Promethium Mercurite',
            'Carbon Fiber', 'Thermosetting Polymer', 'Oxy-Organic Solvents',
        ],
        'complex' => [
            'Titanium Carbide', 'Crystalline Carbonide', 'Fernite Carbide',
            'Tungsten Carbide', 'Sylramic Fibers', 'Fullerides',
            'Phenolic Composites', 'Nanotransistors', 'Hypersynaptic Fibers',
            'Ferrogel', 'Fermionic Condensates', 'Plasmonic Metamaterials',
            'Terahertz Metamaterials', 'Photonic Metamaterials',
            'Nonlinear Metamaterials', 'Pressurized Oxidizers',
            'Reinforced Carbon Fiber',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Industry Modifiers (EVE game mechanics)
    |--------------------------------------------------------------------------
    | Rig and structure bonuses. These change with EVE patches, so keeping
    | them configurable avoids code changes on game balance updates.
    |--------------------------------------------------------------------------
    */
    'industry' => [
        'rig_bonuses' => [
            // Manufacturing rigs (ME modifier per security band)
            't1_me' => ['high' => 0.98, 'low' => 0.976, 'null' => 0.952],
            't2_me' => ['high' => 0.976, 'low' => 0.9524, 'null' => 0.904],
            // Reaction rigs (material + time bonuses)
            't1_medium' => [
                'material' => ['highsec' => 0.020, 'lowsec' => 0.024],
                'time'     => 0.20,
            ],
            't2_medium' => [
                'material' => ['highsec' => 0.024, 'lowsec' => 0.0312],
                'time'     => 0.24,
            ],
            't1_large' => [
                'material' => ['highsec' => 0.024, 'lowsec' => 0.0288],
                'time'     => 0.24,
            ],
            't2_large' => [
                'material' => ['highsec' => 0.030, 'lowsec' => 0.036],
                'time'     => 0.288,
            ],
        ],
        'structure_bonuses' => [
            'Tatara' => ['material' => 0.01, 'time' => 0.25],
        ],
        'engineering_complex_te_rig_bonus' => 0.80,
        'scc_surcharge' => 0.04,
    ],
];
