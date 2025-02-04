<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\PublishableExpertPanelEvent;
use App\Modules\Group\Events\Traits\IsPublishableExpertPanelEvent;
use Illuminate\Broadcasting\InteractsWithSockets;

class StepDateApprovedUpdated extends ExpertPanelEvent implements PublishableExpertPanelEvent
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
    public function __construct(public ExpertPanel  $expertPanel, public int $step, public string $dateApproved)
    {
    }

    public function getLogEntry():string
    {
        return 'Approval date updated to '.$this->dateApproved.' for step '.$this->step;
    }

    public function getProperties():array
    {
        return [
            'application_uuid' => $this->expertPanel->uuid,
            'step' => $this->step,
            'new_date_approved' => $this->dateApproved,
            'old_approval_date' => $this->expertPanel->getOriginal('step_'.$this->step.'_approval_date')
        ];
    }

    public function getEventType(): string
    {
        return 'step_date_approved_updated';
    }

}
