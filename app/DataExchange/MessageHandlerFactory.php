<?php

namespace App\DataExchange;

use App\DataExchange\Actions\CspecDataSyncProcessor;
use Illuminate\Support\Str;
use App\DataExchange\Actions\ErrorMessageHandler;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\UnsupportedIncomingMessage;

class MessageHandlerFactory
{
    public function make(IncomingStreamMessage $message)
    {
        if ($this->isErrorMessage($message)) {
            return app()->make(ErrorMessageHandler::class);
        }

        return $this->resolveHandlerClass($message);

    }

    private function resolveHandlerClass($message): Object
    {
        $class = $this->buildHandlerClassName($message);

        if (class_exists($class)) {
            return app()->make($class);
        }

        if ($message->topic == config('dx.topics.incoming.cspec-general')) {
            return app()->make(CspecDataSyncProcessor::class);
        }

        throw new UnsupportedIncomingMessage($message);
    }


    private function buildHandlerClassName($message)
    {
        $namespace = '\\App\\DataExchange\\Actions';
        try {
            $event = $message->payload->cspecDoc->status->event;
        } catch (\ErrorException $ex) {
            throw new UnsupportedIncomingMessage($message);
        }
        $className = Str::studly($event.'Processor');

        return $namespace.'\\'.$className;
    }

    private function isErrorMessage($message): bool
    {
        return $message->error_code != 0;
    }

}
