<?php

$definitionType = [
    'id' => 1,
    'name' => 'Definition',
    'description' => 'VCEP step 1 and GCEP appliation'
];
$sustainedCurationType = [
    'id' => 2,
    'name' => 'Sustained Curation',
    'description' => 'VCEP step 4'
];

return [
    'types' => [
        'application' => [
            'definition' => $definitionType,
            'sustained-curation' => $sustainedCurationType
        ]
    ],
    'types-by-step' => [
        1 => $definitionType,
        2 => null,
        3 => null,
        4 => $sustainedCurationType
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
