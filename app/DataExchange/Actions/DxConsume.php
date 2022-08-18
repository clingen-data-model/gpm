<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Contracts\MessageProcessor;

class DxConsume
{
    public function __construct(private MessageStream $stream, private MessageProcessor $processor)
    {
    }

    public function handle(array $topics, ?int $limit = null): void
    {

        foreach ($topics as $topic) {
            $this->stream->addTopic($topic);
        }

        foreach($this->stream->consume() as $message) {
            $this->processor->handle($message);
        }
    }
}
