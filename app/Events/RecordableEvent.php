<?php

namespace App\Events;

use App\Events\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

abstract class RecordableEvent extends Event
{
    abstract public function getLogEntry():string;

    public function getActivityType():string
    {
        $type = Str::kebab(array_slice(explode('\\', get_class($this)), -1, 1)[0]);
        return $type;
    }

    abstract public function getLog():string;

    abstract public function hasSubject():bool;

    abstract public function getSubject():Model;

    abstract public function getProperties():?array;

    abstract public function getLogDate():Carbon;
}
