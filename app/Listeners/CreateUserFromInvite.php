<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\Person\Events\InviteRedeemed;
use App\Events\AppModulesPersonEventsInviteRedeemed;
use App\Modules\User\Actions\UserCreate;

class CreateUserFromInvite
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(private UserCreate $createUser)
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  AppModulesPersonEventsInviteRedeemed  $event
     * @return void
     */
    public function handle(InviteRedeemed $event)
    {
        // $user = $this->createUser->handle(
        //     name: $event->person->first_name.' '.$event->person->last_name,
        //     email: $event->email,
        //     password: $event->password
        // );

        // $event->person
        //     ->user()
        //     ->associate($user)
        //     ->save();
    }
}
