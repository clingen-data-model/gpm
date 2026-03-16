<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class PublicationAdded extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Publication $publication) { }

    public function getLogEntry(): string
    {
        return "Publication '" . $this->publication->meta['title'] . "' " . $this->publication->identifier . " added";
    }

    public function getProperties(): array
    {
        return $this->publication->toExchangePayload();
    }
}
