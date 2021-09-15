<?php

namespace App\Modules\Person\Actions;

use Ramsey\Uuid\Uuid;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use App\Modules\Person\Events\PersonInvited;

class PersonInvite
{
    public function __construct(private PersonCreate $createPerson)
    {
    }

    public function handle(array $data): array
    {
        $data['uuid'] = Uuid::uuid4();
        $person = $this->createPerson->handle(...collect($data)->only('uuid', 'first_name', 'last_name', 'email'));

        $invite = Invite::create([
            'inviter_id' => isset($data['inviter_id']) ? $data['inviter_id'] : null,
            'inviter_type' => isset($data['inviter_type']) ? $data['inviter_type'] : null,
            // 'inviter_type' => isset($data['inviter_type']) ? $data['inviter_type'] : null,
            'person_id' => $person->id,
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
        ]);

        Event::dispatch(new PersonInvited($invite));

        return [$person, $invite];
    }
}
