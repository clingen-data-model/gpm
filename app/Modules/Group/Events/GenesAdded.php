<?php
namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GeneEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use Illuminate\Broadcasting\InteractsWithSockets;

class GenesAdded extends GeneEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Collection $genes)
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

    public function getEventType(): string
    {
        return 'gene_added';
    }

    public function getPublishableMessage(): array
    {
        $message = $this->getBaseMessage();
        $message['genes'] = $this->genes->map(fn ($gene) => $this->mapGeneForMessage($gene))->toArray();
        return $message;
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

    public function __get($key)
    {
        if ($key == 'gene') {
            return $this->genes;
        }
    }
}
