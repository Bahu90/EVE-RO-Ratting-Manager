<?php

// A permission list means any one of them is enough, which is how a director
// or admin sees the member pages without holding the member permission too.
$member = ['ratting-manager.member', 'ratting-manager.director', 'ratting-manager.admin'];
$director = ['ratting-manager.director', 'ratting-manager.admin'];

return [
    'ratting-manager' => [
        'name'          => 'Ratting Manager',
        'label'         => 'ratting-manager::menu.ratting_manager',
        'plural'        => true,
        'icon'          => 'fas fa-crosshairs',
        'route_segment' => 'ratting-manager',
        'permission'    => $member,
        'entries'       => [
            [
                'name'       => 'Dashboard',
                'label'      => 'ratting-manager::menu.dashboard',
                'icon'       => 'fas fa-tachometer-alt',
                'route'      => 'ratting-manager.dashboard',
                'permission' => $member,
            ],
            [
                'name'       => 'Leaderboard',
                'label'      => 'ratting-manager::menu.leaderboard',
                'icon'       => 'fas fa-trophy',
                'route'      => 'ratting-manager.leaderboard',
                'permission' => $member,
            ],
            [
                'name'       => 'Ratter Lookup',
                'label'      => 'ratting-manager::menu.ratter_lookup',
                'icon'       => 'fas fa-search',
                'route'      => 'ratting-manager.ratters.index',
                'permission' => $director,
            ],
            [
                'name'       => 'Settings',
                'label'      => 'ratting-manager::menu.settings',
                'icon'       => 'fas fa-cog',
                'route'      => 'ratting-manager.settings.index',
                'permission' => 'ratting-manager.admin',
            ],
            [
                'name'       => 'Help',
                'label'      => 'ratting-manager::menu.help',
                'icon'       => 'fas fa-question-circle',
                'route'      => 'ratting-manager.help',
                'permission' => ['ratting-manager.view', 'ratting-manager.member', 'ratting-manager.director', 'ratting-manager.admin'],
            ],
        ],
    ],
];
