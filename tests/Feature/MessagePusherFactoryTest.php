<?php

namespace Tests\Feature;

use App\DataExchange\Kafka\KafkaProducer;
use App\DataExchange\MessagePushers\DisabledPusher;
use App\DataExchange\MessagePushers\MessageLogger;
use App\DataExchange\MessagePushers\MessagePusherFactory;
use Tests\TestCase;

class MessagePusherFactoryTest extends TestCase
{
    private $factory;

    public function setup(): void
    {
        parent::setup();
        $this->factory = new MessagePusherFactory();
    }

    /**
     * @test
     */
    public function returns_disabled_pusher_if_disabled(): void
    {
        config(['dx.push-enable' => false]);
        $this->assertInstanceOf(DisabledPusher::class, ($this->factory)());
    }

    /**
     * @test
     */
    public function returns_log_pusher_if_driver_is_log(): void
    {
        config(['dx.push-enable' => true, 'dx.driver' => 'log']);
        $this->assertInstanceOf(MessageLogger::class, ($this->factory)());
    }

    /**
     * @test
     */
    public function returns_kafka_produce_if_driver_is_kafka(): void
    {
        config(['dx.push-enable' => true, 'dx.driver' => 'kafka']);
        $this->assertInstanceOf(KafkaProducer::class, ($this->factory)());
    }
}
