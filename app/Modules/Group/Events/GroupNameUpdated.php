<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GroupNameUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public String $newName, public String $oldName)
    {
    }

    public function getLogEntry(): string
    {
        return 'Name changed from "'.$this->oldName.'" to "'.$this->newName.'"';
    }

    public function getProperties(): ?array
    {
        return [
            'new_name' => $this->newName,
            'old_name' => $this->oldName
        ];
    }

}
