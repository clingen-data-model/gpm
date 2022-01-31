<?php

namespace App\Modules\Person\Events;

use App\Modules\User\Models\User;
use App\Modules\Person\Models\Person;

class PersonUnlinkedFromUser extends PersonEvent
{
    public function __construct(public Person $person, public User $user)
    {
    }

    public function getLogEntry(): string
    {
        return 'Unlinked from user '.$this->user->name.' ('.$this->user->id.')';
    }

    public function getProperties(): array
    {
        return [
            'user' => $this->user
        ];
    }
}
