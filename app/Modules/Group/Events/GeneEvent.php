<?php
namespace App\Modules\Group\Events;

use App\Modules\Group\Events\GroupEvent;
use App\Modules\Group\Events\GeneEventInterface;
use App\Modules\Group\Events\Traits\IsPublishableApplicationEvent;

// FIXME: this is actually an ExpertPanelEvent, since only EPs keep track of genes in scope...

abstract class GeneEvent extends GroupEvent implements GeneEventInterface, PublishableApplicationEvent
{
    use IsPublishableApplicationEvent;

}
