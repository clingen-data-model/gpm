<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

abstract class ExpertPanelEvent extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel  $application)
    {
        parent::__construct($application->group);
    }

    public function getLog():string
    {
        return 'applications';
    }

    public function hasSubject():bool
    {
        return true;
    }

    public function getSubject():Model
    {
        return $this->application->group;
    }

    public function getLogDate():Carbon
    {
        return Carbon::now();
    }

    public function getStep()
    {
        return $this->application->current_step;
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function getTopic(): string
    {
        return config('dx.topics.outgoing.gpm-general-events');
    }

    /**
     * For PublishableEvent interface that is applied to many sub-classes
     */
    public function shouldPublish(): bool
    {
        return $this->application->definitionIsApproved;
        
    }

    public function __get($key)
    {
        return $key == 'group' ? $this->application->group : null;
    }
}
