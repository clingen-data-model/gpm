<?php

declare(strict_types=1);

namespace App\DataExchange\Kafka;

use App\DataExchange\Exceptions\StreamingServiceEndOfFIleException;
use RdKafka\Message;

class NoNewMessageHandler extends AbstractMessageHandler
{
    public function handle(Message $message)
    {
        if ($message->err == RD_KAFKA_RESP_ERR__PARTITION_EOF) {
            throw new StreamingServiceEndOfFIleException('No new messages in partition', $message->err);
        }

        parent::handle($message);
    }
}
