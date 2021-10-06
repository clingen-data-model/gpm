<?php

return [
    'types' => [
        'wg' => [
            'id' => 1,
            'name' => 'wg',
            'fullname' => 'Working Group',
            'description' => 'A working group that is not a Clinical Domain Working Group',
        ],
        'cdwg' => [
            'id' => 2,
            'name' => 'cdwg',
            'fullname' => 'Clinical Domain Working Group',
            'description' => 'A Clinical Domain Working Group that oversees Expert Panels.'
        ],
        'ep' => [
            'id' => 3,
            'name' => 'ep',
            'fullname' => 'Expert Panel',
            'description' => 'An Expert Panel'
        ]
    ],
    'statuses' => [
        'pending-approval' => 1,
        'active' => 2,
        'retired' => 3,
        'removed' => 4
    ],
    'roles' => [
        'coordinator' => [
            'id' => 101,
            'name' => 'Coordinator',
        ],
        'chair' => [
            'id' => 102,
            'name' => 'Chair'
        ],
        'biocurator' => [
            'id' => 103,
            'name' => 'Biocurator'
        ],
        'expert' => [
            'id' => 104,
            'name' => 'Expert'
        ],
        'core-approval-member' => [
            'id' => 105,
            'name' => 'Core Approval Member'
        ],
    ],
    'permissions' => [
        'info-edit' => [
            'id' => 1001,
            'name' => 'Edit Info',
        ],
        'members-invite' => [
            'id' => 1002,
            'name' => 'Invite Members',
        ],
        'members-retire' => [
            'id' => 1003,
            'name' => 'Retire Members'
        ],
        'members-remove' => [
            'id' => 1004,
            'name' => 'Remove Members'
        ],
        'application-read' => [
            'id' => 1005,
            'name' => 'Read Application'
        ],
        'application-edit' => [
            'id' => 1006,
            'name' => 'Edit Application'
        ],
    ],
    'role_permissions' => [
        'coordinator' => [1001,1002,1003,1004,1005,1006]
    ]
];
