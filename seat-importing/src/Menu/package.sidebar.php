<?php

return [
    '00market' => [
        'name'          => 'Market Importing',
        'label'         => 'Market Importing',
        'icon'          => 'fas fa-chart-line',
        'route_segment' => 'seat-importing',
        'permission'    => 'market.import',
        'entries'       => [
            [
                'name'          => 'Dashboard',
                'label'         => 'Dashboard',
                'icon'          => 'fas fa-chart-bar',
                'route'         => 'seat-importing.dashboard',
                'route_segment' => 'seat-importing',
                'permission'    => 'market.import',
            ],
            [
                'name'          => 'Settings',
                'label'         => 'Settings',
                'icon'          => 'fas fa-cogs',
                'route'         => 'seat-importing.settings',
                'route_segment' => 'seat-importing.settings',
                'permission'    => 'market.settings',
            ],
        ],
    ],
];
