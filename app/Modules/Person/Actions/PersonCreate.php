<?php

namespace App\Modules\Person\Actions;

use Illuminate\Support\Facades\DB;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Person\Events\PersonCreated;

class PersonCreate
{
    public function handle(
        string $uuid,
        string $first_name,
        string $last_name,
        string $email,
        ?string $phone = null,
        ?int $user_id = null,
    ): Person {
        $person = Person::create([
            'uuid' => $uuid,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'user_id' => $user_id,
        ]);
        Event::dispatch(new PersonCreated($person));
        
        return $person;
    }
}
