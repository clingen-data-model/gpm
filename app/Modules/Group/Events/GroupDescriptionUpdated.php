<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GroupDescriptionUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public ?string $newDescription, public ?string $oldDescription)
    {
    }

    public function getLogEntry(): string
    {
        return 'Description updated from "' . ($this->oldDescription ?? 'NULL') . '" to "' .
        ($this->newDescription ?? 'NULL') . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_description' => $this->oldDescription,
            'new_description' => $this->newDescription
        ];
    }

}
