<?php

namespace App\DataExchange\Exceptions;

class UnsupportedIncomingMessage extends StreamingServiceException
{
    public function __construct($message)
    {
        parent::__construct('An unsupported event type with key '.($message->key ?? 'undefined').' was received on topic '.$message->topic.' with payload '.json_encode($message->payload), 401);
    }
}
