<?php

namespace App\Modules\ExpertPanel\Events;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\PublishableApplicationEvent;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\Group\Events\Traits\IsPublishableGroupEvent;

class StepApproved extends ExpertPanelEvent implements PublishableApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use IsPublishableGroupEvent;

    public function __construct(public ExpertPanel  $application, public int $step, public Carbon $dateApproved)
    {
        parent::__construct($application);
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
        $groupType = $this->application->type->name;
        switch ($this->step) {
            case 1:
                if ($isGcep) {
                    return $groupType . '_final_approval';
                }
                return $groupType . '_definition_approval';
                break;
            case 2:
                return $groupType . '_draft_specification_approval';
            case 3:
                return $groupType . '_pilot_approval';
            case 4:
                return $groupType . '_final_approval';
            default:
                throw new Exception('Invalid step approved expected 1-4, received '.$this->step);
        }
        return 'unexpected_step';
    }

    public function getPublishableMessage(): array
    {
        $message = parent::getPublishableMessage();
        if ($this->step == 1) {

            $this->group->loadMissing([
                'members.person.latestCocAttestation',
            ]);

            $message['members'] = $this->group->members
                                    ->map(function ($member) {
                                        return $this->mapMemberForMessage($member, false);
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
