<?php

namespace App\Domain\Application\Models;

use App\Domain\Application\Models\StepManagerInterface;

abstract class AbstractStepManager implements StepManagerInterface
{
    public function __construct(private Application $application)
    {
    }

    abstract public function canApprove():bool;
    abstract public function isCurrentStep():bool;
    abstract public function meetsAllRequirements():bool;
    abstract public function getUnmetRequirements():array;


}
