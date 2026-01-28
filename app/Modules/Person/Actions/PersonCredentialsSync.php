<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;

class PersonCredentialsSync
{
    public function handle(Person $person, $credentialIds): Person
    {
        $credentialsWithOrder = collect($credentialIds)
            ->mapWithKeys(function ($credentialId, $index) {
                return [$credentialId => ['sort_order' => $index]];
            })
            ->toArray();

        $person->credentials()->sync($credentialsWithOrder);
        $person->touch();
        return $person;
    }

}
