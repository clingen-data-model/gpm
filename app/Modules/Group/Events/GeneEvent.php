<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Events\GeneEventInterface;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

abstract class GeneEvent extends GroupEvent implements GeneEventInterface, PublishableApplicationEvent
{
    use IsPublishableApplicationEvent {
        getPublishableMessage as protected getBaseMessage;
    }

    public function getPublishableMessage(): array
    {
        $message = $this->getBaseMessage();
        $message['genes'] = [$this->mapGeneForMessage($this->gene)];

        return $message;
    }
}
