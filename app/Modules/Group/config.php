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
            'id' => 1,
            'name' => 'Coordinator',
        ],
        'chair' => [
            'id' => 2,
            'name' => 'Chair'
        ],
        'biocurator' => [
            'id' => 3,
            'name' => 'Biocurator'
        ],
        'expert' => [
            'id' => 4,
            'name' => 'Expert'
        ],
        'core-approval-member' => [
            'id' => 5,
            'name' => 'Core Approval Member'
        ],
    ],
    'permissions' => [
        'info-edit' => [
            'id' => 1,
            'name' => 'Edit Info',
        ],
        'members-invite' => [
            'id' => 2,
            'name' => 'Invite Members',
        ],
        'members-retire' => [
            'id' => 3,
            'name' => 'Retire Members'
        ],
        'members-remove' => [
            'id' => 4,
            'name' => 'Remove Members'
        ],
        'application-read' => [
            'id' => 5,
            'name' => 'Read Application'
        ],
        'application-edit' => [
            'id' => 6,
            'name' => 'Edit Application'
        ],
    ],
    'role_permissions' => [
        'coordinator' => [1,2,3,4,5,6]
    ]
];
