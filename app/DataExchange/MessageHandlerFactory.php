<?php

namespace App\DataExchange;

use Illuminate\Support\Str;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;

class MessageHandlerFactory
{
    public function make(IncomingStreamMessage $message)
    {
        $handlerClass = null;

        $class = $this->buildHandlerClassName($message);
        if (class_exists($class)) {
            return new $class();
        }

        throw new UnsupportedIncomingMessage($message);
    }
    

    private function buildHandlerClassName($message)
    {
        $namespace = '\\App\\DataExchange\\Actions\\';
        $className = Str::studly($message->event.'Handler');

        return $namespace.'\\'.$className;
    }
}
