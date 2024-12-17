<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ExpertPanelAttributesUpdated extends ExpertPanelEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use IsPublishableApplicationEvent {
        getPublishableMessage as protected getBaseMessage;
    }

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $expertPanel, public array $attributes)
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
        // TODO: double-check that the "Name" is what the website team wants
        $message = $this->getBaseMessage();
        // TODO: check if these fields are still used/needed, since they don't necessarily show up elsewhere, or maybe should be in trait?
        $message['membership_description'] = $this->expertPanel->membership_description;
        // $message['hypothesis_group'] = $this->expertPanel->hypothesis_group; // currently unused?
        return $message;
        /*
        return [
            "expert_panel" => [
                'id' => $this->expertPanel->group->uuid,
                'name' => $this->expertPanel->display_name,
                'type' => $this->expertPanel->group->type->name,
                'affiliation_id' => $this->expertPanel->affiliation_id,
                'long_base_name' => $this->expertPanel->long_base_name,
                'short_base_name' => $this->expertPanel->short_base_name,
                'hypothesis_group' => $this->expertPanel->hypothesis_group,
                'membership_description' => $this->expertPanel->membership_description,
                'scope_description' => $this->expertPanel->scope_description
            ]
        ];
        */
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return parent::shouldPublish()
            && $this->expertPanel->definitionIsApproved;
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
