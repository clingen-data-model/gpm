<?php

namespace App\Modules\Group\Events;

use App\Modules\ExpertPanel\Models\EvidenceSummary;
use App\Modules\Group\Models\Group;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class EvidenceSummaryAdded extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public EvidenceSummary $evidenceSummary) {}

    public function getLogEntry(): string
    {
        return 'Evidence summary added to ' . $this->group->name . '.';
    }

    public function getProperties(): ?array
    {
        return ['evidence_summary' => $this->evidenceSummary];
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}
