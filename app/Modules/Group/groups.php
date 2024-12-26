<?php

return [
    'types' => [
        'wg' => [
            'id' => 1,
            'name' => 'wg',
            'fullname' => 'Working Group',
            'display_name' => 'Working Group',
            'description' => 'A working group that is not a Clinical Domain Working Group',
            'can_be_parent' => true,
        ],
        'cdwg' => [
            'id' => 2,
            'name' => 'cdwg',
            'fullname' => 'Clinical Domain Working Group',
            'display_name' => 'Clinical Domain Working Group',
            'description' => 'A Clinical Domain Working Group that oversees Expert Panels.',
            'can_be_parent' => true,
        ],
        'gcep' => [
            'id' => 3,
            'name' => 'gcep',
            'fullname' => 'Gene Curation Expert Panel',
            'display_name' => 'GCEP',
            'description' => 'A Gene curation expert panel',
            'can_be_parent' => false,
        ],
        'vcep' => [
            'id' => 4,
            'name' => 'vcep',
            'display_name' => 'VCEP',
            'fullname' => 'Variant Curation Expert Panel',
            'description' => 'A Variant curation expert panel',
            'can_be_parent' => false,
        ],
        'scvcep' => [
            'id' => 5,
            'name' => 'scvcep',
            'fullname' => 'Somatic Cancer Variant Curation Expert Panel',
            'display_name' => 'SCVCEP',
            'description' => 'A Somatic cancer variant curation expert panel',
            'can_be_parent' => false,
        ],
    ],
    'statuses' => [
        'applying' => [
            'id' => 1,
            'name' => 'applying',
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
        4 => 'black',
        5 => 'gray'
    ],
    'roles' => [
        'coordinator' => [
            'id' => 101,
            'name' => 'coordinator',
            'display_name' => 'Coordinator',
            'description' => 'The coordinator of the group. Has all group permissions.',
        ],
        'chair' => [
            'id' => 102,
            'name' => 'chair',
            'display_name' => 'Chair',
            'description' => 'A chair/co-chair of the group.  No default permissions.',
        ],
        'biocurator' => [
            'id' => 103,
            'name' => 'biocurator',
            'display_name' => 'Biocurator',
            'description' => 'Biocurator designation.  No default permissions.',
        ],
        'expert' => [
            'id' => 104,
            'name' => 'expert',
            'display_name' => 'Expert',
            'description' => 'Expert designation.  No default permissions.',
        ],
        'core-approval-member' => [
            'id' => 105,
            'name' => 'core-approval-member',
            'display_name' => 'Core Approval Member',
            'description' => 'Core approval member designation.  No default permissions.',
        ],
        'biocurator-trainer' => [
            'id' => 106,
            'name' => 'biocurator-trainer',
            'display_name' => 'Biocurator trainer',
            'description' => 'Biocurator trainer designation.  No default permissions.',
        ],
        'civic-editor' => [
            'id' => 106,
            'name' => 'civic-editor',
            'display_name' => 'CIVic Editor',
            'description' => 'Individuals approves the submission of edits to CIViC. Indicates a higher level of training.  No default permissions.',
        ],
    ],
    'permissions' => [
        'info-edit' => [
            'id' => 1001,
            'name' => 'info-edit',
            'display_name' => 'Edit Info',
            'description' => 'Can edit group name.',
        ],
        'members-invite' => [
            'id' => 1002,
            'name' => 'members-invite',
            'display_name' => 'Invite Members',
            'description' => 'Can invite people to join the group.',
        ],
        'members-retire' => [
            'id' => 1003,
            'name' => 'members-retire',
            'display_name' => 'Retire Members',
            'description' => 'Can retire group members.',
        ],
        'members-remove' => [
            'id' => 1004,
            'name' => 'members-remove',
            'display_name' => 'Remove Members',
            'description' => 'Can remove group members.',
        ],
        'members-update' => [
            'id' => 1005,
            'name' => 'members-update',
            'display_name' => 'Update Members',
            'description' => 'Can update group member information.',
        ],
        'application-read' => [
            'id' => 1006,
            'name' => 'application-read',
            'display_name' => 'Read Application',
            'description' => 'Can read the group\'s application (if applicable).',
        ],
        'application-edit' => [
            'id' => 1007,
            'name' => 'application-edit',
            'display_name' => 'Edit Application',
            'description' => 'Can update group\'s application (if applicable).',
        ],
        'annual-update-manage' => [
            'id' => 1008,
            'name' => 'annual-update-manage',
            'display_name' => 'Manage Annual Update',
            'description' => 'Can edit and submit an annual update.',
        ],
    ],
    'role_permissions' => [
        'coordinator' => [1001,1002,1003,1004,1005,1006, 1007, 1008]
    ],
];
