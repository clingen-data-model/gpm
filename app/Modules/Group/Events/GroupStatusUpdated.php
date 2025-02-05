<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\GroupStatus;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GroupStatusUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public GroupStatus $newStatus, public GroupStatus $oldStatus)
    {
    }

    public function getLogEntry(): string
    {
        return 'Status updated from "' . $this->oldStatus->name . '" to "' . $this->newStatus->name . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus
        ];
    }

}
