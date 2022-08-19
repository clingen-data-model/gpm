<?php

namespace App\DataExchange\Actions;

use App\DataExchange\Contracts\MessageStream;
use App\DataExchange\Contracts\MessageProcessor;
use Illuminate\Contracts\Bus\Dispatcher;

class DxConsume
{
    public function __construct(private MessageStream $stream, private MessageProcessor $processor, private Dispatcher $jobBus)
    {
    }

    public function handle(array $topics, ?int $limit = null): void
    {

        foreach($this->stream->addTopics($topics)->consume() as $message) {
            $this->jobBus->dispatch($this->processor::makeJob($message));
        }
    }
}
