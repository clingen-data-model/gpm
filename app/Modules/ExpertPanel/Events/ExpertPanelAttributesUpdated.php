<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\Group\Events\PublishableExpertPanelEvent;
use App\Modules\Group\Events\Traits\IsPublishableExpertPanelEvent;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ExpertPanelAttributesUpdated extends GroupEvent implements PublishableExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableExpertPanelEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $expertPanel, public array $attributes)
    {
        //
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

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        // FIXME: rethink when refactoring "expert panel" predicate. Should probably have separate "should publish" predicate
        return parent::shouldPublish() && (($this->expertPanel === null) || $this->expertPanel->isApproved);
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
