<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Support\Carbon;

class ExpertPanelDeleted extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ExpertPanel  $application
    ) {
        parent::__construct($application);
    }

    public function getProperties():array
    {
        return [];
    }

    public function getLogEntry():string
    {
        return 'Application deleted';
    }

    public function shouldPublish(): bool
    {
        // NOTE: have to special-case this because the group<->expertPanel relationship may be gone by the time this is fired
        return $this->application->definitionIsApproved;
    }
}
