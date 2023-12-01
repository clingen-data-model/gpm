<?php

namespace Tests\Dummies;

use App\DataExchange\Contracts\MessageProcessor;
use App\DataExchange\DxMessage;
use Lorisleiva\Actions\Concerns\AsJob;

class FakeMessageProcessor implements MessageProcessor
{
    use AsJob;

    public static $messages = [];

    public function handle(DxMessage $message): DxMessage
    {
        static::$messages[] = $message;

        return $message;
    }
}
