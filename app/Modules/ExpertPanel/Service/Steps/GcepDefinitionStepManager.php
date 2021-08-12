<?php

namespace App\Modules\ExpertPanel\Service\Steps;

use App\Modules\ExpertPanel\Models\ExpertPanel;

class GcepDefinitionStepManager extends AbstractStepManager
{
    public function __construct(private ExpertPanel  $expertPanel)
    {
    }

    public function getUnmetRequirements():array
    {
        return [];
    }

    public function meetsAllRequirements():bool
    {
        return true;
    }

    public function isCurrentStep():bool
    {
        return $this->expertPanel->current_step == 1 
            && $this->expertPanel->ep_type_id == config('expert_panels.types.gcep.id');
    }

    public function isLastStep():bool
    {
        return true;
    }

    // public function getDocuments():Collection;
    // public function getLogEntries():Collection;
    
}