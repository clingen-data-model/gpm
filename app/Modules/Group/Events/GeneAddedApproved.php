<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GeneEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\Group\Events\GeneEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\PublishableApplicationEvent;

class GeneAddedApproved extends GeneEvent implements GeneEventInterface //, PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public array $gene)
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
