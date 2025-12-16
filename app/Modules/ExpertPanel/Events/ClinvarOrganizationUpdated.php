<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;

class ClinvarOrganizationUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public ?string $newClinvarID, public ?string $oldClinvarID) {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Clinvar Organization ID updated from "' . $this->oldClinvarID . '" to "' . $this->newClinvarID . '"';
    }

    public function getProperties(): ?array
    {
        return [
            'old_clinvar_org_id' => $this->oldClinvarID,
            'new_clinvar_org_id' => $this->newClinvarID
        ];
    }
}