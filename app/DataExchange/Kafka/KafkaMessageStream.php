<?php

declare(strict_types=1);

namespace App\DataExchange\Kafka;

use Generator;
use App\DataExchange\DxMessage;
use App\DataExchange\Contracts\MessageStream;

/**
 * @property array $topics
 */
class KafkaMessageStream implements MessageStream
{
    protected $topics = [];
    protected $listening = false;

    public function __construct(private \RdKafka\KafkaConsumer $kafkaConsumer)
    {
    }

    public function subscribe(): void
    {
        $this->kafkaConsumer->subscribe($this->topics);
    }


    /**
     * Continuously listens for new messages and yields them as they are received.
     *
     * @return Generator|DxMessage
     */
    public function listen(): Generator
    {
        $this->subscribe();

        while (true) {
            $message = $this->kafkaConsumer->consume(10000);
            try {
                $dxMessage = DxMessage::createFromRdKafkaMessage($message);
            } catch (\Exception $e) {
                dump($message);
            }
            if ($dxMessage->isReportableError()) {
                \Log::warning('Error message received from kafka broker: '.$dxMessage->errorString);
            }
            yield $dxMessage;
        }
    }

    /**
     * Begin listening for messages on topics in topic list
     *
     * @return Generator|DxMessage
     */
    public function consume(): Generator
    {
        foreach ($this->listen() as $dxMessage) {
            if ($dxMessage->isEndOfFile() || $dxMessage->isTimeOut()) {
                break;
            }
            yield $dxMessage;
        }
    }

    /**
     * Consumes a specified number of messages and yields them.
     *
     * @param $numbserOfMessages Integer
     * @return Generator|DxMessage
     */
    public function consumeSomeMessages($numberOfMessages): Generator
    {
        foreach ($this->consume() as $idx => $dxMessage) {
            if ($idx == $numberOfMessages) {
                break;
            }

            yield $dxMessage;
        }
    }


    /**
     * Add a topic to consume
     *
     * @param string $topicName Name of topic to add
     * @return MessageStream
     */
    public function addTopic(String $topicName): MessageStream
    {
        array_push($this->topics, $topicName);
        $this->cleanTopics();
        return $this;
    }

    public function addTopics(array $topics): MessageStream
    {
        foreach ($topics as $topic) {
            $this->addTopic($topic);
        }

        return $this;
    }


    /**
     * Remove topic from topic list
     *
     * @param string $topicName Name of topic to remove from topic list
     * @return MessageStream
     */
    public function removeTopic(String $topicName): MessageStream
    {
        if (in_array($topicName, $this->topics)) {
            unset($this->topics[array_search($topicName, $this->topics)]);
            $this->cleanTopics();
        }

        return $this;
    }

    /**
     * Get a list of topics currently being consumed
     *
     * @retrun array List of topic names
     */
    public function listTopics(): array
    {
        $availableTopics = $this->kafkaConsumer->getMetadata(true, null, 60e3)->getTopics();

        return array_map(
            function ($topic) {
                return [
                    'name' => $topic->getName(),
                    'offset' => $topic->getOffset()
                ];
            },
            $availableTopics
        );
    }

    public function __get($key)
    {
        if ($key == 'topics') {
            return $this->topics;
        }
        if ($key == 'consumer') {
            return $this->kafkaConsumer;
        }
    }

    /**
     * Make sure topic list is unique.
     */
    private function cleanTopics()
    {
        $this->topics = array_unique($this->topics);
        $this->topics = array_values($this->topics);
    }
}
