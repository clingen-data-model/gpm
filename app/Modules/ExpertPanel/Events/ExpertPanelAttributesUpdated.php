<?php

namespace App\Modules\ExpertPanel\Events;

use App\Events\PublishableEvent;
use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ExpertPanelAttributesUpdated extends ExpertPanelEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public array $attributes)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        $parts = [];
        foreach ($this->attributes as $key => $value) {
            $parts[] = $key . ' = ' . $value;
        }

        return 'Attributes updated: ' . implode('; ', $parts);
    }

    public function getProperties(): array
    {
        return $this->attributes;
    }

    public function getEventType(): string
    {
        return 'ep_info_updated';
    }

}
