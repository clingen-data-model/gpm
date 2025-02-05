<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ApplicationStepSubmitted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Submission $submission)
    {
    }

    public function getLogEntry(): string
    {
        $submitterName = $this->submission->submitter ? $this->submission->submitter->name : 'system';
        return $this->submission->type->name.' application submitted for approval by '.$submitterName.'.';
    }

    public function getProperties(): ?array
    {
        return [
            'submission' => $this->submission->toArray(),
            'date_submitted' => $this->submission->created_at,
            'step' => $this->submission->group->expertPanel->current_step
        ];
    }

}
