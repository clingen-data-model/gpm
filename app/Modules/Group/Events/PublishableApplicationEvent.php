<?php

namespace App\Modules\Group\Events;

use Carbon\Carbon;
use App\Events\Event;

interface PublishableApplicationEvent extends Event
{
    public function getLogDate(): Carbon;
    public function getEventType(): string;
}
