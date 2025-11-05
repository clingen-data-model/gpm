<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

class ExpertPanelAffiliationIdUpdated extends GroupEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels, IsPublishableGroupEvent;

    public function __construct(
        public Group $group,
        public $affiliationId,
    ) {
        parent::__construct($group);
    }

    public function getLogEntry(): string
    {
        return 'EP affiliation_id set to ' . $this->affiliationId . '.';
    }

    public function getProperties(): ?array
    {
        $properties = [
            'affiliation_id' => $this->affiliationId
        ];
        return count($properties) > 0 ? $properties : null;
    }

    public function getEventType(): string
    {
        return 'ep_info_updated';
    }
}
