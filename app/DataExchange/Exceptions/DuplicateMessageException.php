<?php

namespace App\DataExchange\Exceptions;

class DuplicateMessageException extends StreamingServiceException
{
    public function __construct(private $message, private $code, private array $additionalLogData)
    {
    }

    public function getAdditionalLogData(): array
    {
        return $this->additionalLogData;
    }


}
