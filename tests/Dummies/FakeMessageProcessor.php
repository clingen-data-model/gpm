<?php

namespace Tests\Dummies;

use App\DataExchange\DxMessage;
use App\DataExchange\Contracts\MessageProcessor;

class FakeMessageProcessor implements MessageProcessor
{
    public $messages = [];

    public function handle(DxMessage $message): DxMessage
    {
        $this->messages[] = $message;

        return $message;
    }
}
