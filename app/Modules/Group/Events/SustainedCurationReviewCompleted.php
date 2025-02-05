<?php

namespace App\Modules\Group\Events;

use Illuminate\Support\Collection;
use App\Modules\Group\Models\Group;
use Illuminate\Support\Facades\Auth;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class SustainedCurationReviewCompleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Collection $tasks)
    {
    }

    public function getLogEntry(): string
    {
        $submitterName = Auth::user() ? Auth::user()->name : 'system';
        return 'Sustained curation info was reviewed and/or updated by ' . $submitterName . '.';
    }

    public function getProperties(): ?array
    {
        return [
            'task_ids' => $this->tasks->pluck('id')
        ];
    }

}
