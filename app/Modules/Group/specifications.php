<?php

return [
    'statuses' => [

    ],
    'rulesets' => [
        'statuses' => [
            'proposed' => [
                'id' => 1,
                'name' => 'proposed',
                "description" => "A new specicification document CSpec document created with scopes and VCEP affiliation defined",

                'color' => 'blue'
            ],
            'rules-classification' => [
                'id' => 2,
                'name' => 'rules-classification',
                "description" => "A CSpec document and scopes are in agreement with GPM and a document is ready to proceed to Rules classification. This event requires GMP notification of the Step 1 approval - ep_definition_approved",
                'color' => 'blue'
            ],
            'classified-rules-under-review' => [
                'id' => 3,
                'name' => 'classified-rules-under-review',
                "description" => "Initial draft of the CSpec with rules for all the applicable scopes are submitted for review",
                'color' => 'yellow'
            ],
            'pilot-rules-classification' => [
                'id' => 4,
                'name' => 'pilot-rules-classification',
                "description" => "Classified rules from the initial draft are approved by the SVI",
                'color' => 'blue'
            ],
            'classified-pilot-rules-under-review' => [
                'id' => 5,
                'name' => 'classified-pilot-rules-under-review',
                "description" => "Pilot rules after validation are submitted for review for all the applicable scopes",
                'color' => 'yellow'
            ],
            'c-spec-final-review' => [
                'id' => 6,
                'name' => 'c-spec-final-review',
                "description" => "Pilot rules after validation are submitted for review for all the applicable scopes",
                'color' => 'yellow'
            ],
            'approved-for-release' => [
                'id' => 7,
                'name' => 'approved-for-release',
                "description" => "VCEP final approval notification receieved from GPM - ep_final_approval",
                'color' => 'green'
            ],
            'released' => [
                'id' => 8,
                'name' => 'released',
                "description" => "All the specifications within the VCEP are approved for release in sync with the Step 4 application approval",
                'color' => 'green'
            ],
        ]
    ]
];
