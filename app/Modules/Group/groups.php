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
        'pending-approval' => [
            'id' => 1,
            'name' => 'pending-approval',
        ],
        'active' => [
            'id' => 2,
            'name' => 'active',
        ],
        'retired' => [
            'id' => 3,
            'name' => 'retired',
        ],
        'removed' => [
            'id' => 4,
            'name' => 'removed'
        ],
        'inactive' => [
            'id' => 5,
            'name' => 'inactive'
        ]

    ],
    'status_colors' => [
        1 => 'blue',
        2 => 'green',
        3 => 'gray',
        4 => 'black'
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
        'biocurator-trainer' => [
            'id' => 106,
            'name' => 'biocurator-trainer'
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
        'members-update' => [
            'id' => 1005,
            'name' => 'members-update'
        ],
        'application-read' => [
            'id' => 1006,
            'name' => 'application-read'
        ],
        'application-edit' => [
            'id' => 1007,
            'name' => 'application-edit'
        ],
    ],
    'role_permissions' => [
        'coordinator' => [1001,1002,1003,1004,1005,1006, 1007]
    ],
];
