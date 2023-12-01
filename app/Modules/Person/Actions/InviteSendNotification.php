<?php

namespace App\Modules\Person\Actions;

use App\Modules\Person\Events\PersonInvited;
use App\Modules\Person\Models\Invite;
use App\Modules\Person\Notifications\InviteNotification;
use Illuminate\Support\Facades\Notification;
use Lorisleiva\Actions\Concerns\AsObject;

class InviteSendNotification
{
    use AsObject;

    public function handle(Invite $invite): Invite
    {
        Notification::send($invite->person, new InviteNotification($invite));

        return $invite;
    }

    public function listen(PersonInvited $event)
    {
        return $this->handle($event->invite);
    }
}
