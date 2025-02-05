<?php

namespace App\Modules\ExpertPanel\Events;

use App\Events\PublishableEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ExpertPanelAttributesUpdated extends ExpertPanelEvent implements PublishableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public array $attributes)
    {
        //
    }

    public function getLogEntry():string
    {
        $parts = [];
        foreach ($this->attributes as $key=>$value) {
            $parts[] = $key.' = '.$value;
        }

        return 'Attributes updated: '.implode('; ', $parts);
    }

    public function getProperties():array
    {
        return $this->attributes;
    }

    public function getEventType():string
    {
        return 'ep_info_updated';
    }

    public function getPublishableMessage(): array
    {
        return [
            "expert_panel" => [
                'id' => $this->application->group->uuid,
                'name' => $this->application->display_name,
                'type' => $this->application->group->type->name,
                'affiliation_id' => $this->application->affiliation_id,
                'long_base_name' => $this->application->long_base_name,
                'short_base_name' => $this->application->short_base_name,
                'hypothesis_group' => $this->application->hypothesis_group,
                'membership_description' => $this->application->membership_description,
                'scope_description' => $this->application->scope_description
            ]
        ];
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return parent::shouldPublish()
            && $this->application->definitionIsApproved;
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
