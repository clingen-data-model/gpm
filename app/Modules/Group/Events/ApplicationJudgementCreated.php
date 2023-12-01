<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Judgement;
use App\Modules\Group\Models\Submission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApplicationJudgementCreated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Group $group;

    public Submission $submission;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Judgement $judgement)
    {
        $this->submission = $judgement->submission;
        $this->group = $judgement->submission->group;
    }

    public function getProperties(): array
    {
        return [
            'judgement_id' => $this->judgement->id,
            'step' => $this->group->expertPanel->current_step,
        ];
    }

    public function getLogEntry(): string
    {
        $logEntry = $this->judgement->person->name.' made a decision on the submission: '.$this->judgement->decision;

        return $logEntry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
