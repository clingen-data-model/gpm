<?php

namespace App\Modules\ExpertPanel\Service\Steps;

use App\Modules\ExpertPanel\Models\ExpertPanel;

class VcepPilotStepManager extends AbstractStepManager
{
    public function __construct(private ExpertPanel  $expertPanel)
    {
    }

    public function isCurrentStep():bool
    {
        return $this->expertPanel->ep_type_id == config('expert_panels.types.vcep.id')
            && $this->expertPanel->current_step == 3;
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
