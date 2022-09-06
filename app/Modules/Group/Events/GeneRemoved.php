<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GeneEvent;
use App\Modules\ExpertPanel\Models\Gene;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\Group\Events\GeneEventInterface;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

class GeneRemoved extends GeneEvent implements PublishableApplicationEvent, GeneEventInterface
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

    public function getEventType(): string
    {
        return 'gene_removed';
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
