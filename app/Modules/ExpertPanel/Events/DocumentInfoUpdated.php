<?php

namespace App\Modules\ExpertPanel\Events;

use App\Models\Document;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DocumentInfoUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $expertPanel, public Document $document)
    {
    }

    public function getLogEntry():string
    {
        return 'Document '.$this->document->type->name.' version '.$this->document->version.' info updated';
    }
    
    public function getProperties():array
    {
        return [
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
