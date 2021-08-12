<?php

namespace App\Modules\ExpertPanel\Exceptions;

use Exception;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class UnexpectedCurrentStepException extends Exception
{
    public function __construct(private ExpertPanel  $application)
    {
        $message = 'Unexpected step '.$application->current_step.' for application with uuid '.$application->uuid;
        parent::__construct($message, 422);
    }
    

    public function getApplication()
    {
        return $this->application;
    }
    
}
