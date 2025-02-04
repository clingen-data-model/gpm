<?php

namespace App\Modules\ExpertPanel\Actions;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use App\Modules\ExpertPanel\Actions\SpecificationRulesetSync;
use App\Modules\ExpertPanel\Events\SpecificationStatusUpdated;

class SpecificationAndRulesetsSync
{
    public function __construct(
        private SpecificationSync $syncSpecification,
        private SpecificationRulesetSync $syncRuleset
    ) {
    }


    public function handle(
        string $cspecId,
        ExpertPanel $expertPanel,
        string $name,
        object $status,
        array $rulesets
    ): ?Specification {
        $specification = $this->syncSpecification->handle(
            cspecId: $cspecId,
            expertPanel: $expertPanel,
            name: $name,
            status: $status->current
        );

        foreach ($rulesets as $ruleset) {
            $this->syncRuleset->handle(
                specification: $specification,
                cspecRulesetId: $ruleset->ruleSetId,
                status: $status->current,
            );
        }

        event(new SpecificationStatusUpdated(
            expertPanel: $expertPanel,
            specification: $specification,
        ));

        return $specification;
    }
}
