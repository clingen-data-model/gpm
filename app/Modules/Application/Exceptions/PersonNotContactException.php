<?php

namespace App\Modules\Application\Exceptions;

use App\Modules\Person\Models\Person;
use App\Modules\Application\Models\Application;

class PersonNotContactException extends \Exception
{
    public function __construct(private Application $application, private Person $person)
    {
        $message = 'The specified person is not a contact of this application.';
        parent::__construct($message, 422);
    }

    public function getApplication()
    {
        return $this->application;
    }

    public function getPerson()
    {
        return $this->person;
    }
    
    
    
}
