<?php

namespace App\DataExchange\Actions;

use Lorisleiva\Actions\Concerns\AsJob;
use App\DataExchange\Models\StreamMessage;

class StreamMessageCreate
{
    use AsJob;

    public function handle(string $topic, $message, string $eventUuid): StreamMessage
    {
        return StreamMessage::create([
            'topic' => $topic,
            'message' => $message,
            'event_uuid' => $eventUuid,
        ]);
    }
}
