<?php

return [
    [
        'name'          => 'Market Importing',
        'icon'          => 'fas fa-chart-line',
        'route_segment' => 'seat-importing',
        'entries'       => [
            [
                'name'          => 'Dashboard',
                'icon'          => 'fas fa-tachometer-alt',
                'route'         => 'seat-importing.dashboard',
                'route_segment' => 'seat-importing',
                'permission'    => 'seat-importing.view',
            ],
            [
                'name'          => 'Settings',
                'icon'          => 'fas fa-cog',
                'route'         => 'seat-importing.settings',
                'route_segment' => 'seat-importing.settings',
                'permission'    => 'seat-importing.manage',
            ],
        ],
    ],
];
