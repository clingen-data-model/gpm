<?php

namespace App\Modules\Group\Events;

use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class MembershipDescriptionUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public ?string $description)
    {
    }

    public function getLogEntry(): string
    {
        return 'Membership description updated.';
    }

    public function getProperties(): array
    {
        return ['membership_description' => $this->description];
    }

}
