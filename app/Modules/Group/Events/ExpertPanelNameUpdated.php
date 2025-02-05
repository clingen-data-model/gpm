<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ExpertPanelNameUpdated extends GroupEvent implements PublishableApplicationEvent
{
    use Dispatchable,
        InteractsWithSockets,
        SerializesModels,
        IsPublishableApplicationEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public Group $group,
        public ?string $longName,
        public ?string $shortName,
        public ?string $oldLong,
        public ?string $oldShort
    ) {
    }

    public function getLogEntry(): string
    {
        return 'EP name updated.';
    }

    public function getProperties(): ?array
    {
        $properties = [];
        if ($this->oldLong != $this->longName) {
            $properties['old_long_base_name'] = $this->oldLong;
            $properties['new_long_base_name'] = $this->longName;
        }
        if ($this->oldShort != $this->shortName) {
            $properties['old_short_base_name'] = $this->oldShort;
            $properties['new_short_base_name'] = $this->shortName;
        }

        return count($properties) > 0 ? $properties : null;
    }

    public function getEventType(): string
    {
        return 'ep_info_updated';
    }

    public function shouldPublish(): bool
    {
        return $this->group->expertPanel->definitionIsApproved;
    }
}
