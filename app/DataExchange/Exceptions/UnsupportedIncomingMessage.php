<?php

namespace App\DataExchange\Exceptions;

class UnsupportedIncomingMessage extends StreamingServiceException
{
    public function __construct($message)
    {
        parent::__construct('An unsupported message type with key '.$message->key.' was received on topic '.$message->topic, 401);
    }
}
