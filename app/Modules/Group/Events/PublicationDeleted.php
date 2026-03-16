<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use App\Modules\Group\Models\Publication;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class PublicationDeleted extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public Publication $publication) {}

    public function getLogEntry(): string
    {
        return "Publication '" . $this->publication->meta['title'] . "' " . $this->publication->identifier . " deleted";
    }

    public function getProperties(): array
    {
        return [
            'publication_id' => $this->publication->uuid,
            'source' => $this->publication->source,
            'identifier' => $this->publication->identifier,
        ];
    }
}
