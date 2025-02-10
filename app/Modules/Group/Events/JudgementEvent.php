<?php

namespace App\Modules\Group\Events;

use App\Events\AbstractEvent;
use Illuminate\Queue\SerializesModels;
use App\Modules\Group\Models\Judgement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class JudgementEvent extends AbstractEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Judgement $judgement)
    {
    }

    public function shouldPublish(): bool
    {
        return false;
    }

}
