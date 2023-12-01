<?php

declare(strict_types=1);

namespace App\DataExchange\Contracts;

interface MessageConsumer
{
    /**
     * sets a topic
     *
     * @param  string  $topic topic name
     */
    public function addTopic(string $topic): MessageConsumer;

    /**
     * remove a topic subscription
     */
    public function removeTopic(string $topic): MessageConsumer;

    /**
     * Consumes incoming messages until end-of-file exception
     */
    public function consume(): MessageConsumer;

    /**
     * Starts listening for incoming messages
     */
    public function consumeSomeMessages($number): MessageConsumer;

    /**
     * Listen to topic until told to stop
     */
    public function listen(): MessageConsumer;

    /**
     * @return array List of topics
     */
    public function listTopics(): array;
}
