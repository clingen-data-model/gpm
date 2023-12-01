<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Models\StreamMessage;
use Lorisleiva\Actions\Concerns\AsJob;

class StreamMessageCreate
{
    use AsJob;

    public function handle(string $topic, $message): StreamMessage
    {
        return StreamMessage::create(compact('topic', 'message'));
    }
}
