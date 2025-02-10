<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class ApplicationSentToChairs extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Group $group,
        public Collection $comments,
        public ?string $additionalComments = null
    ) {}

    public function getProperties(): array
    {
        return [
            'step' => $this->group->expertPanel->current_step,
            'additional_comments' => $this->additionalComments,
            'comment_ids' => $this->comments->pluck('id')
        ];
    }

    public function getLogEntry(): string
    {
        $logEntry = 'Sent to CDWG OC Chairs: Step ' . $this->group->expertPanel->current_step . ' application';

        return $logEntry;
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}
