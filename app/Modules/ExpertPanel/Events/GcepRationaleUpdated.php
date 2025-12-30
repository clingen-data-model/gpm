<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use Illuminate\Support\Facades\Auth;

class GcepRationaleUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $application, public ?string $gcep_rationale)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        $updatedBy = Auth::user() ? Auth::user()->name : 'system';
        return 'GCEP Rationale updated by ' . $updatedBy . ', new rationale: ' . $this->gcep_rationale;
    }

    public function getProperties(): array
    {
        return ['gcep_rationale' => $this->gcep_rationale];
    }
}
