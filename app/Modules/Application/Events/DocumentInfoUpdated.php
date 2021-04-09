<?php

namespace App\Modules\Application\Events;

use App\Models\Document;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\Application\Models\Application;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DocumentInfoUpdated extends ApplicationEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Application $application, public Document $document)
    {
    }

    public function getLogEntry():string
    {
        return 'Document '.$this->document->type->name.' version '.$this->document->version.' info updated';
    }
    
    public function getProperties():array
    {
        return [
            'date_received' => $this->document->date_received,
            'date_reviewed' => $this->document->date_reviewed
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
