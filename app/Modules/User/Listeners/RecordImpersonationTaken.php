<?php

namespace App\Modules\User\Listeners;

use Illuminate\Support\Facades\Event;
use Lab404\Impersonate\Events\TakeImpersonation;
use App\Modules\User\Events\UserImpersonationStarted;

/**
 * Bridge lab404's take event to a recordable domain event.
 */
class RecordImpersonationTaken
{
    public function handle(TakeImpersonation $event): void
    {
        Event::dispatch(new UserImpersonationStarted($event->impersonated, $event->impersonator));
    }
}
