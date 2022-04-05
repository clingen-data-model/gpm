<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\StreamingServiceException;

class ErrorMessageHandler
{
    protected $knownErrors = [
        \RD_KAFKA_RESP_ERR__PARTITION_EOF,
        \RD_KAFKA_RESP_ERR__TIMED_OUT
    ];

    public function handle(IncomingStreamMessage $message)
    {
        if (!in_array($message->err, $this->knownErrors)) {
            $errMsg = ($message->payload) ? $message->payload : 'An unknown error occurred while consuming Kafka messages';
            throw new StreamingServiceException($errMsg, $message->err);
        }
    }
}
