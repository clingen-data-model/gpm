<?php

namespace App\DataExchange\Actions;

use Lorisleiva\Actions\Concerns\AsJob;
use App\DataExchange\Models\StreamMessage;

class StreamMessageCreate
{
    use AsJob;

    public function handle(string $topic, $message, string $eventUuid): StreamMessage
    {
        $event_uuid = $eventUuid;
        return StreamMessage::create(compact('topic', 'message', 'event_uuid'));
    }
}
