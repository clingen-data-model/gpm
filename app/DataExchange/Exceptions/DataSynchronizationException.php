<?php

namespace App\DataExchange\Exceptions;

class DataSynchronizationException extends StreamingServiceException
{
    public function __construct($message)
    {
        parent::__construct($message, 401);
    }
}
