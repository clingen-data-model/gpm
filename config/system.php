<?php

return [
   'roles' => [
       'super-user' => [
           'id' => 1,
           'name' => 'super-user',
           'guard_name' => 'web',
           'scope' => 'system'
       ],
       'super-admin' => [
            'id' => 2,
            'name' => 'super-admin',
            'guard_name' => 'web',
            'scope' => 'system'
       ],
       'admin' => [
           'id' => 3,
           'name' => 'admin',
           'guard_name' => 'web',
           'scope' => 'system'
       ]
    ],

    'permissions' => [
        'users-manage' => [
            'id' => 1,
            'name' => 'users-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'logs-view' => [
            'id' => 2,
            'name' => 'logs-view',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'mail-log-view' => [
            'id' => 3,
            'name' => 'mail-log-view',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'invites-view' => [
            'id' => 4,
            'name' => 'invites-view',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'group-manage' => [
            'id' => 10,
            'name' => 'groups-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'people-manage' => [
            'id' => 20,
            'name' => 'people-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'ep-applications-manage' => [
            'id' => 30,
            'name' => 'ep-applications-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'annual-updates-manage' => [
            'id' => 40,
            'name' => 'annual-updates-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ]
    ],

    'role_permissions' => [
        'super-user' => [1,2,3,4,10,20,30,40],
        'super-admin' => [1,3,4,10,20,30,40],
        'admin' => [3,4,10,20]
    ]
];
