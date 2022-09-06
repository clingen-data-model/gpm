<?php

$statuses = [
    'proposed' => [
        'id' => 1,
        "name" => "Proposed",
        "event" => "proposal-submitted",
        "description" => "A new specicification document CSpec document created with scopes and VCEP affiliation defined",
        'color' => 'blue',
      ],
      'rules-classification' => [
        'id' => 2,
        "name" => "RulesClassification",
        "event" => "proposal-approved",
        "description" => "A CSpec document and scopes are in agreement with GPM and a document is ready to proceed to Rules classification. This event requires GMP notification of the Step 1 approval - ep_definition_approved",
        'color' => 'blue',
      ],
      'classified-rules-under-review' => [
        'id' => 3,
        "name" => "ClassifiedRulesUnderReview",
        "event" => "classified-rules-submitted",
        "description" => "Initial draft of the CSpec with rules for all the applicable scopes are submitted for review",
        'color' => 'yellow',
      ],
      'pilot-rules-classification' => [
        'id' => 4,
        "name" => "PilotRulesClassification",
        "event" => "classified-rules-approved",
        "description" => "Classified rules from the initial draft are approved by the SVI",
        'color' => 'blue',
      ],
      'classified-pilot-rules-under-review' => [
        'id' => 5,
        "name" => "ClassifiedPilotRulesUnderReview",
        "event" => "pilot-rules-submitted",
        "description" => "Pilot rules after validation are submitted for review for all the applicable scopes",
        'color' => 'yellow',
      ],
      'c-spec-final-review' => [
        'id' => 6,
        "name" => "CSpecFinalReview",
        "event" => "pilot-rules-approved",
        "description" => "Pilot rules after validation are submitted for review for all the applicable scopes",
        'color' => 'yellow',
      ],
      'approved-for-release' => [
        'id' => 7,
        "name" => "ApprovedForRelease",
        "event" => "vcep-final-release-approved",
        "description" => "VCEP final approval notification receieved from GPM - ep_final_approval",
        'color' => 'green',
      ],
      'released' => [
        'id' => 8,
        "name" => "Released",
        "event" => "all-cspec-release-approved",
        "description" => "All the specifications within the VCEP are approved for release in sync with the Step 4 application approval",
        'color' => 'green',
      ]
];

return [
    'statuses' => $statuses,
    'rulesets' => [
        'statuses' => $statuses
    ]
];
