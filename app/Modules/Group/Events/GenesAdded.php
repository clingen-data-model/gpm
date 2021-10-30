<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GenesAdded extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public array $genes)
    {
    }

    public function getLogEntry(): string
    {
        return 'Genes added to '.$this->group->name.' scope.';
    }
    
    public function getProperties(): ?array
    {
        return ['genes' => $this->genes];
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
