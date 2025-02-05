<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GeneEvent;
use App\Modules\ExpertPanel\Models\Gene;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GeneRemoved extends GeneEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Gene $gene)
    {
    }

    public function getLogEntry(): string
    {
        return 'Gene ' . $this->gene->gene_symbol . ' removed from ' . $this->group->name . ' scope.';
    }

    public function getProperties(): ?array
    {
        return $this->gene->toArray(0);
    }

    public function getEventType(): string
    {
        return 'gene_removed';
    }

    public function getPublishableMessage(): array
    {
        $message = parent::getPublishableMessage();
        $message['genes'] = [$this->mapGeneForMessage($this->gene)];

        return $message;
    }

}
