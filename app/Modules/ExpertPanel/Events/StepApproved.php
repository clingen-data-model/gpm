<?php

namespace App\Modules\ExpertPanel\Events;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\PublishableExpertPanelEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableExpertPanelEvent;

class StepApproved extends ExpertPanelEvent implements PublishableExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use IsPublishableExpertPanelEvent {
        getPublishableMessage as protected getBaseMessage;
    }

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public int $step, public Carbon $dateApproved)
    {
        //
    }

    public function getProperties():array
    {
        return [
            'date_approved' => $this->dateApproved
        ];
    }

    public function getLogEntry():string
    {
        return 'Step '.$this->step.' approved';
    }

    public function getLogDate():Carbon
    {
        return $this->dateApproved;
    }

    public function getStep()
    {
        return max(($this->application->current_step - 1), 1);
    }

    public function getEventType(): string
    {
        switch ($this->step) {
            case 1:
                return 'ep_definition_approved';
            case 2:
                return 'vcep_draft_specifications_approved';
            case 3:
                return 'vcep_pilot_approved';
            case 4:
                return 'ep_final_approval';
            default:
                throw new Exception('Invalid step approved expected 1-4, received '.$this->step);
        }
    }

    public function getPublishableMessage(): array
    {
        $message = $this->getBaseMessage();
        if ($this->step == 1) {
            $message['members'] = $this->group->members
                                    ->map(function ($member) {
                                        return $this->mapMemberForMessage($member);
                                    })
                                    ->toArray();

            $message['scope']['statement'] = $this->group->expertPanel->scope_description;
            $message['scope']['genes'] = $this->group->expertPanel->genes
                                            ->map(function ($gene) {
                                                return $this->mapGeneForMessage($gene);
                                            })
                                            ->toArray();
        }

        return $message;

    }

}