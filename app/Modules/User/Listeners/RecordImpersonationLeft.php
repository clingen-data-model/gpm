<?php

namespace App\Modules\User\Listeners;

use Illuminate\Support\Facades\Event;
use Lab404\Impersonate\Events\LeaveImpersonation;
use App\Modules\User\Events\UserImpersonationEnded;

/**
 * Bridge lab404's leave event to a recordable domain event.
 */
class RecordImpersonationLeft
{
    public function handle(LeaveImpersonation $event): void
    {
        Event::dispatch(new UserImpersonationEnded($event->impersonated, $event->impersonator));
    }
}
