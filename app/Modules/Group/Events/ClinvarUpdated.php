<?php

namespace App\Modules\Group\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ClinvarUpdated extends GroupEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Group $group, public ?string $newClinvarID, public ?string $oldClinvarID) {
    }

    public function getLogEntry(): string
    {
        return 'Clinvar ID updated from "' . $this->oldClinvarID . '" to "' . $this->newClinvarID . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_clinvar_id' => $this->oldClinvarID,
            'new_clinvar_id' => $this->newClinvarID
        ];
    }
}
