<?php

namespace App\DataExchange\Actions;

use Lorisleiva\Actions\Concerns\AsListener;

class IncomingMessageStore
{
    use AsListener;

    public function handle($kafkaMessage)
    {
    }
}
