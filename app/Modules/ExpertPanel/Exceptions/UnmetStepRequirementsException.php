<?php

namespace App\Modules\ExpertPanel\Exceptions;

use Exception;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class UnmetStepRequirementsException extends Exception
{
    public function __construct(private ExpertPanel  $expertPanel, private array $unmetRequirements)
    {
        parent::__construct('You can not approve this application step b/c there are unmet requirements');
    }

    public function getUnmetRequirements()
    {
        return $this->unmetRequirements;
    }

    public function getApplication()
    {
        return $this->expertPanel;
    }
    
}