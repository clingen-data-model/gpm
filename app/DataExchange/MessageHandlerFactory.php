<?php

namespace App\DataExchange;

use Illuminate\Support\Str;
use App\DataExchange\Actions\ErrorMessageHandler;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;

class MessageHandlerFactory
{
    public function make(IncomingStreamMessage $message)
    {
        if ($message->error_code != 0) {
            return app()->make(ErrorMessageHandler::class);
        }

        $class = $this->buildHandlerClassName($message);
        if (class_exists($class)) {
            return app()->make($class);
        }

        throw new UnsupportedIncomingMessage($message);
    }

    private function buildHandlerClassName($message)
    {
        $namespace = '\\App\\DataExchange\\Actions';
        $className = Str::studly($message->payload->cspecDoc->status->event.'Processor');

        return $namespace.'\\'.$className;
    }
}
