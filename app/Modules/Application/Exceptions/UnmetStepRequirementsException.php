<?php

namespace App\Modules\Application\Exceptions;

use Exception;
use App\Modules\Application\Models\Application;

class UnmetStepRequirementsException extends Exception
{
    public function __construct(private Application $application, private array $unmetRequirements)
    {
        parent::__construct('You can not approve this application step b/c there are unmet requirements');
    }

    public function getUnmetRequirements()
    {
        return $this->unmetRequirements;
    }

    public function getApplication()
    {
        return $this->application;
    }
    
}