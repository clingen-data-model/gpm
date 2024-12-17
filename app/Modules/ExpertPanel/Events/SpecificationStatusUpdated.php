<?php

namespace App\Modules\ExpertPanel\Events;

use App\Modules\Group\Models\Group;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use App\Modules\ExpertPanel\Models\ExpertPanel;
use App\Modules\ExpertPanel\Models\Specification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class SpecificationStatusUpdated extends ExpertPanelEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(public ExpertPanel $expertPanel, public Specification $specification )
    {
    }

    public function getLogEntry(): string
    {
        return 'Specification "'.$this->specification->name.'" status updated to "'.$this->specification->status.'"';
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



    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

}
