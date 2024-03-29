<?php

return [
    'assignees' => [
        'cdwg-oc' => [
            'name' => 'CDWG OC',
            'short_name' => 'CDWG',
            'id' => 1
        ],
        'expert-panel' => [
            'name' => 'Expert Panel',
            'short_name' => 'EP',
            'id' => 2
        ],
        'svi-vcep-review-committee' => [
            'name' => 'SVI VCEP Review Committee',
            'short_name' => 'SVI',
            'id' => 3
        ],
        'gene-curation-core-group' => [
            'name' => 'Gene Curation Core Group',
            'short_name' => 'GC',
            'id' => 4,
        ],
        'chairs' => [
            'name' => 'CDWG OC chairs',
            'short_name' => 'Chairs',
            'id' => 5
        ]
    ],
    'types' => [
        'review-submission' => [
            'id' => 1,
            'name' => 'review submission',
            'description' => 'Admin group should review submission',
            'default_entry' => 'Review application and respond to EP.'
        ],
        'make-revisions' => [
            'id' => 2,
            'name' => 'make revisions',
            'description' => 'The group has application revisions to complted',
            'default_entry' => 'Update application and resubmit for approval.'
        ],
        'chair-review' => [
            'id' => 3,
            'name' => 'chair review',
            'description' => 'Chairs should review the application and provide feedback',
            'default_entry' => 'Chairs review application and provide feedback.'
        ]
    ]

];
