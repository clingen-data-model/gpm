<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Judgement;
use App\Modules\Group\Models\Submission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class ApplicationJudgementCreated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;
    public Submission $submission;

    public function __construct(public Judgement $judgement)
    {
        $this->submission = $judgement->submission;
        $this->group = $judgement->submission->group;
    }

    public function getProperties():array
    {
        return [
            'judgement_id' => $this->judgement->id,
            'step' => $this->group->expertPanel->current_step,
        ];
    }

    public function getLogEntry():string
    {
        $logEntry = $this->judgement->person->name.' made a decision on the submission: '.$this->judgement->decision;

        return $logEntry;
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
