<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Submission;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ApplicationRevisionsRequested extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public function __construct(public Submission $submission, public ?string $comments = null)
    {
        $this->group = $submission->group;
    }

    public function getProperties():array
    {
        return [
            'submission_id' => $this->submission->id,
            'step' => $this->submission->group->expertPanel->current_step,
        ];
    }

    public function getLogEntry():string
    {
        $logEntry = 'Revisions requested for step '.$this->group->expertPanel->current_step;

        if ($this->comments) {
            $logEntry .= ":\n".$this->comments;
        }

        return $logEntry;
    }

}
