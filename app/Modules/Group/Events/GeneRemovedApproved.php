<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\PublishableApplicationEvent;

class GeneRemovedApproved extends GroupEvent implements GeneEvent //, PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Gene $gene)
    {
    }

    public function getLogEntry(): string
    {
        return 'Gene '.$this->gene->gene_symbol.' removed from '.$this->group->name.' scope.';
    }
    
    public function getProperties(): ?array
    {
        return $this->gene->toArray(0);
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
