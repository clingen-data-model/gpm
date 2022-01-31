<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Models\Person;
use Lorisleiva\Actions\Concerns\AsAction;
use App\Modules\Person\Events\PersonUnlinkedFromUser;

class PersonUnlinkUser
{
    use AsAction;

    public function handle(Person $person)
    {
        if (is_null($person->user)) {
            return $person;
        }
        
        dd($person->user);

        $user = $person->user;
        $person->update(['user_id' => null]);

        event(new PersonUnlinkedFromUser($person, $user));

        return $person;
    }
    
}
