<?php

namespace App\Modules\ExpertPanel\Exceptions;

use App\Modules\Person\Models\Person;
use App\Modules\ExpertPanel\Models\ExpertPanel;

class PersonNotContactException extends \Exception
{
    public function __construct(private ExpertPanel  $expertPanel, private Person $person)
    {
        $message = 'The specified person is not a contact of this application.';
        parent::__construct($message, 422);
    }

    public function getApplication()
    {
        return $this->expertPanel;
    }

    public function getPerson()
    {
        return $this->person;
    }
    
    
    
}
