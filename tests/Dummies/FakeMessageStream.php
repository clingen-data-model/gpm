<?php

namespace Tests\Dummies;

use Generator;
use App\DataExchange\Contracts\MessageStream;

class FakeMessageStream implements MessageStream
{
    public function __construct(private array $messages)
    {
    }


    public function addTopic($topic): MessageStream
    {
        return $this;
    }

    public function removeTopic($argument): MessageStream
    {
        return $this;
    }

    public function listTopics(): array
    {
        return [];
    }

    public function consume(): Generator
    {
        return $this->listen();
    }

    public function consumeSomeMessages($number): Generator
    {
        foreach ($this->listen() as $idx => $message) {
            if ( $idx < $number ) {
                yield $message;
            }
        }
    }

    public function listen(): Generator
    {
        yield from $this->messages;
    }




}
