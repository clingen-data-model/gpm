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
        'ep-manage-application' => [
            'id' => 30,
            'name' => 'ep-applications-manage',
            'guard_name' => 'web',
            'scope' => 'system'
        ]
    ],

    'role_permissions' => [
        'super-user' => [1,10,20,30],
        'super-admin' => [1,10,20,30],
        'admin' => [10,20]
    ]
];