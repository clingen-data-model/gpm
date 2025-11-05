<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

// FIXME: shoule be an ExpertPanelEvent

class SubmissionApproved extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public function __construct(public Submission $submission)
    {
        $this->group = $submission->group;
    }

    public function getProperties(): array
    {
        return [
            'submission' => $this->submission->toArray()
        ];
    }

    public function getLogEntry(): string
    {
        return 'Step ' . $this->getStep() . ' approved';
    }

    // override to include members and genes
    public function getPublishableMessage(): array {
        $message = $this->getProperties() ?? [];
        $message['group'] = $this->mapGroupForMessage( true, true);
        return $message;
    }

    public function getStep()
    {
        return max(($this->group->expertPanel->current_step - 1), 1);
    }

}
