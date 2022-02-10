<?php
return [
    'notifications' => [
        'cc' => [
            'steps' => [1,4],
            'recipients' => [
                ['cdwg_oversightcommittee@clinicalgenome.org', 'CDWG Oversite Committee'],
                ['clingentrackerhelp@unc.edu', 'Clingen Tracker Help'],
                ['volunteer@clinicalgenome.org', 'CCDB Support'],
                ['erepo@clinicalgenome.org', 'ERepo Support'],
                ['clingen-helpdesk@lists.stanford.edu', 'GCI/VCI Support'],
            ]
        ]
    ],
    'steps' => [
        1 => 'Group Definition',
        2 => 'Draft Specifications',
        3 => 'Pilot Specifications',
        4 => 'Sustained Curation'
    ]
];
