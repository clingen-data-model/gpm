<?php
namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GeneEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class GenesAdded extends GeneEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

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
        $message = parent::getPublishableMessage();
        $message['genes'] = $this->genes->map(fn ($gene) => $this->mapGeneForMessage($gene))->toArray();
        return $message;
    }


    public function __get($key)
    {
        if ($key == 'gene') {
            return $this->genes;
        }
    }
}
