<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Models\ScopeOfWorkVersion;

class MemberUnretired extends GroupMemberEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name . ' "un-retired" from ' . $this->groupMember->group->displayName;
    }

    public function getSubject(): Group
    {
        return $this->groupMember->group;
    }

    public function shouldPublish(): bool
    {
        return parent::shouldPublish() && ! ScopeOfWorkVersion::forGroup($this->groupMember->group)->approved()->exists();
    }

    // No additional properties beyond those in GroupMemberEvent
}
