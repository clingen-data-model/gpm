<?php

namespace App\Domain\Application\Service\Steps;

use App\Domain\Application\Models\Application;

abstract class AbstractStepManager implements StepManagerInterface
{
    public function __construct(private Application $application)
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
