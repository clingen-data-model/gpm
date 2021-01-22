<?php

namespace App\Events;

use Exception;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;

class RecordableEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function getLogEntry():string
    {
        throw new Exception('Not implemented');
    }

    public function getLog():string
    {
        throw new Exception('Not implemented');
    }

    public function hasSubject():bool
    {
        throw new Exception('Not implemented');
    }

    public function getSubject():Model
    {
        throw new Exception('Not implemented');
    }

    public function getProperties():array
    {
        throw new Exception('Not implemented');
    }

}