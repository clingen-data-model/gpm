<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\Ruleset;
use App\Modules\ExpertPanel\Models\RulesetStatus;
use App\Modules\ExpertPanel\Models\Specification;


class SpecificationRulesetSync
{


    public function handle(Specification $specification, string $cspecRulesetId, RulesetStatus $status): Ruleset
    {
        return $specification
            ->rulesets()
            ->updateOrCreate(
                [ 'cspec_ruleset_id' => $cspecRulesetId ],
                [ 'status_id' => $status->id ]
            );
    }
}
