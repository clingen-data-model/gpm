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
            'name' => 'coordinator',
        ],
        'chair' => [
            'id' => 102,
            'name' => 'chair'
        ],
        'biocurator' => [
            'id' => 103,
            'name' => 'biocurator'
        ],
        'expert' => [
            'id' => 104,
            'name' => 'expert'
        ],
        'core-approval-member' => [
            'id' => 105,
            'name' => 'core-approval-member'
        ],
    ],
    'permissions' => [
        'info-edit' => [
            'id' => 1001,
            'name' => 'info-edit',
        ],
        'members-invite' => [
            'id' => 1002,
            'name' => 'members-invite',
        ],
        'members-retire' => [
            'id' => 1003,
            'name' => 'members-retire'
        ],
        'members-remove' => [
            'id' => 1004,
            'name' => 'members-remove'
        ],
        'application-read' => [
            'id' => 1005,
            'name' => 'application-read'
        ],
        'application-edit' => [
            'id' => 1006,
            'name' => 'application-edit'
        ],
    ],
    'role_permissions' => [
        'coordinator' => [1001,1002,1003,1004,1005,1006]
    ]
];
