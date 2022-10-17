<?php

namespace App\DataExchange\Actions;

use App\DataExchange\DxMessage;
use App\DataExchange\Models\IncomingStreamMessage;
use App\DataExchange\Exceptions\DuplicateMessageException;

class IncomingMessageStore
{
    public function handle(DxMessage $message)
    {
        $key = $this->resolveKey($message);
        $storedMessage = IncomingStreamMessage::firstOrCreate([
            'key' => $key,
        ], [
            'timestamp' => $message->timestamp,
            'topic' => $message->topic,
            'partition' => $message->partition,
            'offset' => $message->offset,
            'error_code' => $message->err ?? 0,
            'payload' => $message->payload,
        ]);

        if ($storedMessage->payload != $message->payload) {
            throw new DuplicateMessageException('We got a message from the '.$message->topic.' with a key that already exists and a payload that is different', 422, ['storedMessage->payload' => $storedMessage->payload, 'payload' => $message->payload]);
        }

        return $storedMessage;
    }

    private function resolveKey(DxMessage $message): string
    {
        if (!is_null($message->key)) {
            return $message->key;
        }

        if (isset($message->payload->uuid) && !is_null($message->payload->uuid)) {
            return $message->payload->uuid;
        }

        return $message->topic.'-'.$message->timestamp;
    }
}
