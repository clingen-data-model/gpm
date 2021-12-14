<?php

return [
    'types' => [
        'application' => [
            'definition' => [
                'id' => 1, 
                'name' => 'Definition',
                'description' => 'VCEP step 1 and GCEP appliation'
            ],
            'sustained-curation' => [
                'id' => 2,
                'name' => 'Sustained Curation',
                'description' => 'VCEP step 4'
            ]
        ]
    ],
    'statuses' => [
        'pending' => [
            'id' => 1,
            'name' => 'Pending',
            'description' => 'Submission does not have a response.',
        ],
        'revise-and-resubmit' => [
            'id' => 2,
            'name' => 'Revise and Resubmit',
            'description' => 'Submitted item must be revised and resubmitted with no promise of approval.',
        ],
        'approved-w-revisions' => [
            'id' => 3,
            'name' => 'Approved w/ Revisions',
            'description' => 'Submitted item must be revised and will be approved review of revisions.',
        ],
        'approved' => [
            'id' => 4,
            'name' => 'Approved',
            'description' => 'Submitted item is unconditionally approved.'
        ]
    ],
];