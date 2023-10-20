<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

abstract class RecordableEvent implements Event
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function getLogEntry(): string;

    public function getActivityType(): string
    {
        $type = Str::kebab(array_slice(explode('\\', $this::class), -1, 1)[0]);

        return $type;
    }

    abstract public function getLog(): string;

    abstract public function hasSubject(): bool;

    abstract public function getSubject(): Model;

    abstract public function getProperties(): ?array;

    abstract public function getLogDate(): Carbon;
}
