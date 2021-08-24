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
        'coordinator' => 1,
        'chair' => 2,
        'biocurator' => 3,
        'expert' => 4,
        'core-approval-member' => 5
    ],
    'permissions' => [
        'info-edit' => 1,
        'members-invite' => 2,
        'members-retire' => 3,
        'members-remove' => 4,
        'application-read' => 5,
        'application-edit' => 6,
    ]
];
