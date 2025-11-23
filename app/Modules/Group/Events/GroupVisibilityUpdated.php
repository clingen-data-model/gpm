<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupVisibility;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GroupVisibilityUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public GroupVisibility $newVisibility, public GroupVisibility $oldVisibility)
    {
    }

    public function getLogEntry(): string
    {
        return 'Group Visibility updated from "' . $this->oldVisibility->name . '" to "' . $this->newVisibility->name . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_visibility' => $this->oldVisibility->name,
            'new_visibility' => $this->newVisibility->name
        ];
    }

}
