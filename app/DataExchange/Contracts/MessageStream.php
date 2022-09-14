<?php

namespace App\DataExchange\Contracts;

use Generator;
use App\DataExchange\DxMessage;

interface MessageStream
{
    /**
     * sets a topic
     *
     * @param String $topic topic name
     *
     * @return MessageStream
     */
    public function addTopic(String $topic): MessageStream;

    /**
     * Add multiple topics at once.
     *
     * @param array $topics
     *
     * @return MessageStream
     */
    public function addTopics(Array $topics): MessageStream;

    /**
     * remove a topic subscription
     */
    public function removeTopic(String $topic): MessageStream;

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
     * @return Array List of topics
     */
    public function listTopics(): array;
}
