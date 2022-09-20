<?php

namespace App\DataExchange\MessagePushers;

use Illuminate\Support\Facades\Log;
use App\DataExchange\Kafka\KafkaProducer;
use App\DataExchange\Contracts\MessagePusher;
use App\DataExchange\MessagePushers\MessageLogger;
use App\DataExchange\MessagePushers\DisabledPusher;

class MessagePusherFactory
{
    public function __invoke(): MessagePusher
    {
        if (!config('dx.push-enable')) {
            return new DisabledPusher();
        }
        if (config('dx.driver') == 'kafka') {
            return app()->make(KafkaProducer::class);
        }
        if (config('dx.driver') == 'log') {
            return new MessageLogger();
        }

        Log::warning('No DataExchange driver set.  Defaulting to log driver');
        return new MessageLogger();
    }

}
