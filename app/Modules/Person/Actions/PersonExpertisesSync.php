<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;

class PersonExpertisesSync
{
    public function handle(Person $person, $expertiseIds): Person
    {
        $person->expertises()->sync($expertiseIds);
        return $person;
    }

}
