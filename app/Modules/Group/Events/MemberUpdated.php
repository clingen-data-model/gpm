<?php

namespace App\Modules\Group\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Models\GroupMember;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

// FIXME: consider whether this should be publishable (or even a GroupMemberEvent)

class MemberUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public GroupMember $groupMember, public array $data)
    {
        parent::__construct($this->groupMember->group);
    }

    public function getLogEntry(): string
    {
        return $this->groupMember->person->name . ' updated';
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getProperties(): ?array
    {
        return [
            'group_member' => $this->groupMember->person->only('id', 'uuid', 'name', 'email'),
            'new_data' => $this->data,
        ];
    }

}
