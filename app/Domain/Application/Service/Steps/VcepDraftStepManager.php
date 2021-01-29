<?php

namespace App\Domain\Application\Service\Steps;

use App\Domain\Application\Models\Application;

class VcepDraftStepManager extends AbstractStepManager
{
    public function __construct(private Application $application)
    {
    }

    public function isCurrentStep():bool
    {
        return $this->application->ep_type_id == config('expert_panels.types.vcep.id')
            && $this->application->current_step == 2;
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
