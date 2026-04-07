<?php

return [
    '0assets' => [
        'name'          => 'Eve Intelligence Center',
        'label'         => 'Eve Intelligence Center',
        'icon'          => 'fas fa-boxes',
        'route_segment' => 'seat-assets',
        'entries'       => [
            [
                'name'  => 'Dashboard',
                'label' => 'Dashboard',
                'icon'  => 'fas fa-tachometer-alt',
                'route' => 'seat-assets::dashboard',
            ],
            [
                'name'  => 'Assets',
                'label' => 'Assets',
                'icon'  => 'fas fa-boxes',
                'route' => 'seat-assets::assets',
            ],
            [
                'name'  => 'Stockpile Workflow',
                'label' => 'Stockpile Workflow',
                'icon'  => 'fas fa-sync-alt',
                'route' => 'seat-assets::stockpiles.workflow',
            ],
            [
                'name'  => 'Stockpiles',
                'label' => 'Stockpiles',
                'icon'  => 'fas fa-layer-group',
                'route' => 'seat-assets::stockpiles',
            ],
            [
                'name'  => 'Industry Calculator',
                'label' => 'Industry Calculator',
                'icon'  => 'fas fa-calculator',
                'route' => 'seat-assets::industry.calculator',
            ],
            [
                'name'  => 'Reactions Planner',
                'label' => 'Reactions Planner',
                'icon'  => 'fas fa-vial',
                'route' => 'seat-assets::reactions.planner',
            ],
            [
                'name'  => 'Market: Markup Report',
                'label' => 'Markup Report',
                'icon'  => 'fas fa-percentage',
                'route' => 'seat-assets::market.markup',
            ],
            [
                'name'  => 'Market: Stock Health',
                'label' => 'Stock Health',
                'icon'  => 'fas fa-box-open',
                'route' => 'seat-assets::market.stock',
            ],
            [
                'name'  => 'Market: Doctrine Dashboard',
                'label' => 'Doctrine Dashboard',
                'icon'  => 'fas fa-shield-alt',
                'route' => 'seat-assets::market.doctrine',
            ],
            [
                'name'  => 'Market: Doctrine Importing',
                'label' => 'Doctrine Importing',
                'icon'  => 'fas fa-file-import',
                'route' => 'seat-assets::market.fittings',
            ],
            [
                'name'  => 'Stockpiles Guide',
                'label' => 'Stockpiles Guide',
                'icon'  => 'fas fa-book',
                'route' => 'seat-assets::industry.guide',
            ],
        ],
    ],
];
