<?php

return [
    'seat-assets' => [
        'name' => 'Asset Manager',
        'icon' => 'fas fa-boxes',
        'route_segment' => 'seat-assets',
        'entries' => [
            [
                'name' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'route' => 'seat-assets::dashboard',
            ],
        ],
    ],
];
