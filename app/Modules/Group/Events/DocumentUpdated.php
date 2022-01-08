<?php

namespace App\Modules\Group\Events;

use App\Models\Document;
use App\Modules\Group\Models\Group;
use App\Modules\Group\Events\GroupEvent;

class DocumentUpdated extends GroupEvent
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
        return 'Document '.$this->document->filename.' info updated';
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
