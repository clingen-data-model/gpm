<?php

namespace App\Modules\Application\Service\Steps;

use App\Modules\Application\Models\Application;

class VcepPilotStepManager extends AbstractStepManager
{
    public function __construct(private Application $application)
    {
    }

    public function isCurrentStep():bool
    {
        return $this->application->ep_type_id == config('expert_panels.types.vcep.id')
            && $this->application->current_step == 3;
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
