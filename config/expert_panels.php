<?php

return [
    'types' => [
        'gcep' => [
            'id' => 1,
            'name' => 'gcep',
            'display_name' => 'GCEP',
            'full_name' => 'Gene Curation Expert Panel',
        ],
        'vcep' => [
            'id' => 2,
            'name' => 'vcep',
            'display_name' => 'VCEP',
            'full_name' => 'Variant Curation Expert Panel',
        ],
        'scvcep' => [
            'id' => 3,
            'name' => 'scvcep',
            'display_name' => 'SCVCEP',
            'full_name' => 'Somatic Cancer Variant Curation Expert Panel',
        ]
    ],
    'curation_protocols' => [
        'single-biocurator' => [
            'id' => 1,
            'name' => 'single-biocurator',
            'full_name' => 'Single biocurator followed by EP discussion'
        ],
        'biocurator-export' => [
            'id' => 2,
            'name' => 'biocurator-expert',
            'full_name' => 'Paired biocurator/expert review followed by expidated EP approval',
        ],
        'paired-biocurator' => [
            'id' => 3,
            'name' => 'paired-biocurator',
            'full_name' => 'Dual biocurator review with expedited EP review for concordant genes and full review discordant genes.',
        ],
        'other' => [
            'id' => 100,
            'name' => 'other',
            'full_name' => 'other',
        ]
    ]
];
