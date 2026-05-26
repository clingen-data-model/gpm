<?php

namespace App\Modules\Group\Events;

use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Group;

class WebTextUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public ?string $oldExcerpt, public ?string $newExcerpt) {}

    public function getEventType(): string
    {
        return "website_excerpt_updated";
    }

    public function getLogEntry(): string
    {
        return 'Excerpt updated from "' . ($this->oldExcerpt ?? 'NULL') . '" to "' . ($this->newExcerpt ?? 'NULL') . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_excerpt' => $this->oldExcerpt,
            'new_excerpt' => $this->newExcerpt
        ];
    }
}
