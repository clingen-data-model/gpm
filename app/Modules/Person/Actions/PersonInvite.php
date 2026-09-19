<?php

namespace App\Modules\Person\Actions;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use App\Modules\Group\Models\Group;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Models\Person;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use App\Modules\Person\Events\PersonInvited;
use App\Services\Clerk\ClerkInvitationService;

class PersonInvite
{
    public function __construct(
        private ClerkInvitationService $clerkInvitationService
    ) {
    }

    public function handle(Person $person, ?Model $inviter = null, bool $dispatchEvent = true): Invite
    {
        $invite = Invite::create([
            'inviter_id' => ($inviter) ? $inviter->id : null,
            'inviter_type' => ($inviter) ? get_class($inviter) : null,
            'person_id' => $person->id,
            'email' => $person->email,
            'first_name' => $person->first_name,
            'last_name' => $person->last_name,
        ]);

        // Every invite needs a Clerk invitation: the accept page can only complete a sign-up
        // with the __clerk_ticket that the Clerk invitation URL carries.
        $clerkInvitation = $this->clerkInvitationService->createForInvite($invite, $inviter instanceof Group ? $inviter : null);
        $clerkExpiresAt = data_get($clerkInvitation, 'expires_at');
        $invite->update([
            'clerk_invitation_id' => data_get($clerkInvitation, 'id'),
            'clerk_invitation_url' => data_get($clerkInvitation, 'url'),
            'expires_at' => $clerkExpiresAt ? Carbon::createFromTimestampMs($clerkExpiresAt) : now()->addDays(30),
        ]);

        $invite = $invite->fresh();

        if ($dispatchEvent) {
            Event::dispatch(new PersonInvited($invite));
        }

        return $invite;
    }
}
