<?php

namespace App\Events;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

abstract class RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function getLogEntry():string;

    abstract public function getLog():string;

    abstract public function hasSubject():bool;

    abstract public function getSubject():Model;

    abstract public function getProperties():?array;

    abstract public function getLogDate():Carbon;

}