<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\PublishableApplicationEvent;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;
use Illuminate\Broadcasting\InteractsWithSockets;

class StepDateApprovedUpdated extends ExpertPanelEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use IsPublishableGroupEvent;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public int $step, public string $dateApproved)
    {
        parent::__construct($application);
    }

    public function getLogEntry():string
    {
        return 'Approval date updated to '.$this->dateApproved.' for step '.$this->step;
    }

    public function getProperties():array
    {
        return [
            'application_uuid' => $this->application->uuid,
            'step' => $this->step,
            'new_date_approved' => $this->dateApproved,
            'old_approval_date' => $this->application->getOriginal('step_'.$this->step.'_approval_date')
        ];
    }

}
