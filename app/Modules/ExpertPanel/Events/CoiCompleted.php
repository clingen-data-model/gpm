<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\Coi;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

// FIXME: should be under Group namespace rather than ExpertPanel
class CoiCompleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Coi $coi)
    {
    }

    public function getLogEntry(): string
    {
        return ($this->coi->groupMember)
            ? 'COI form completed by ' . $this->coi->groupMember->person->email
            : 'Legacy COI uploaded';
    }

    public function getProperties(): array
    {
        return (array) $this->coi->data;
    }

}
