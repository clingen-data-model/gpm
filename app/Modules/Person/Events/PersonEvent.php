<?php

namespace App\Modules\Person\Events;

use App\Events\RecordableEvent;
use App\Modules\Person\Models\Person;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

abstract class PersonEvent extends RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Person $person)
    {
    }

    public function getLog(): string
    {
        return 'people';
    }

    public function hasSubject(): bool
    {
        return true;
    }

    public function getSubject(): Model
    {
        return $this->person;
    }

    public function getLogDate(): Carbon
    {
        return Carbon::now();
    }
}
