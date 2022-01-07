<?php

namespace App\Modules\Group\Actions;

use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Mail;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GeneRemoved;
use App\Modules\Group\Mail\GeneRemovedMail;
use Lorisleiva\Actions\Concerns\AsListener;

class NotifyGenesRemoved
{
    use AsListener;

    public function handle(Group $group, Gene $gene): void
    {
        $mailClass = new GeneRemovedMail($group, $gene);
        Mail::to(config('mail.from.address'))->send($mailClass);
    }

    public function asListener(GeneRemoved $event): void
    {
        if (!$event->group->expertPanel->definitionIsApproved) {
            return;
        }

        $this->handle($event->group, $event->gene);
    }
}
