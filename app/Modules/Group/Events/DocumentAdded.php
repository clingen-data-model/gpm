<?php

namespace App\Modules\Group\Events;

use App\Models\Document;
use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Events\GroupEvent;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DocumentAdded extends GroupEvent
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public Document $document)
    {
    }

    public function getLogEntry(): string
    {
        return 'Document '.$this->document->filename.' uploaded.';
    }

    public function getProperties(): array
    {
        return [
            'document' => $this->document->toArray()
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
