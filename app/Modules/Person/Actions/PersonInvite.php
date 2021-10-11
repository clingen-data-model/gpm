<?php

namespace App\Modules\Person\Actions;

use Ramsey\Uuid\Uuid;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Person\Events\PersonInvited;

class PersonInvite
{
    public function __construct()
    {
    }

    public function handle(Person $person, ?Model $inviter = null): Invite
    {
        $invite = Invite::create([
            'inviter_id' => ($inviter) ? $inviter->id : null,
            'inviter_type' => ($inviter) ? get_class($inviter) : null,
            'person_id' => $person->id,
            'email' => $person->email,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
        ]);

        Event::dispatch(new PersonInvited($invite));

        return $invite;
    }
}
