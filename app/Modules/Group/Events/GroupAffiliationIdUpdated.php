<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

// TO DO CGSP-1023 NEED TO RENAME THIS TO GROUP
class GroupAffiliationIdUpdated extends GroupEvent implements PublishableApplicationEvent
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
        return 'Group Affiliation ID set to ' . $this->affiliationId . '.';
    }

    public function getProperties(): ?array
    {
        return [
            'affiliation_id' => $this->affiliationId
        ];
    }

    public function getEventType(): string
    {
        return 'group_info_updated';
    }
}
