<?php

namespace App\Modules\Application\Service\Steps;

use App\Modules\Application\Models\Application;

class GcepDefinitionStepManager extends AbstractStepManager
{
    public function __construct(private Application $application)
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
        return $this->application->current_step == 1 
            && $this->application->ep_type_id == config('expert_panels.types.gcep.id');
    }

    public function isLastStep():bool
    {
        return true;
    }

    // public function getDocuments():Collection;
    // public function getLogEntries():Collection;
    
}