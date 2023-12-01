<?php

namespace App\DataExchange\Contracts;

use App\DataExchange\DxMessage;
use Generator;

interface MessageStream
{
    /**
     * sets a topic
     *
     * @param  string  $topic topic name
     */
    public function addTopic(string $topic): MessageStream;

    /**
     * Add multiple topics at once.
     */
    public function addTopics(array $topics): MessageStream;

    /**
     * remove a topic subscription
     */
    public function removeTopic(string $topic): MessageStream;

    /**
     * Consumes incoming messages until end-of-file exception
     *
     * @return Generator|DxMessage
     */
    public function consume(): Generator;

    /**
     * Starts listening for incoming messages
     *
     * @return Generator|DxMessage
     */
    public function consumeSomeMessages($number): Generator;

    /**
     * Listen to topic until told to stop
     *
     * @return Generator|DxMessage
     */
    public function listen(): Generator;

    /**
     * @return array List of topics
     */
    public function listTopics(): array;
}
