<?php

namespace App\Modules\ExpertPanel\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use Illuminate\Broadcasting\InteractsWithSockets;

class SpecificationStatusUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ExpertPanel $application, public Specification $specification)
    {
        parent::__construct($application);
    }

    public function getLogEntry(): string
    {
        return 'Specification "' . $this->specification->name . '" status updated to "' . $this->specification->status . '"';
    }

    public function getProperties(): array
    {
        $props = [
            'specification_id' => $this->specification->cspec_id,
            'status' => $this->specification->status
        ];
        $step = $this->getStepFromStatus($this->specification->status);
        if ($step) {
            $props['step'] = $step;
        }

        return $props;
    }

    private function getStepFromStatus($status): ?int
    {
        if (preg_match('/classified/i', $status)) {
            return 2;
        }
        if (preg_match('/pilot/i', $status)) {
            return 3;
        }

        return null;
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
