<?php

namespace App\Modules\ExpertPanel\Service\Steps;

use App\Modules\ExpertPanel\Models\ExpertPanel;

abstract class AbstractStepManager implements StepManagerInterface
{
    public function __construct(private ExpertPanel  $expertPanel)
    {
    }

    abstract public function isCurrentStep():bool;
    abstract public function meetsAllRequirements():bool;
    abstract public function getUnmetRequirements():array;

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
    
    public function isLastStep():bool
    {
        return false;
    }
}
