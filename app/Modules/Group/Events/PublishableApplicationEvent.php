<?php

namespace App\Modules\Group\Events;

use App\Events\Event;
use Illuminate\Support\Carbon;

interface PublishableApplicationEvent extends Event
{
    public function getLogDate(): Carbon;
    public function getEventType(): string;
    public function getPublishableMessage(): array;
}
