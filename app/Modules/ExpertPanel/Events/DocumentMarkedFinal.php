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

class DocumentMarkedFinal extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel  $application, public Document $document)
    {
        parent::__construct($application);
    }

    public function getLogEntry():string
    {
        return $this->document->type->name.' version '.$this->document->version.' marked final.';
    }

    public function getProperties():array
    {
        return [
            'document_uuid' => $this->document->uuid
        ];
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
