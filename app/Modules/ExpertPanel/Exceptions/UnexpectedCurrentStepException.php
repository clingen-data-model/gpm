<?php

namespace App\Modules\ExpertPanel\Exceptions;

use Exception;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class UnexpectedCurrentStepException extends Exception
{
    public function __construct(private ExpertPanel  $expertPanel)
    {
        $message = 'Unexpected step '.$expertPanel->current_step.' for application with uuid '.$expertPanel->uuid;
        parent::__construct($message, 422);
    }
    

    public function getApplication()
    {
        return $this->expertPanel;
    }
    
}
