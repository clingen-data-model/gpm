<?php

namespace App\Modules\Group\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Group;

class CaptionIconUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group) {}

    public function getSchemaVersion(): string
    {
        return '2.0.0';
    }

    public function getLogEntry(): string
    {
        return "Caption/Icon updated for {$this->group->name}";
    }

    public function getProperties(): ?array
    {
        return [
            'caption'   => $this->group->caption,
            'icon_url'  => $this->group->icon_url,
        ];
    }
}
