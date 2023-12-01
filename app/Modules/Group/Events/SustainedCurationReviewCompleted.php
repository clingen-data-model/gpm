<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SustainedCurationReviewCompleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Collection $tasks)
    {
    }

    public function getLogEntry(): string
    {
        $submitterName = Auth::user() ? Auth::user()->name : 'system';

        return 'Sustained curation info was reviewed and/or updated by '.$submitterName.'.';
    }

    public function getProperties(): ?array
    {
        return [
            'task_ids' => $this->tasks->pluck('id'),
        ];
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
