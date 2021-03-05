<?php

namespace App\Modules\Application\Exceptions;

use Exception;
use App\Modules\Application\Models\Application;

class UnexpectedCurrentStepException extends Exception
{
    public function __construct(private Application $application)
    {
        $message = 'Unexpected step '.$application->current_step.' for application with uuid '.$application->uuid;
        parent::__construct($message, 422);
    }
    

    public function getApplication()
    {
        return $this->application;
    }
    
}
