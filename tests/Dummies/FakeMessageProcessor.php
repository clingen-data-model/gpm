<?php

namespace Tests\Dummies;

use App\DataExchange\DxMessage;
use App\DataExchange\Contracts\MessageProcessor;
use Lorisleiva\Actions\Concerns\AsJob;

class FakeMessageProcessor implements MessageProcessor
{
    use AsJob;

    static public $messages = [];

    public function handle(DxMessage $message): DxMessage
    {
        static::$messages[] = $message;

        return $message;
    }

}
