<?php

namespace App\Modules\ExpertPanel\Events;

use App\Events\RecordableEvent;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * @property App\Modules\Group\Models\Group $group
 */
abstract class ExpertPanelEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application)
    {
    }

    public function getLog(): string
    {
        return 'applications';
    }

    public function hasSubject(): bool
    {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->application->group;
    }

    public function getLogDate(): Carbon
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
        return $this->group->isEp;
    }

    public function __get($key)
    {
        return $key == 'group' ? $this->application->group : null;
    }
}
