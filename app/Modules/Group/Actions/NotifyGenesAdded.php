<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Events\GenesAdded;
use App\Modules\Group\Mail\GeneAddedMail;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsListener;

class NotifyGenesAdded
{
    use AsListener;

    public function handle(Group $group, Collection $genes): void
    {
        if (! config('app.features.notify_scope_change')) {
            return;
        }
        $mailClass = new GeneAddedMail($group, $genes);
        Mail::to(config('mail.from.address'))->send($mailClass);
    }

    public function asListener(GenesAdded $event): void
    {
        if (! $event->group->expertPanel->definitionIsApproved) {
            return;
        }

        $this->handle($event->group, $event->genes);
    }
}
