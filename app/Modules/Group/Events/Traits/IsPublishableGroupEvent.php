<?php

namespace App\Modules\Group\Events\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Modules\Group\Models\Group;

trait IsPublishableGroupEvent
{
    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }

    public function getEventType(): string
    {
        return Str::snake(self::class);
    }

    public function getPublishableMessage(): array
    {
        return ['group' => $this->group->representationForDataExchange()];
    }
}
