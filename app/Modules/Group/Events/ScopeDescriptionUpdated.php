<?php

namespace App\Modules\Group\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

// FIXME: only ExpertPanels have scope descriptions, so should be ExpertPanelEvent...
class ScopeDescriptionUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public Group $group, public ?string $description)
    {
        //
    }

    public function getLogEntry(): string
    {
        return 'Scope description updated.';
    }

    public function getProperties(): array
    {
        return ['scope_description' => $this->description];
    }

    public function shouldPublish(): bool
    {
        return false;
    }
}
