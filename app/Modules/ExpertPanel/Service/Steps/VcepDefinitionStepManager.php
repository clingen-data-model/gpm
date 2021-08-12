<?php

namespace App\Modules\ExpertPanel\Service\Steps;

use App\Modules\ExpertPanel\Models\ExpertPanel;

class VcepDefinitionStepManager extends AbstractStepManager
{
    public function __construct(private ExpertPanel  $application)
    {
    }

    public function isCurrentStep():bool
    {
        return $this->application->ep_type_id == config('expert_panels.types.vcep.id')
            && $this->application->current_step == 1;
    }

    public function meetsAllRequirements():bool
    {
        return true;
    }

    public function getUnmetRequirements():array
    {
        return [];
    }
        
}
