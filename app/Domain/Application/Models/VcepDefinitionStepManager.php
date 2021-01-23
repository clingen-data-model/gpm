<?php

namespace App\Domain\Application\Models;

use App\Domain\Application\Models\Application;
use App\Domain\Application\Models\AbstractStepManager;

class VcepDefinitionStepManager extends AbstractStepManager
{
    public function __construct(private Application $application)
    {
    }
    


    public function canApprove():bool
    {
        if (! $this->isCurrentStep() ) {
            throw new \Exception('Application\'s current step does not match loaded step.');
        }

        if (! $this->meetsAllRequirements() ) {
            return false;
        }

        return true;

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
