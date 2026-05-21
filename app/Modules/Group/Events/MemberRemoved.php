<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class MemberRemoved extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name . ' removed from group on ' . $this->groupMember->end_date->format('Y-m-d');
    }

    public function getSubject(): Group
    {
        return $this->groupMember->group;
    }

    public function getProperties(): array
    {
        $props = parent::getProperties();
        $props['end_date'] = $this->groupMember->end_date->toAtomString();
        return $props;
    }

    public function shouldPublish(): bool
    {
        return parent::shouldPublish() && ! ScopeOfWorkVersion::forGroup($this->group)->approved()->exists();
    }

}
