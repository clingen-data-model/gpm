<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupNameUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public string $newName, public string $oldName)
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
            'old_name' => $this->oldName,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
