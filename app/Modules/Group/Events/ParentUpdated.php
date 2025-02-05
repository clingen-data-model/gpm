<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ParentUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public ?Group $parent, public ?Group $oldParent)
    {
    }

    public function getLogEntry(): string
    {
        $oldParentName = $this->oldParent ? $this->oldParent->name : 'none';
        $newParentName = $this->parent ? $this->parent->name : 'none';
        return 'Parent changed from ' . $oldParentName . ' to ' . $newParentName . '.';
    }

    public function getProperties(): ?array
    {
        return [
            'new_parent' => $this->parent,
            'old_parent' => $this->oldParent,
        ];
    }

}
