<?php

$definitionType = [
    'id' => 1,
    'name' => 'Definition',
    'description' => 'VCEP step 1 and GCEP application'
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
        ],
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
        'revisions-requested' => [
            'id' => 2,
            'name' => 'Revisions Requested',
            'description' => 'Submitted item must be revised and resubmitted with no promise of approval.',
        ],
        'under-chair-review' => [
            'id' => 3,
            'name' => 'Under Chair Review',
            'description' => 'Submitted item has been sent to CDWG OC Chairs for review.',
        ],
        'approved' => [
            'id' => 4,
            'name' => 'Approved',
            'description' => 'Submitted item is unconditionally approved.'
        ]
    ],
];
