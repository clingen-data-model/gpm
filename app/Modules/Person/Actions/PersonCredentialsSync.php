<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;

class PersonCredentialsSync
{
    public function handle(Person $person, $credentialIds): Person
    {
        $person->credentials()->sync($credentialIds);
        return $person;
    }

}
