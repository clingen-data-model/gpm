<?php

namespace App\Modules\Group\Events;

use App\Events\PublishableEvent;

interface PublishableApplicationEvent extends PublishableEvent
{
    public function mapGeneForMessage($gene): array;
    public function mapMemberForMessage($member): array;    
}
