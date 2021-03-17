<?php

namespace App\Modules\Application\Events;

use App\Models\Document;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use App\Modules\Application\Models\Application;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Carbon;

class DocumentReviewed extends ApplicationEvent
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
        return 'Reviewed '.$this->document->type->long_name.' version '.$this->document->version.'.';
    }
    
    public function getProperties():array
    {
        return ['document' => $this->document->toArray()];
    }
    
    public function getLogDate():Carbon
    {
        return $this->document->date_reviewed;
    }
    

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    // public function broadcastOn()
    // {
    //     return new PrivateChannel('channel-name');
    // }
}
