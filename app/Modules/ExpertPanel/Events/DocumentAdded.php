<?php

namespace App\Modules\ExpertPanel\Events;

use App\Models\Document;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\ExpertPanel\Events\ApplicationEvent;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Carbon;

class DocumentAdded extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public ExpertPanel $application,
        public Document $document
    ) {
        //
    }

    public function getLogEntry():string
    {
        return 'Added version '.$this->document->version.' of '.$this->document->type->long_name.'.';
    }
    

    public function getProperties(): array
    {
        return ['document' => $this->document->toArray()];
    }

    public function getLogDate():Carbon
    {
        return $this->document->date_received;
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
