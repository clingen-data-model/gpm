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
        $submitterName = Auth::user() ? Auth::user()->name : 'system';
        return "Publication added by " . $submitterName . ".";
    }

    public function getProperties(): array
    {
        return [
            'publication_id' => $this->publication->uuid,
            'source' => $this->publication->source,
            'identifier' => $this->publication->identifier,
            'pub_type' => $this->publication->pub_type,
            'published_at' => optional($this->publication->published_at)->toDateString(),            
            'meta_keys' => $this->publication->meta ?? [],
            'has_meta' => ! empty($this->publication->meta),
        ];
    }
}
