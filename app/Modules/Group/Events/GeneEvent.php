<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Events\GeneEventInterface;
use App\Modules\Group\Events\Traits\IsPublishableExpertPanelEvent;

abstract class GeneEvent extends GroupEvent implements GeneEventInterface, PublishableExpertPanelEvent
{
    use IsPublishableExpertPanelEvent {
        getPublishableMessage as protected getBaseMessage;
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return parent::shouldPublish()
            && $this->group->expertPanel->definitionIsApproved;
    }
}
