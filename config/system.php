<?php

return [
   'roles' => [
       'super-user' => [
           'id' => 1,
           'name' => 'super-user',
           'display_name' => 'SuperUser',
           'description' => 'Full access user with ALL permissions.  Reserved for programmers and technically versed users charged with administering the application.',
           'guard_name' => 'web',
           'scope' => 'system'
       ],
       'super-admin' => [
            'id' => 2,
            'name' => 'super-admin',
            'display_name' => 'SuperAdmin',
            'description' => 'Administrator with full group, application, annual update, and person management permissions.  A super-admin should be able take any action that does not require technical expertise.',
            'guard_name' => 'web',
            'scope' => 'system'
       ],
       'admin' => [
           'id' => 3,
           'name' => 'admin',
           'display_name' => 'Admin',
           'description' => 'Has standard admin privileges and can manage invites, groups, and people.',
           'guard_name' => 'web',
           'scope' => 'system'
       ]
    ],

    'permissions' => [
        'users-manage' => [
            'id' => 1,
            'name' => 'users-manage',
            'display_name' => 'Manage users',
            'description' => 'Edit user roles and permissions (limited based on role).',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'logs-view' => [
            'id' => 2,
            'name' => 'logs-view',
            'display_name' => 'View System Logs',
            'description' => 'View system logs.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'mail-log-view' => [
            'id' => 3,
            'name' => 'mail-log-view',
            'display_name' => 'View Mail Logs',
            'description' => 'View mail logs and resend email.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'invites-view' => [
            'id' => 4,
            'name' => 'invites-view',
            'display_name' => 'View Invites',
            'description' => 'Invite administration.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'announcements-manage' => [
            'id' => 5,
            'name' => 'announcements-manage',
            'display_name' => 'Manage announcements',
            'description' => 'Create announcements.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'group-manage' => [
            'id' => 10,
            'name' => 'groups-manage',
            'display_name' => 'Manage groups',
            'description' => 'Create, update, delete groups.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'people-manage' => [
            'id' => 20,
            'name' => 'people-manage',
            'display_name' => 'Manage people',
            'description' => 'Create, update, delete, merge people.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'ep-applications-manage' => [
            'id' => 30,
            'name' => 'ep-applications-manage',
            'display_name' => 'Manage applications',
            'description' => 'Create and update applications; mark aplications approved.',
            'guard_name' => 'web',
            'scope' => 'system'
        ],
        'annual-updates-manage' => [
            'id' => 40,
            'name' => 'annual-updates-manage',
            'display_name' => 'Manage Annual Updates',
            'description' => 'Create and update applications; create a new annual update window.',
            'guard_name' => 'web',
            'scope' => 'system'
        ]
    ],

    'role_permissions' => [
        'super-user' => [1,2,3,4,5,10,20,30,40],
        'super-admin' => [1,3,4,5,10,20,30,40],
        'admin' => [3,4,10,20]
    ]
];
