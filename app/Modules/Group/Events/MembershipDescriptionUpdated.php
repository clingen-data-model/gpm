<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MembershipDescriptionUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public ?string $description)
    {
        //
    }

    public function getLogEntry(): string
    {
        return 'Membership description updated.';
    }

    public function getProperties(): array
    {
        return ['membership_description' => $this->description];
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
